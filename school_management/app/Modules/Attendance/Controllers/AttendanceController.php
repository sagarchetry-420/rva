<?php
namespace App\Modules\Attendance\Controllers;

use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Attendance\Validators\AttendanceValidator;
use App\Modules\Academic\Repositories\ClassRepository;
use App\Modules\Attendance\Services\AttendancePdfService;
use App\Modules\Attendance\Repositories\AttendanceRepository;

/**
 * AttendanceController — Handles attendance marking
 */
class AttendanceController extends \Controller
{
    private AttendanceService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AttendanceService();
    }

    public function index(): void
    {
        $classId = (int)$this->input('class_id', 0);
        $date = $this->input('attendance_date', date('Y-m-d'));
        
        // Force date to today for teachers
        if ($_SESSION['user_type'] === 'teacher') {
            $date = date('Y-m-d');
        }
        
        $classRepo = new ClassRepository();
        // Determine role-based class filtering
        $classes = [];
        if ($_SESSION['user_type'] === 'admin') {
            $classes = $classRepo->findAll();
        } else if ($_SESSION['user_type'] === 'teacher') {
            // If teacher, only show classes they teach or are class teacher of
            // For now, load all classes for simplicity, but in a real scenario we'd filter
            $classes = $classRepo->findAll();
        }

        $students = [];
        $isMarked = false;
        if ($classId && $date) {
            $students = $this->service->getAttendanceList($classId, $date);
            $isMarked = $this->service->isAttendanceMarked($classId, $date);
        }

        $this->render('Modules/Attendance/Views/index', [
            'pageTitle'       => 'Mark Attendance',
            'classes'         => $classes,
            'filterClass'     => $classId,
            'attendanceDate'  => $date,
            'students'        => $students,
            'isMarked'        => $isMarked
        ], $_SESSION['user_type']); // render in respective layout
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');

        switch ($action) {
            case 'save':
                if ($_SESSION['user_type'] === 'admin') {
                    $this->flash('error', 'Admins cannot mark daily attendance.');
                    $this->redirect(moduleUrl('admin', 'attendance'));
                    return;
                }
                $this->save();
                break;
            case 'applyLeave':
                $this->applyLeave();
                break;
            case 'exportPdf':
                $this->exportPdf();
                break;
            default:
                $this->flash('error', 'Invalid action.');
                $module = $_GET['module'] ?? 'admin';
                $actionName = $_GET['action'] ?? 'attendance';
                $this->redirect('/' . $module . '/' . $actionName);
        }
    }

    private function save(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        $validator = new AttendanceValidator();
        if (!$validator->validateBulkSave($data)) {
            $this->flash('error', $validator->firstError());
            $module = $_GET['module'] ?? 'admin';
            $actionName = $_GET['action'] ?? 'attendance';
            $this->redirect('/' . $module . '/' . $actionName . '?class_id=' . ($data['class_id'] ?? '') . '&attendance_date=' . ($data['attendance_date'] ?? ''));
            return;
        }

        if ($_SESSION['user_type'] === 'teacher') {
            $data['attendance_date'] = date('Y-m-d');
            if ($this->service->isAttendanceMarked((int)$data['class_id'], $data['attendance_date'])) {
                $this->flash('error', 'Attendance for this class has already been marked for today. You cannot modify it.');
                $this->redirect('/teacher/attendance?class_id=' . $data['class_id'] . '&attendance_date=' . $data['attendance_date']);
                return;
            }
        }

        $result = $this->service->saveBulkAttendance(
            (int)$data['class_id'],
            $data['attendance_date'],
            $data['attendance'],
            (int)$_SESSION['user_id']
        );

        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $module = $_GET['module'] ?? 'admin';
        $actionName = $_GET['action'] ?? 'attendance';
        $this->redirect('/' . $module . '/' . $actionName . '?class_id=' . $data['class_id'] . '&attendance_date=' . $data['attendance_date']);
    }

    private function applyLeave(): void
    {
        $this->validateCsrf();
        if ($_SESSION['user_type'] !== 'admin') {
            $this->flash('error', 'Only admins can manage leaves.');
            $this->redirect(moduleUrl('teacher', 'attendance'));
            return;
        }

        $data = $this->allInput();
        $studentId = (int)$data['student_id'];
        $classId = (int)$data['class_id'];
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $status = $data['leave_status']; // Leave or Half Leave
        $remarks = $data['remarks'] ?? '';
        
        if (strtotime($endDate) < strtotime($startDate)) {
            $this->flash('error', 'End date cannot be before start date.');
            $this->redirect('/admin/attendance?class_id=' . $classId . '&attendance_date=' . $startDate);
            return;
        }

        $documentPath = null;
        if (!empty($_FILES['leave_document']['name'])) {
            $uploadDir = APP_ROOT . '/uploads/leaves/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['leave_document']['name']);
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['leave_document']['tmp_name'], $targetFile)) {
                $documentPath = 'uploads/leaves/' . $fileName;
            }
        }

        // Generate date range
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        $attendanceRepo = new AttendanceRepository();

        if ($session) {
            while ($current <= $end) {
                $currentDate = date('Y-m-d', $current);
                $attendanceRepo->upsertAttendance([
                    'student_id'      => $studentId,
                    'class_id'        => $classId,
                    'session_id'      => $session['session_id'],
                    'attendance_date' => $currentDate,
                    'status'          => $status,
                    'remarks'         => $remarks,
                    'leave_document'  => $documentPath,
                    'marked_by'       => (int)$_SESSION['user_id']
                ]);
                $current = strtotime('+1 day', $current);
            }
            $this->flash('success', 'Leave applied successfully.');
        } else {
            $this->flash('error', 'No active session found.');
        }

        $this->redirect('/admin/attendance?class_id=' . $classId . '&attendance_date=' . $startDate);
    }

    private function exportPdf(): void
    {
        $this->validateCsrf();
        $classId = (int)$this->input('class_id', 0);
        $date = $this->input('attendance_date', date('Y-m-d'));
        $studentId = (int)$this->input('student_id', 0);

        if (!$classId) {
            $this->flash('error', 'Class ID is required for export.');
            $this->redirect(moduleUrl('admin', 'attendance'));
            return;
        }

        $pdfService = new AttendancePdfService();

        if ($studentId > 0) {
            // Specific student report
            $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
            $session = $academicRepo->getActiveSession();
            
            $db = \Database::getInstance();
            $student = $db->fetch("SELECT * FROM students s JOIN student_academics sa ON s.student_id = sa.student_id WHERE s.student_id = ?", [$studentId]);
            
            $attendanceRepo = new AttendanceRepository();
            $summary = $attendanceRepo->getStudentSummary($studentId, $session['session_id']);
            
            $pdfService->generateStudentReport($student, $summary);
        } else {
            // Whole class report for a date
            $students = $this->service->getAttendanceList($classId, $date);
            $pdfService->generateClassReport($classId, $date, $students);
        }
    }

    public function monthlyReport(): void
    {
        $classId = (int)$this->input('class_id', 0);
        $yearMonth = $this->input('month', date('Y-m'));
        
        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        
        $students = [];
        $classDetails = null;
        
        if ($classId && $session) {
            $classRepo = new ClassRepository();
            $classDetails = $classRepo->findById($classId);
            
            $attendanceRepo = new AttendanceRepository();
            $students = $attendanceRepo->getMonthlyClassAttendance($classId, $yearMonth, $session['session_id']);
        }
        
        $classRepo = new ClassRepository();
        $classes = $classRepo->findAll();
        $this->render('Modules/Attendance/Views/monthly_report', [
            'classes' => $classes,
            'filterClass' => $classId,
            'classDetails' => $classDetails,
            'yearMonth' => $yearMonth,
            'students' => $students,
            'pageTitle' => 'Monthly Class Attendance'
        ]);
    }

    public function exportMonthlyCsv(): void
    {
        $this->validateCsrf();
        $classId = (int)$this->input('class_id', 0);
        $yearMonth = $this->input('month', date('Y-m'));

        if (!$classId) {
            $this->flash('error', 'Class ID is required for export.');
            $this->redirect(moduleUrl('admin', 'index') . '?action=attendance/monthly');
            return;
        }

        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        
        $classRepo = new ClassRepository();
        $classDetails = $classRepo->findById($classId);
        
        $attendanceRepo = new AttendanceRepository();
        $students = $attendanceRepo->getMonthlyClassAttendance($classId, $yearMonth, $session['session_id']);

        $daysInMonth = (int)date('t', strtotime($yearMonth . '-01'));

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Monthly_Attendance_' . ($classDetails['class_name'] ?? '') . '_' . $yearMonth . '.csv"');

        $out = fopen('php://output', 'w');
        
        $header = ['Roll No', 'Name'];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $header[] = $i;
        }
        fputcsv($out, $header);

        foreach ($students as $s) {
            $row = [$s['roll_number'], $s['name']];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $status = $s['attendance'][$i] ?? '-';
                if ($status === 'Present') $status = 'P';
                elseif ($status === 'Absent') $status = 'A';
                elseif ($status === 'Leave') $status = 'L';
                elseif ($status === 'Half Leave') $status = 'HL';
                $row[] = $status;
            }
            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }
}
