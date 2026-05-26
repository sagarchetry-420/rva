<?php
namespace App\Modules\Student\Controllers;

use App\Modules\Student\Repositories\StudentRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;
use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\Exams\Repositories\ExamRepository;
use App\Modules\Exams\Repositories\ResultRepository;
use App\Modules\Exams\Repositories\ScheduleRepository;

/**
 * StudentPortalController — Handles the student-facing portal
 * (dashboard, profile, attendance, results, fees, notices, timetable, transcript)
 */
class StudentPortalController extends \Controller
{
    private StudentRepository $studentRepo;

    public function __construct()
    {
        parent::__construct();
        $this->studentRepo = new StudentRepository();
    }

    /**
     * Helper: get the current student record from the logged-in user
     */
    private function getCurrentStudent(): ?array
    {
        $userId = $_SESSION['user_id'] ?? 0;
        return $this->studentRepo->findByUserId($userId);
    }

    /**
     * Helper: get academic record for the current student
     */
    private function getStudentAcademic(int $studentId): ?array
    {
        return $this->db->fetch(
            "SELECT sa.*, c.class_name, c.section, asess.session_name 
             FROM student_academics sa
             JOIN classes c ON sa.class_id = c.class_id
             LEFT JOIN academic_sessions asess ON sa.session_id = asess.session_id
             WHERE sa.student_id = ? AND sa.admission_status = 'Active'
             ORDER BY sa.academic_id DESC LIMIT 1",
            [$studentId]
        );
    }

    // ─────────────────────────────────────────────
    //  Dashboard
    // ─────────────────────────────────────────────
    public function dashboard(): void
    {
        $student = $this->getCurrentStudent();
        $academic = $student ? $this->getStudentAcademic($student['student_id']) : null;
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $attendanceSummary = ['Present' => 0, 'Absent' => 0, 'Leave' => 0, 'Half Leave' => 0];
        $totalDays = 0;
        if ($student && $session) {
            $attendanceRepo = new AttendanceRepository();
            $attendanceSummary = $attendanceRepo->getStudentSummary($student['student_id'], $session['session_id']);
            $totalDays = array_sum($attendanceSummary);
        }

        $this->render('Modules/Student/Views/portal/dashboard', [
            'pageTitle'          => 'Student Dashboard',
            'student'            => $student,
            'academic'           => $academic,
            'session'            => $session,
            'attendanceSummary'  => $attendanceSummary,
            'totalDays'          => $totalDays
        ], 'student');
    }

    // ─────────────────────────────────────────────
    //  Profile
    // ─────────────────────────────────────────────
    public function profile(): void
    {
        $student = $this->getCurrentStudent();
        $academic = $student ? $this->getStudentAcademic($student['student_id']) : null;

        $this->render('Modules/Student/Views/portal/profile', [
            'pageTitle' => 'My Profile',
            'student'   => $student,
            'academic'  => $academic
        ], 'student');
    }

        // ─────────────────────────────────────────────
    //  ID Card
    // ─────────────────────────────────────────────
    public function idCard(): void
    {
        $student = $this->getCurrentStudent();
        $academic = $student ? $this->getStudentAcademic($student['student_id']) : null;
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        if (!$student) {
            $this->flash('error', 'Student not found.');
            $this->redirect(moduleUrl('student', 'dashboard'));
            return;
        }

        $this->render('Modules/Student/Views/portal/id_card', [
            'pageTitle' => 'Student ID Card',
            'student'   => $student,
            'academic'  => $academic,
            'session'   => $session
        ], 'student'); // Actually, might want a blank layout for printing, but we can handle that in the view by hiding sidebar/header via CSS.
    }

    // ─────────────────────────────────────────────
    //  Attendance
    // ─────────────────────────────────────────────
    public function attendance(): void
    {
        $student = $this->getCurrentStudent();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $attendanceSummary = ['Present' => 0, 'Absent' => 0, 'Leave' => 0, 'Half Leave' => 0];
        $recentAttendance = [];

        if ($student && $session) {
            $attendanceRepo = new AttendanceRepository();
            $attendanceSummary = $attendanceRepo->getStudentSummary($student['student_id'], $session['session_id']);
            
            $recentAttendance = $this->db->fetchAll(
                "SELECT attendance_date, status, remarks 
                 FROM attendance 
                 WHERE student_id = ? AND session_id = ? 
                 ORDER BY attendance_date DESC 
                 LIMIT 30",
                [$student['student_id'], $session['session_id']]
            );
        }

        $this->render('Modules/Student/Views/portal/attendance', [
            'pageTitle'          => 'My Attendance',
            'student'            => $student,
            'session'            => $session,
            'attendanceSummary'  => $attendanceSummary,
            'recentAttendance'   => $recentAttendance
        ], 'student');
    }

    // ─────────────────────────────────────────────
    //  Results
    // ─────────────────────────────────────────────
    public function results(): void
    {
        $student = $this->getCurrentStudent();
        $academicRepo = new ClassSubjectRepository();
        $activeSession = $academicRepo->getActiveSession();
        
        $sessions = [];
        $exams = [];
        $results = [];
        $selectedSessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : ($activeSession['session_id'] ?? 0);
        $selectedExamId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

        if ($student) {
            $sessions = $this->db->fetchAll(
                "SELECT DISTINCT asess.session_id, asess.session_name, asess.start_date
                 FROM student_academics sa
                 JOIN academic_sessions asess ON sa.session_id = asess.session_id
                 WHERE sa.student_id = ?
                 ORDER BY asess.start_date DESC",
                [$student['student_id']]
            );
            
            // If they don't have results in the active session but have in others, default to their latest session
            if (empty(array_filter($sessions, fn($s) => $s['session_id'] == $selectedSessionId)) && !empty($sessions)) {
                $selectedSessionId = $sessions[0]['session_id'];
            }

            if ($selectedSessionId > 0) {
                // Get all exams in this session where student has results
                $exams = $this->db->fetchAll(
                    "SELECT DISTINCT e.exam_id, e.exam_name
                     FROM results r
                     JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                     JOIN examinations e ON es.exam_id = e.exam_id
                     WHERE r.student_id = ? AND r.session_id = ? AND e.is_published = 1
                     ORDER BY e.start_date DESC",
                    [$student['student_id'], $selectedSessionId]
                );

                $query = "SELECT r.*, es.exam_date, es.max_marks, s.subject_name, e.exam_name
                          FROM results r
                          JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                          JOIN subjects s ON es.subject_id = s.subject_id
                          JOIN examinations e ON es.exam_id = e.exam_id
                          WHERE r.student_id = ? AND r.session_id = ? AND e.is_published = 1";
                $params = [$student['student_id'], $selectedSessionId];

                if ($selectedExamId > 0) {
                    $query .= " AND e.exam_id = ?";
                    $params[] = $selectedExamId;
                }

                $query .= " ORDER BY e.start_date DESC, s.subject_name";

                $results = $this->db->fetchAll($query, $params);
            }
        }

        $this->render('Modules/Student/Views/portal/results', [
            'pageTitle'         => 'My Results',
            'student'           => $student,
            'sessions'          => $sessions,
            'exams'             => $exams,
            'results'           => $results,
            'selectedSessionId' => $selectedSessionId,
            'selectedExamId'    => $selectedExamId
        ], 'student');
    }

    public function viewResult(): void
    {
        $student = $this->getCurrentStudent();
        $academicRepo = new ClassSubjectRepository();
        $activeSession = $academicRepo->getActiveSession();
        
        $examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
        $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : ($activeSession['session_id'] ?? 0);
        
        $examName = '';
        $results = [];
        
        if ($student && $examId && $sessionId) {
            // Get exam details
            $exam = $this->db->fetch("SELECT exam_name FROM examinations WHERE exam_id = ?", [$examId]);
            if ($exam) {
                $examName = $exam['exam_name'];
                
                $query = "SELECT r.*, es.exam_date, es.max_marks, s.subject_name
                          FROM results r
                          JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                          JOIN subjects s ON es.subject_id = s.subject_id
                          JOIN examinations e ON es.exam_id = e.exam_id
                          WHERE r.student_id = ? AND r.session_id = ? AND es.exam_id = ? AND e.is_published = 1
                          ORDER BY s.subject_name";
                $results = $this->db->fetchAll($query, [$student['student_id'], $sessionId, $examId]);
            }
        }
        
        $this->render('Modules/Student/Views/portal/view_result', [
            'pageTitle' => 'Exam Results',
            'student'   => $student,
            'examName'  => $examName,
            'results'   => $results,
            'sessionId' => $sessionId
        ], 'student');
    }

    // ─────────────────────────────────────────────
    //  Fees
    // ─────────────────────────────────────────────
    public function fees(): void
    {
        $student = $this->getCurrentStudent();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $fees = [];
        if ($student && $session) {
            $fees = $this->db->fetchAll(
                "SELECT f.*, fc.category_name, ss.service_name, f.payment_status as status, IF(f.payment_status = 'Paid', f.amount, 0) as paid_amount
                 FROM fees f
                 LEFT JOIN fee_categories fc ON f.category_id = fc.category_id
                 LEFT JOIN services ss ON f.service_id = ss.service_id
                 WHERE f.student_id = ? AND f.session_id = ?
                 ORDER BY f.due_date DESC",
                [$student['student_id'], $session['session_id']]
            );
        }

        $this->render('Modules/Student/Views/portal/fees', [
            'pageTitle' => 'My Fees',
            'student'   => $student,
            'session'   => $session,
            'fees'      => $fees
        ], 'student');
    }

    // ─────────────────────────────────────────────
    //  Notices (read-only)
    // ─────────────────────────────────────────────
    public function notices(): void
    {
        $notices = $this->db->fetchAll(
            "SELECT * FROM notices WHERE target_audience IN ('All', 'Students') ORDER BY created_at DESC"
        );

        $this->render('Modules/Student/Views/portal/notices', [
            'pageTitle' => 'Notices',
            'notices'   => $notices
        ], 'student');
    }

    // ─────────────────────────────────────────────
    //  Timetable
    // ─────────────────────────────────────────────
    public function timetable(): void
    {
        $student = $this->getCurrentStudent();
        $academic = $student ? $this->getStudentAcademic($student['student_id']) : null;
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $timetable = [];
        if ($academic && $session) {
            $timetable = $this->db->fetchAll(
                "SELECT tt.*, s.subject_name, t.first_name as teacher_first, t.last_name as teacher_last
                 FROM timetable tt
                 LEFT JOIN subjects s ON tt.subject_id = s.subject_id
                 LEFT JOIN teachers t ON tt.teacher_id = t.teacher_id
                 WHERE tt.class_id = ? AND tt.session_id = ?
                 ORDER BY FIELD(tt.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), tt.start_time",
                [$academic['class_id'], $session['session_id']]
            );
        }

        $this->render('Modules/Student/Views/portal/timetable', [
            'pageTitle'  => 'Class Timetable',
            'student'    => $student,
            'academic'   => $academic,
            'timetable'  => $timetable,
            'session'    => $session
        ], 'student');
    }

    // ─────────────────────────────────────────────
    //  Exam Routine
    // ─────────────────────────────────────────────
    public function exam_routine(): void
    {
        $student = $this->getCurrentStudent();
        $academic = $student ? $this->getStudentAcademic($student['student_id']) : null;
        $examId = (int)$this->input('exam_id', 0);
        
        $exams = [];
        $schedules = [];
        $selectedExam = null;
        
        if ($academic) {
            // Get all exams that have schedules for this class
            $exams = $this->db->fetchAll(
                "SELECT DISTINCT e.*
                 FROM examinations e
                 JOIN exam_schedules es ON e.exam_id = es.exam_id
                 WHERE es.class_id = ? AND e.is_approved = 1 AND e.is_schedule_published = 1
                 ORDER BY e.start_date DESC",
                [$academic['class_id']]
            );
            
            // If no exam selected but exams exist, select the first one
            if ($examId === 0 && !empty($exams)) {
                $examId = (int)$exams[0]['exam_id'];
            }
            
            if ($examId > 0) {
                $schedules = $this->db->fetchAll(
                    "SELECT es.*, e.exam_name, s.subject_name, s.subject_code
                     FROM exam_schedules es
                     JOIN examinations e ON es.exam_id = e.exam_id
                     JOIN subjects s ON es.subject_id = s.subject_id
                     WHERE es.class_id = ? AND e.exam_id = ? AND e.is_approved = 1 AND e.is_schedule_published = 1
                     ORDER BY es.exam_date ASC, es.start_time ASC",
                    [$academic['class_id'], $examId]
                );
                foreach ($exams as $e) {
                    if ($e['exam_id'] == $examId) {
                        $selectedExam = $e;
                        break;
                    }
                }
            }
        }

        $this->render('Modules/Student/Views/portal/exam_routine', [
            'pageTitle'    => 'My Exam Routine',
            'student'      => $student,
            'academic'     => $academic,
            'exams'        => $exams,
            'selectedExam' => $selectedExam,
            'schedules'    => $schedules
        ], 'student');
    }

    // ─────────────────────────────────────────────
    //  Transcript
    // ─────────────────────────────────────────────
    public function transcript(): void
    {
        $student = $this->getCurrentStudent();
        if (!$student) {
            $this->flash('error', 'Student record not found.');
            $this->redirect(moduleUrl('student', 'dashboard'));
            return;
        }

        $academic = $this->getStudentAcademic($student['student_id']);

        // Get all sessions in which the student has been enrolled
        $sessions = $this->db->fetchAll(
            "SELECT DISTINCT asess.session_id, asess.session_name, asess.start_date, asess.end_date,
                    c.class_name, c.section, sa.roll_number
             FROM student_academics sa
             JOIN academic_sessions asess ON sa.session_id = asess.session_id
             JOIN classes c ON sa.class_id = c.class_id
             WHERE sa.student_id = ?
             ORDER BY asess.start_date ASC",
            [$student['student_id']]
        );

        // Get all published results for this student across all sessions in a single hit
        $allResults = $this->db->fetchAll(
            "SELECT r.marks_obtained, r.grade, r.is_absent, r.remarks, r.session_id,
                    es.max_marks, s.subject_name, s.subject_code,
                    e.exam_id, e.exam_name, e.exam_type, e.start_date, e.end_date
             FROM results r
             JOIN exam_schedules es ON r.schedule_id = es.schedule_id
             JOIN subjects s ON es.subject_id = s.subject_id
             JOIN examinations e ON es.exam_id = e.exam_id
             WHERE r.student_id = ? AND e.is_published = 1
             ORDER BY e.start_date ASC, s.subject_name",
            [$student['student_id']]
        );

        // Group results by session and exam
        $groupedResults = [];
        foreach ($allResults as $row) {
            $sessId = $row['session_id'];
            $examId = $row['exam_id'];
            if (!isset($groupedResults[$sessId][$examId])) {
                $groupedResults[$sessId][$examId] = [
                    'exam' => [
                        'exam_id' => $examId,
                        'exam_name' => $row['exam_name'],
                        'exam_type' => $row['exam_type'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date']
                    ],
                    'results' => []
                ];
            }
            $groupedResults[$sessId][$examId]['results'][] = [
                'marks_obtained' => $row['marks_obtained'],
                'grade' => $row['grade'],
                'is_absent' => $row['is_absent'],
                'remarks' => $row['remarks'],
                'max_marks' => $row['max_marks'],
                'subject_name' => $row['subject_name'],
                'subject_code' => $row['subject_code']
            ];
        }

        $transcriptData = [];
        foreach ($sessions as $sess) {
            $sessId = $sess['session_id'];
            if (!isset($groupedResults[$sessId])) {
                continue;
            }

            $sessionExams = [];
            foreach ($groupedResults[$sessId] as $examData) {
                $exam = $examData['exam'];
                $results = $examData['results'];

                $totalMax = 0;
                $totalObtained = 0;
                foreach ($results as $r) {
                    $totalMax += (float)$r['max_marks'];
                    if (!$r['is_absent']) {
                        $totalObtained += (float)$r['marks_obtained'];
                    }
                }
                $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;

                // Calculate overall grade
                if ($percentage >= 90) $overallGrade = 'A+';
                elseif ($percentage >= 80) $overallGrade = 'A';
                elseif ($percentage >= 70) $overallGrade = 'B+';
                elseif ($percentage >= 60) $overallGrade = 'B';
                elseif ($percentage >= 50) $overallGrade = 'C';
                elseif ($percentage >= 40) $overallGrade = 'D';
                else $overallGrade = 'F';

                $sessionExams[] = [
                    'exam' => $exam,
                    'results' => $results,
                    'totalMax' => $totalMax,
                    'totalObtained' => $totalObtained,
                    'percentage' => $percentage,
                    'overallGrade' => $overallGrade
                ];
            }

            if (!empty($sessionExams)) {
                $transcriptData[] = [
                    'session' => $sess,
                    'exams' => $sessionExams
                ];
            }
        }

        $this->render('Modules/Student/Views/portal/transcript', [
            'pageTitle'      => 'Academic Transcript',
            'student'        => $student,
            'academic'       => $academic,
            'transcriptData' => $transcriptData
        ], 'student');
    }

    /**
     * Download transcript as PDF
     */
    public function downloadTranscript(): void
    {
        $student = $this->getCurrentStudent();
        if (!$student) {
            $this->flash('error', 'Student record not found.');
            $this->redirect(moduleUrl('student', 'transcript'));
            return;
        }

        $academic = $this->getStudentAcademic($student['student_id']);

        // Gather same data as transcript()
        $sessions = $this->db->fetchAll(
            "SELECT DISTINCT asess.session_id, asess.session_name, asess.start_date, asess.end_date,
                    c.class_name, c.section, sa.roll_number
             FROM student_academics sa
             JOIN academic_sessions asess ON sa.session_id = asess.session_id
             JOIN classes c ON sa.class_id = c.class_id
             WHERE sa.student_id = ?
             ORDER BY asess.start_date ASC",
            [$student['student_id']]
        );

        // Get all published results for this student across all sessions in a single hit
        $allResults = $this->db->fetchAll(
            "SELECT r.marks_obtained, r.grade, r.is_absent, r.remarks, r.session_id,
                    es.max_marks, s.subject_name, s.subject_code,
                    e.exam_id, e.exam_name, e.exam_type, e.start_date, e.end_date
             FROM results r
             JOIN exam_schedules es ON r.schedule_id = es.schedule_id
             JOIN subjects s ON es.subject_id = s.subject_id
             JOIN examinations e ON es.exam_id = e.exam_id
             WHERE r.student_id = ? AND e.is_published = 1
             ORDER BY e.start_date ASC, s.subject_name",
            [$student['student_id']]
        );

        // Group results by session and exam
        $groupedResults = [];
        foreach ($allResults as $row) {
            $sessId = $row['session_id'];
            $examId = $row['exam_id'];
            if (!isset($groupedResults[$sessId][$examId])) {
                $groupedResults[$sessId][$examId] = [
                    'exam' => [
                        'exam_id' => $examId,
                        'exam_name' => $row['exam_name'],
                        'exam_type' => $row['exam_type'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date']
                    ],
                    'results' => []
                ];
            }
            $groupedResults[$sessId][$examId]['results'][] = [
                'marks_obtained' => $row['marks_obtained'],
                'grade' => $row['grade'],
                'is_absent' => $row['is_absent'],
                'remarks' => $row['remarks'],
                'max_marks' => $row['max_marks'],
                'subject_name' => $row['subject_name'],
                'subject_code' => $row['subject_code']
            ];
        }

        $transcriptData = [];
        foreach ($sessions as $sess) {
            $sessId = $sess['session_id'];
            if (!isset($groupedResults[$sessId])) {
                continue;
            }

            $sessionExams = [];
            foreach ($groupedResults[$sessId] as $examData) {
                $exam = $examData['exam'];
                $results = $examData['results'];

                $totalMax = 0;
                $totalObtained = 0;
                foreach ($results as $r) {
                    $totalMax += (float)$r['max_marks'];
                    if (!$r['is_absent']) {
                        $totalObtained += (float)$r['marks_obtained'];
                    }
                }
                $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;

                // Calculate overall grade
                if ($percentage >= 90) $overallGrade = 'A+';
                elseif ($percentage >= 80) $overallGrade = 'A';
                elseif ($percentage >= 70) $overallGrade = 'B+';
                elseif ($percentage >= 60) $overallGrade = 'B';
                elseif ($percentage >= 50) $overallGrade = 'C';
                elseif ($percentage >= 40) $overallGrade = 'D';
                else $overallGrade = 'F';

                $sessionExams[] = [
                    'exam' => $exam,
                    'results' => $results,
                    'totalMax' => $totalMax,
                    'totalObtained' => $totalObtained,
                    'percentage' => $percentage,
                    'overallGrade' => $overallGrade
                ];
            }

            if (!empty($sessionExams)) {
                $transcriptData[] = [
                    'session' => $sess,
                    'exams' => $sessionExams
                ];
            }
        }

        // Generate PDF
        require_once APP_ROOT . '/includes/fpdf/fpdf.php';
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Header
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', APP_NAME), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'Official Academic Transcript', 0, 1, 'C');
        $pdf->Ln(3);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);

        // Student info
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(40, 7, 'Student Name:', 0);
        $pdf->SetFont('Arial', '', 11);
        $name = ($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['last_name'] ?? '');
        $pdf->Cell(60, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', trim($name)), 0);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(35, 7, 'Admission No:', 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, $student['admission_number'] ?? 'N/A', 0, 1);

        if ($academic) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(40, 7, 'Current Class:', 0);
            $pdf->SetFont('Arial', '', 11);
            $cls = ($academic['class_name'] ?? '') . ' ' . ($academic['section'] ?? '');
            $pdf->Cell(60, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', trim($cls)), 0);

            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(35, 7, 'Roll Number:', 0);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 7, $academic['roll_number'] ?? 'N/A', 0, 1);
        }

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(40, 7, 'Date of Birth:', 0);
        $pdf->SetFont('Arial', '', 11);
        $dob = isset($student['date_of_birth']) ? date('d M Y', strtotime($student['date_of_birth'])) : 'N/A';
        $pdf->Cell(60, 7, $dob, 0);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(35, 7, 'Generated:', 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, date('d M Y'), 0, 1);

        $pdf->Ln(3);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);

        if (empty($transcriptData)) {
            $pdf->SetFont('Arial', 'I', 12);
            $pdf->Cell(0, 10, 'No published exam results found for this student.', 0, 1, 'C');
        } else {
            foreach ($transcriptData as $td) {
                $sess = $td['session'];
                // Session header
                $pdf->SetFillColor(41, 128, 185);
                $pdf->SetTextColor(255);
                $pdf->SetFont('Arial', 'B', 11);
                $sessLabel = $sess['session_name'] . ' | ' . $sess['class_name'] . ' ' . $sess['section'];
                $pdf->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $sessLabel), 0, 1, 'L', true);
                $pdf->SetTextColor(0);
                $pdf->Ln(2);

                foreach ($td['exams'] as $ed) {
                    $exam = $ed['exam'];
                    // Exam sub-header
                    $pdf->SetFont('Arial', 'B', 10);
                    $pdf->SetFillColor(236, 240, 241);
                    $examLabel = $exam['exam_name'] . ' (' . $exam['exam_type'] . ')';
                    $pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $examLabel), 0, 1, 'L', true);

                    // Table header
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->SetFillColor(52, 73, 94);
                    $pdf->SetTextColor(255);
                    $pdf->Cell(70, 6, '  Subject', 1, 0, 'L', true);
                    $pdf->Cell(30, 6, 'Max Marks', 1, 0, 'C', true);
                    $pdf->Cell(30, 6, 'Obtained', 1, 0, 'C', true);
                    $pdf->Cell(20, 6, 'Grade', 1, 0, 'C', true);
                    $pdf->Cell(40, 6, 'Remarks', 1, 1, 'C', true);
                    $pdf->SetTextColor(0);

                    // Rows
                    $pdf->SetFont('Arial', '', 9);
                    $fill = false;
                    foreach ($ed['results'] as $r) {
                        if ($fill) $pdf->SetFillColor(245, 245, 245);
                        else $pdf->SetFillColor(255, 255, 255);

                        $subName = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $r['subject_name']);
                        $pdf->Cell(70, 6, '  ' . $subName, 1, 0, 'L', true);
                        $pdf->Cell(30, 6, $r['max_marks'], 1, 0, 'C', true);
                        $pdf->Cell(30, 6, $r['is_absent'] ? 'Absent' : $r['marks_obtained'], 1, 0, 'C', true);
                        $pdf->Cell(20, 6, $r['grade'] ?: '-', 1, 0, 'C', true);
                        $rmk = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $r['remarks'] ?? '-');
                        $pdf->Cell(40, 6, $rmk, 1, 1, 'C', true);
                        $fill = !$fill;
                    }

                    // Totals row
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->SetFillColor(230, 230, 230);
                    $pdf->Cell(70, 7, '  TOTAL', 1, 0, 'L', true);
                    $pdf->Cell(30, 7, $ed['totalMax'], 1, 0, 'C', true);
                    $pdf->Cell(30, 7, $ed['totalObtained'], 1, 0, 'C', true);
                    $pdf->Cell(20, 7, $ed['overallGrade'], 1, 0, 'C', true);
                    $pdf->Cell(40, 7, $ed['percentage'] . '%', 1, 1, 'C', true);

                    $pdf->Ln(4);
                }
                $pdf->Ln(2);
            }
        }

        // Footer note
        $pdf->Ln(5);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, 'This is a computer-generated transcript. No signature is required.', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Generated on ' . date('d M Y \a\t h:i A') . ' from ' . APP_NAME, 0, 1, 'C');

        $filename = 'Transcript_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $student['first_name'] . '_' . $student['last_name']) . '.pdf';
        $pdf->Output('D', $filename);
        exit;
    }

    public function downloadRoutine(): void
    {
        $student = $this->getCurrentStudent();
        $academic = $student ? $this->getStudentAcademic($student['student_id']) : null;
        $examId = (int)$this->input('exam_id', 0);

        if (!$academic || !$examId) {
            $this->flash('error', 'No data available for export.');
            $this->redirect(moduleUrl('student', 'exam_routine'));
            return;
        }

        $exam = $this->db->fetch("SELECT * FROM examinations WHERE exam_id = ?", [$examId]);
        if (!$exam) {
            $this->flash('error', 'Exam not found.');
            $this->redirect(moduleUrl('student', 'exam_routine'));
            return;
        }

        $schedules = $this->db->fetchAll(
            "SELECT es.*, s.subject_name, s.subject_code
             FROM exam_schedules es
             JOIN subjects s ON es.subject_id = s.subject_id
             WHERE es.class_id = ? AND es.exam_id = ?
             ORDER BY es.exam_date ASC, es.start_time ASC",
            [$academic['class_id'], $examId]
        );

        $filename = "Exam_Routine_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $exam['exam_name']) . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Day', 'Subject Name', 'Subject Code', 'Start Time', 'End Time', 'Full Marks', 'Pass Marks']);
        
        foreach ($schedules as $s) {
            fputcsv($output, [
                date('d-M-Y', strtotime($s['exam_date'])),
                date('l', strtotime($s['exam_date'])),
                $s['subject_name'],
                $s['subject_code'],
                date('h:i A', strtotime($s['start_time'])),
                date('h:i A', strtotime($s['end_time'])),
                (float)$s['full_marks'],
                (float)$s['pass_marks']
            ]);
        }
        
        fclose($output);
        exit;
    }

    public function exportFees(): void
    {
        $student = $this->getCurrentStudent();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        if (!$student || !$session) {
            $this->flash('error', 'No data available for export.');
            $this->redirect(moduleUrl('student', 'fees'));
            return;
        }

        $fees = $this->db->fetchAll(
            "SELECT f.*, fc.category_name, ss.service_name, f.payment_status as status, IF(f.payment_status = 'Paid', f.amount, 0) as paid_amount
             FROM fees f
             LEFT JOIN fee_categories fc ON f.category_id = fc.category_id
             LEFT JOIN services ss ON f.service_id = ss.service_id
             WHERE f.student_id = ? AND f.session_id = ?
             ORDER BY f.due_date DESC",
            [$student['student_id'], $session['session_id']]
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="My_Fees_' . $student['first_name'] . '.csv"');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Description', 'Amount', 'Paid', 'Balance', 'Due Date', 'Status']);
        foreach ($fees as $f) {
            $feeName = $f['service_name'] ?? $f['category_name'] ?? 'General Fee';
            if (!empty($f['remarks'])) {
                $feeName .= ' - ' . $f['remarks'];
            }
            fputcsv($out, [
                $feeName,
                $f['amount'] ?? 0,
                $f['paid_amount'] ?? 0,
                ($f['amount'] ?? 0) - ($f['paid_amount'] ?? 0),
                $f['due_date'] ?? '',
                $f['status'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }
}
