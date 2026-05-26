<?php
namespace App\Modules\Exams\Controllers;

use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Validators\ExamValidator;
use App\Modules\Exams\Repositories\ExamRepository;
use App\Modules\Exams\Repositories\ScheduleRepository;
use App\Modules\Academic\Repositories\ClassRepository;
use App\Modules\Academic\Repositories\SubjectRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;

/**
 * ExamController — Manages Exams and Schedules
 */
class ExamController extends \Controller
{
    private ExamService $service;
    private ExamRepository $examRepo;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ExamService();
        $this->examRepo = new ExamRepository();
    }

    public function index(): void
    {
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        
        $exams = [];
        if ($session) {
            $exams = $this->examRepo->findAllBySession($session['session_id']);
            foreach ($exams as &$exam) {
                $exam['classes'] = $this->examRepo->getExamClasses($exam['exam_id']);
                // Check marks progress
                $progress = $this->getExamMarksProgress($exam['exam_id'], $session['session_id']);
                $exam['is_marks_complete'] = $progress['is_complete'];
                $exam['missing_reports'] = $progress['missing_reports'];
            }
        }

        $classRepo = new ClassRepository();
        $classes = $classRepo->findAll();

        $this->render('Modules/Exams/Views/exams', [
            'pageTitle' => 'Examination Management',
            'exams'     => $exams,
            'classes'   => $classes,
            'session'   => $session
        ], 'admin');
    }

    public function schedules(): void
    {
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);

        if (!$examId || !$classId) {
            $this->flash('error', 'Select an Exam and a Class to view schedules.');
            $this->redirect(moduleUrl('admin', 'examinations'));
            return;
        }

        $exam = $this->examRepo->findById($examId);
        $exam['classes'] = $this->examRepo->getExamClasses($examId);
        
        $classRepo = new ClassRepository();
        $class = $classRepo->findById($classId);

        $scheduleRepo = new ScheduleRepository();
        $schedules = $scheduleRepo->findByExamAndClass($examId, $classId);

        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        // Get subjects specifically assigned to this class for the session
        $assignedSubjects = [];
        if ($session) {
            $assignedSubjects = $academicRepo->findByClass($classId, $session['session_id']);
        }

        // Build matrix
        $examSlots = [];
        $matrix = [];
        
        // Fetch all distinct time slots used anywhere in this exam so columns persist across classes
        $db = \Database::getInstance();
        
        // Auto-migrate legacy slots
        $legacySlots = $db->fetchAll("SELECT DISTINCT start_time, end_time FROM exam_schedules WHERE exam_id = ? AND class_id = ?", [$examId, $classId]);
        foreach ($legacySlots as $ls) {
            try {
                $db->execute("INSERT IGNORE INTO exam_slots (exam_id, class_id, start_time, end_time) VALUES (?, ?, ?, ?)", [$examId, $classId, $ls['start_time'], $ls['end_time']]);
            } catch (\Exception $e) {}
        }

        $allExamSlots = $db->fetchAll(
            "SELECT start_time, end_time FROM exam_slots WHERE exam_id = ? AND class_id = ?",
            [$examId, $classId]
        );
        foreach ($allExamSlots as $s) {
            $slotKey = $s['start_time'].'|'.$s['end_time'];
            $examSlots[$slotKey] = [
                'start_time' => $s['start_time'],
                'end_time'   => $s['end_time'],
                'label'      => date('h:i A', strtotime($s['start_time'])) . ' - ' . date('h:i A', strtotime($s['end_time']))
            ];
        }

        foreach ($schedules as $s) {
            $slotKey = $s['start_time'].'|'.$s['end_time'];
            $matrix[$s['exam_date']][$slotKey] = [
                'subject_id' => $s['subject_id'],
                'full_marks' => $s['full_marks'],
                'pass_marks' => $s['pass_marks']
            ];
        }

        // Sort exam slots chronologically
        uasort($examSlots, function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        // Determine date range for rows (extract from existing matrix or create blank rows)
        $dates = array_keys($matrix);
        sort($dates);
        
        if (empty($dates)) {
            $subjectCount = count($assignedSubjects) > 0 ? count($assignedSubjects) : 5;
            for ($i = 0; $i < $subjectCount; $i++) {
                $dates[] = "";
            }
        }

        $this->render('Modules/Exams/Views/schedules_v2', [
            'pageTitle'  => 'Exam Schedule: ' . $exam['exam_name'],
            'exam'       => $exam,
            'class'      => $class,
            'schedules'  => $schedules,
            'subjects'   => $assignedSubjects,
            'examSlots'  => $examSlots,
            'matrix'     => $matrix,
            'dates'      => $dates
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');

        switch ($action) {
            case 'create_exam':
                $this->createExam();
                break;
            case 'update_schedules':
                $this->updateSchedules();
                break;
            case 'approve_exam':
                $this->approveExam();
                break;
            case 'download_schedules_template':
                $this->downloadSchedulesTemplate();
                break;
            case 'import_schedules_csv':
                $this->importSchedulesCsv();
                break;
            case 'download_exam_details':
                $this->downloadExamDetails();
                break;
            case 'download_exam_details':
                $this->downloadExamDetails();
                break;
            case 'add_exam_slot':
                $this->addExamSlot();
                break;
            case 'edit_exam_slot':
                $this->editExamSlot();
                break;
            case 'delete_exam_slot':
                $this->deleteExamSlot();
                break;
            case 'toggle_publish_schedule':
                $this->togglePublishSchedule();
                break;
            case 'toggle_publish_results':
                $this->togglePublishResults();
                break;
            case 'get_missing_marks':
                $this->getMissingMarksAjax();
                break;
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('admin', 'examinations'));
        }
    }

    private function createExam(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        // Auto-set exam_name from exam_type if not provided
        if (empty($data['exam_name'])) {
            $data['exam_name'] = $data['exam_type'] ?? 'Exam';
        }

        $validator = new ExamValidator();
        if (!$validator->validateExam($data)) {
            $this->flash('error', $validator->firstError());
            $this->redirect(moduleUrl('admin', 'examinations'));
            return;
        }

        $result = $this->service->createExam($data, $_SESSION['user_id'], $_SESSION['user_type']);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'examinations'));
    }

    private function approveExam(): void
    {
        $this->validateCsrf();
        $examId = (int)$this->input('exam_id', 0);
        
        $result = $this->service->approveExam($examId, $_SESSION['user_id']);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'examinations'));
    }

    private function togglePublishSchedule(): void
    {
        $this->validateCsrf();
        $examId = (int)$this->input('exam_id', 0);
        $state = (int)$this->input('state', 0);

        if ($examId > 0) {
            $db = \Database::getInstance();
            $db->execute("UPDATE examinations SET is_schedule_published = ? WHERE exam_id = ?", [$state, $examId]);
            $this->flash('success', $state ? 'Schedule published successfully.' : 'Schedule unpublished successfully.');
        } else {
            $this->flash('error', 'Invalid exam ID.');
        }
        $this->redirect(moduleUrl('admin', 'examinations'));
    }

    private function togglePublishResults(): void
    {
        $this->validateCsrf();
        $examId = (int)$this->input('exam_id', 0);
        $state = (int)$this->input('state', 0);

        if ($examId > 0) {
            $db = \Database::getInstance();
            
            if ($state == 1) {
                $academicRepo = new ClassSubjectRepository();
                $session = $academicRepo->getActiveSession();
                $progress = $this->getExamMarksProgress($examId, $session['session_id']);
                if (!$progress['is_complete']) {
                    $this->flash('error', 'Cannot publish results. Marks entry is incomplete.');
                    $this->redirect(moduleUrl('admin', 'examinations'));
                    return;
                }
            }

            $db->execute("UPDATE examinations SET is_published = ? WHERE exam_id = ?", [$state, $examId]);
            $this->flash('success', $state ? 'Results published successfully.' : 'Results unpublished successfully.');
        } else {
            $this->flash('error', 'Invalid exam ID.');
        }
        $this->redirect(moduleUrl('admin', 'examinations'));
    }

    private function getMissingMarksAjax(): void
    {
        $examId = (int)$this->input('exam_id', 0);
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        
        $progress = $this->getExamMarksProgress($examId, $session['session_id']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'reports' => $progress['missing_reports']]);
        exit;
    }

    private function getExamMarksProgress(int $examId, int $sessionId): array
    {
        return $this->examRepo->getExamMarksProgressStats($examId, $sessionId);
    }

    private function addExamSlot(): void
    {
        $this->validateCsrf();
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);
        $startTime = $this->input('start_time', '');
        $endTime = $this->input('end_time', '');

        if (!$examId || !$classId || !$startTime || !$endTime) {
            $this->flash('error', 'All fields are required.');
            $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
            return;
        }

        if (strtotime($startTime) >= strtotime($endTime)) {
            $this->flash('error', 'Start time must be before end time.');
            $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
            return;
        }

        $db = \Database::getInstance();

        // Check for overlapping slots
        $existingSlots = $db->fetchAll("SELECT start_time, end_time FROM exam_slots WHERE exam_id = ? AND class_id = ?", [$examId, $classId]);
        $newStart = strtotime($startTime);
        $newEnd = strtotime($endTime);

        foreach ($existingSlots as $slot) {
            $existStart = strtotime($slot['start_time']);
            $existEnd = strtotime($slot['end_time']);

            // Overlap condition: Start A < End B AND End A > Start B
            if ($newStart < $existEnd && $newEnd > $existStart) {
                $this->flash('error', 'The time slot overlaps with an existing column (' . date('h:i A', $existStart) . ' - ' . date('h:i A', $existEnd) . ').');
                $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
                return;
            }
        }

        try {
            $db->execute(
                "INSERT INTO exam_slots (exam_id, class_id, start_time, end_time) VALUES (?, ?, ?, ?)",
                [$examId, $classId, $startTime, $endTime]
            );
            $this->flash('success', 'Time column added successfully.');
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to add column. Time slot might already exist.');
        }
        $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
    }

    private function editExamSlot(): void
    {
        $this->validateCsrf();
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);
        $oldStartTime = $this->input('old_start_time', '');
        $oldEndTime = $this->input('old_end_time', '');
        $startTime = $this->input('start_time', '');
        $endTime = $this->input('end_time', '');

        if (!$examId || !$classId || !$startTime || !$endTime || !$oldStartTime || !$oldEndTime) {
            $this->flash('error', 'Missing required fields.');
            $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
            return;
        }

        $db = \Database::getInstance();

        if (strtotime($startTime) >= strtotime($endTime)) {
            $this->flash('error', 'Start time must be before end time.');
            $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
            return;
        }

        // Check for overlapping slots
        $existingSlots = $db->fetchAll("SELECT start_time, end_time FROM exam_slots WHERE exam_id = ? AND class_id = ?", [$examId, $classId]);
        $newStart = strtotime($startTime);
        $newEnd = strtotime($endTime);

        foreach ($existingSlots as $slot) {
            // Ignore the slot currently being edited
            if (strtotime($slot['start_time']) === strtotime($oldStartTime) && strtotime($slot['end_time']) === strtotime($oldEndTime)) {
                continue;
            }

            $existStart = strtotime($slot['start_time']);
            $existEnd = strtotime($slot['end_time']);

            if ($newStart < $existEnd && $newEnd > $existStart) {
                $this->flash('error', 'The time slot overlaps with an existing column (' . date('h:i A', $existStart) . ' - ' . date('h:i A', $existEnd) . ').');
                $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
                return;
            }
        }

        $db->execute(
            "UPDATE exam_slots SET start_time = ?, end_time = ? WHERE exam_id = ? AND class_id = ? AND start_time = ? AND end_time = ?",
            [$startTime, $endTime, $examId, $classId, $oldStartTime, $oldEndTime]
        );
        
        // Also update any actual scheduled subjects linked to the old times!
        $db->execute(
            "UPDATE exam_schedules SET start_time = ?, end_time = ? WHERE exam_id = ? AND class_id = ? AND start_time = ? AND end_time = ?",
            [$startTime, $endTime, $examId, $classId, $oldStartTime, $oldEndTime]
        );

        $this->flash('success', 'Time column updated successfully.');
        $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
    }

    private function deleteExamSlot(): void
    {
        $this->validateCsrf();
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);
        $startTime = $this->input('start_time', '');
        $endTime = $this->input('end_time', '');

        if ($examId && $classId && $startTime && $endTime) {
            $db = \Database::getInstance();
            $db->execute("DELETE FROM exam_slots WHERE exam_id = ? AND class_id = ? AND start_time = ? AND end_time = ?", [$examId, $classId, $startTime, $endTime]);
            $db->execute("DELETE FROM exam_schedules WHERE exam_id = ? AND class_id = ? AND start_time = ? AND end_time = ?", [$examId, $classId, $startTime, $endTime]);
            $this->flash('success', 'Time column removed.');
        } else {
            $this->flash('error', 'Missing parameters.');
        }
        $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
    }

    public function updateSchedules(): void
    {
        $this->validateCsrf();
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);

        $schedules = $_POST['schedules'] ?? [];
        $rowDates = $_POST['row_dates'] ?? [];

        file_put_contents('debug_schedule_post.txt', print_r($_POST, true));

        if (!$examId || !$classId) {
            $this->flash('error', 'Invalid request.');
            $this->redirect(moduleUrl('admin', 'examinations'));
            return;
        }

        // We will do a full replace: delete all existing schedules for this exam/class, then insert the new ones.
        $db = \Database::getInstance();
        $db->delete('exam_schedules', 'exam_id = ? AND class_id = ?', [$examId, $classId]);

        $scheduleRepo = new ScheduleRepository();
        $count = 0;

        foreach ($schedules as $rowIndex => $slots) {
            $rawDate = $rowDates[$rowIndex] ?? '';
            if (empty($rawDate)) continue;

            $time = false;
            // Explicitly support DD-MM-YYYY if it contains dashes
            if (strpos($rawDate, '-') !== false) {
                $dt = \DateTime::createFromFormat('d-m-Y', $rawDate);
                if ($dt !== false) {
                    $time = $dt->getTimestamp();
                }
            }
            
            // Fallback
            if (!$time) {
                $time = strtotime($rawDate);
            }
            
            $date = $time ? date('Y-m-d', $time) : $rawDate;

            foreach ($slots as $slotKey => $slotData) {
                $subId = isset($slotData['subject_id']) ? (int)$slotData['subject_id'] : 0;
                if ($subId > 0) {
                    $parts = explode('|', $slotKey);
                    if (count($parts) >= 2) {
                        $startTime = $parts[0];
                        $endTime = $parts[1];
                        $fullMarks = isset($slotData['full_marks']) && $slotData['full_marks'] !== '' ? (float)$slotData['full_marks'] : 100;
                        $passMarks = isset($slotData['pass_marks']) && $slotData['pass_marks'] !== '' ? (float)$slotData['pass_marks'] : 35;

                        $scheduleRepo->create([
                            'exam_id'    => $examId,
                            'class_id'   => $classId,
                            'subject_id' => $subId,
                            'exam_date'  => $date,
                            'start_time' => $startTime,
                            'end_time'   => $endTime,
                            'full_marks' => $fullMarks,
                            'pass_marks' => $passMarks,
                            'room_number'=> null
                        ]);
                        $count++;
                    }
                }
            }
        }

        $this->flash('success', "Schedule saved. Assigned $count subjects.");
        $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
    }

    public function downloadSchedulesTemplate(): void
    {
        $this->validateCsrf(false); // No CSRF for GET
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);

        $exam = $this->examRepo->findById($examId);
        
        $filename = "exam_schedule_template.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date (DD-MM-YYYY)', 'Start Time (HH:MM)', 'End Time (HH:MM)', 'Subject Code', 'Full Marks', 'Pass Marks']);
        fputcsv($output, [date('d-m-Y', strtotime($exam['start_date'])), '09:00', '12:00', 'MATH101', '100', '35']);
        fclose($output);
        exit;
    }

    private function downloadExamDetails(): void
    {
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);
        
        if (!$examId || !$classId) {
            $this->flash('error', 'Invalid request.');
            $this->redirect(moduleUrl('admin', 'examinations'));
            return;
        }

        $exam = $this->examRepo->findById($examId);
        $classRepo = new ClassRepository();
        $class = $classRepo->findById($classId);

        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        $sessionId = $session ? $session['session_id'] : 0;

        $db = \Database::getInstance();
        $subjects = $db->fetchAll(
            "SELECT s.subject_name, s.subject_code 
             FROM class_subjects cs 
             JOIN subjects s ON cs.subject_id = s.subject_id 
             WHERE cs.class_id = ? AND cs.session_id = ?
             ORDER BY s.subject_name",
            [$classId, $sessionId]
        );

        $classNameClean = preg_replace('/[^A-Za-z0-9]/', '_', $class['class_name'] . '_' . $class['section']);
        $filename = "exam_details_" . $classNameClean . ".csv";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Exam Meta Info
        fputcsv($output, ['Exam Name', 'Class', 'Start Date', 'End Date']);
        fputcsv($output, [
            $exam['exam_name'], 
            $class['class_name'] . ' ' . $class['section'],
            $exam['start_date'],
            $exam['end_date']
        ]);
        
        fputcsv($output, []); // Empty row for spacing
        
        // Subjects
        fputcsv($output, ['Assigned Subject Name', 'Subject Code']);
        foreach ($subjects as $sub) {
            fputcsv($output, [$sub['subject_name'], $sub['subject_code']]);
        }
        
        fclose($output);
        exit;
    }

    private function importSchedulesCsv(): void
    {
        $this->validateCsrf();
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);
        
        if (!$examId || !$classId || empty($_FILES['csv_file']['tmp_name'])) {
            $this->flash('error', 'Invalid request or missing file.');
            $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
            return;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if ($handle === false) {
            $this->flash('error', 'Could not read the uploaded file.');
            $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
            return;
        }

        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        $sessionId = $session ? $session['session_id'] : 0;

        // Get allowed subjects for this class
        $db = \Database::getInstance();
        $allowedSubjects = $db->fetchAll(
            "SELECT cs.subject_id, s.subject_code 
             FROM class_subjects cs 
             JOIN subjects s ON cs.subject_id = s.subject_id 
             WHERE cs.class_id = ? AND cs.session_id = ?",
            [$classId, $sessionId]
        );
        $subjectMap = [];
        foreach ($allowedSubjects as $sub) {
            $subjectMap[strtoupper(trim($sub['subject_code']))] = $sub['subject_id'];
        }

        $exam = $db->fetch("SELECT start_date, end_date FROM examinations WHERE exam_id = ?", [$examId]);
        $examStart = strtotime($exam['start_date']);
        $examEnd = strtotime($exam['end_date']);

        $header = fgetcsv($handle); // skip header
        $validRows = [];
        $rowNum = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($data) < 6) continue;
            
            $rawDate = trim($data[0]);
            
            $time = false;
            // Explicitly support DD-MM-YYYY if it contains dashes
            if (strpos($rawDate, '-') !== false) {
                $dt = \DateTime::createFromFormat('d-m-Y', $rawDate);
                if ($dt !== false) {
                    $time = $dt->getTimestamp();
                }
            }
            
            if (!$time) {
                $time = strtotime($rawDate);
            }
            
            // Smart Ambiguity Resolution: If it falls outside bounds, try swapping Month and Day
            if ($time && ($time < $examStart || $time > $examEnd)) {
                $parts = preg_split('/[-\/.]/', $rawDate);
                if (count($parts) === 3) {
                    $altDate = $parts[1] . '-' . $parts[0] . '-' . $parts[2];
                    $altTime = strtotime($altDate);
                    if ($altTime && $altTime >= $examStart && $altTime <= $examEnd) {
                        $time = $altTime;
                    }
                }
            }

            if (!$time) {
                $this->flash('error', "Invalid date format on row $rowNum.");
                fclose($handle);
                $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
                return;
            }
            if ($time < $examStart || $time > $examEnd) {
                $this->flash('error', "Date '$rawDate' on row $rowNum is outside the exam's allowed date range ({$exam['start_date']} to {$exam['end_date']}).");
                fclose($handle);
                $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
                return;
            }
            $date = date('Y-m-d', $time);

            $startTime = date('H:i:s', strtotime(trim($data[1])));
            $endTime = date('H:i:s', strtotime(trim($data[2])));
            if ($startTime >= $endTime) {
                $this->flash('error', "Start time must be before end time on row $rowNum.");
                fclose($handle);
                $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
                return;
            }

            $subjectCode = strtoupper(trim($data[3]));
            if (!isset($subjectMap[$subjectCode])) {
                $this->flash('error', "Subject code '$subjectCode' on row $rowNum is not assigned to this class.");
                fclose($handle);
                $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
                return;
            }

            $fullMarks = (float)trim($data[4]);
            $passMarks = (float)trim($data[5]);

            $validRows[] = [
                'exam_id'    => $examId,
                'class_id'   => $classId,
                'subject_id' => $subjectMap[$subjectCode],
                'exam_date'  => $date,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'full_marks' => $fullMarks,
                'pass_marks' => $passMarks,
                'room_number'=> null
            ];
        }
        fclose($handle);

        if (empty($validRows)) {
            $this->flash('error', "No valid data found in CSV.");
            $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
            return;
        }

        try {
            $db->transaction(function() use ($db, $examId, $classId, $validRows) {
                $db->delete('exam_schedules', 'exam_id = ? AND class_id = ?', [$examId, $classId]);
                $scheduleRepo = new ScheduleRepository();
                foreach ($validRows as $row) {
                    $scheduleRepo->create($row);
                }
            });
            $this->flash('success', "Exam schedule imported successfully. " . count($validRows) . " records added.");
        } catch (\Exception $e) {
            $this->flash('error', "Failed to import schedule: " . $e->getMessage());
        }

        $this->redirect('/admin/schedules?exam_id=' . $examId . '&class_id=' . $classId);
    }
}
