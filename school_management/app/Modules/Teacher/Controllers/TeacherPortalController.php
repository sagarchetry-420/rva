<?php
namespace App\Modules\Teacher\Controllers;

use App\Modules\Teacher\Repositories\TeacherRepository;
use App\Modules\Academic\Repositories\ClassRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;
use App\Modules\Academic\Repositories\SubjectRepository;
use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Exams\Repositories\ExamRepository;
use App\Modules\Exams\Repositories\ScheduleRepository;
use App\Modules\Exams\Repositories\ResultRepository;

/**
 * TeacherPortalController — Handles the teacher-facing portal
 * (dashboard, my-classes, attendance, examinations, results, notices, timetable)
 */
class TeacherPortalController extends \Controller
{
    private TeacherRepository $teacherRepo;

    public function __construct()
    {
        parent::__construct();
        $this->teacherRepo = new TeacherRepository();
    }

    /**
     * Helper: get the current teacher record from the logged-in user
     */
    private function getCurrentTeacher(): ?array
    {
        $userId = $_SESSION['user_id'] ?? 0;
        return $this->db->fetch("SELECT * FROM teachers WHERE user_id = ?", [$userId]);
    }

    // ─────────────────────────────────────────────
    //  Dashboard
    // ─────────────────────────────────────────────
    public function dashboard(): void
    {
        $teacher = $this->getCurrentTeacher();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $classesTeaching = 0;
        $subjectsTeaching = 0;
        $upcomingExams = [];

        if ($teacher && $session) {
            // Count distinct classes this teacher is assigned to
            $classesData = $this->db->fetchAll(
                "SELECT DISTINCT cs.class_id, c.class_name, c.section 
                 FROM class_subjects cs 
                 JOIN classes c ON cs.class_id = c.class_id 
                 WHERE cs.teacher_id = ? AND cs.session_id = ?",
                [$teacher['teacher_id'], $session['session_id']]
            );
            $classesTeaching = count($classesData);

            // Count subjects
            $subjectsData = $this->db->fetchAll(
                "SELECT DISTINCT cs.subject_id 
                 FROM class_subjects cs 
                 WHERE cs.teacher_id = ? AND cs.session_id = ?",
                [$teacher['teacher_id'], $session['session_id']]
            );
            $subjectsTeaching = count($subjectsData);

            // Check if teacher is a class teacher
            $classTeacherOf = $this->db->fetch(
                "SELECT class_name, section FROM classes WHERE class_teacher_id = ?",
                [$teacher['teacher_id']]
            );

            // Upcoming exams for classes this teacher handles
            $classIds = array_column($classesData, 'class_id');
            if (!empty($classIds)) {
                $placeholders = implode(',', array_fill(0, count($classIds), '?'));
                $upcomingExams = $this->db->fetchAll(
                    "SELECT DISTINCT e.exam_name, e.start_date, e.end_date
                     FROM examinations e
                     JOIN exam_classes ec ON e.exam_id = ec.exam_id
                     WHERE ec.class_id IN ({$placeholders})
                       AND e.session_id = ?
                       AND e.end_date >= CURDATE()
                     ORDER BY e.start_date ASC
                     LIMIT 5",
                    array_merge($classIds, [$session['session_id']])
                );
            }
        }

        $this->render('Modules/Teacher/Views/portal/dashboard', [
            'pageTitle'        => 'Teacher Dashboard',
            'teacher'          => $teacher,
            'classesTeaching'  => $classesTeaching,
            'subjectsTeaching' => $subjectsTeaching,
            'classTeacherOf'   => $classTeacherOf ?? null,
            'upcomingExams'    => $upcomingExams,
            'session'          => $session
        ], 'teacher');
    }

    // ─────────────────────────────────────────────
    //  ID Card
    // ─────────────────────────────────────────────
    public function idCard(): void
    {
        $teacher = $this->getCurrentTeacher();
        
        if (!$teacher) {
            $this->flash('error', 'Teacher not found.');
            $this->redirect(moduleUrl('teacher', 'dashboard'));
            return;
        }

        $this->render('Modules/Teacher/Views/portal/id_card', [
            'pageTitle' => 'Teacher ID Card',
            'teacher'   => $teacher
        ], 'teacher');
    }

    // ─────────────────────────────────────────────
    //  My Classes
    // ─────────────────────────────────────────────
    public function myClasses(): void
    {
        $teacher = $this->getCurrentTeacher();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $assignments = [];
        if ($teacher && $session) {
            $assignments = $this->db->fetchAll(
                "SELECT cs.*, c.class_name, c.section, s.subject_name, s.subject_code
                 FROM class_subjects cs
                 JOIN classes c ON cs.class_id = c.class_id
                 JOIN subjects s ON cs.subject_id = s.subject_id
                 WHERE cs.teacher_id = ? AND cs.session_id = ?
                 ORDER BY c.class_name, c.section, s.subject_name",
                [$teacher['teacher_id'], $session['session_id']]
            );
        }

        $this->render('Modules/Teacher/Views/portal/my_classes', [
            'pageTitle'    => 'My Classes & Subjects',
            'teacher'      => $teacher,
            'assignments'  => $assignments,
            'session'      => $session
        ], 'teacher');
    }

    // ─────────────────────────────────────────────
    //  Attendance (uses the shared Attendance module)
    // ─────────────────────────────────────────────
    public function attendance(): void
    {
        $teacher = $this->getCurrentTeacher();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        // Get classes this teacher is assigned to
        $classes = [];
        if ($teacher && $session) {
            $classes = $this->db->fetchAll(
                "SELECT DISTINCT c.class_id, c.class_name, c.section 
                 FROM class_subjects cs 
                 JOIN classes c ON cs.class_id = c.class_id 
                 WHERE cs.teacher_id = ? AND cs.session_id = ?
                 ORDER BY c.class_name, c.section",
                [$teacher['teacher_id'], $session['session_id']]
            );
        }

        $classId = (int)$this->input('class_id', 0);
        $date = $this->input('attendance_date', date('Y-m-d'));

        $students = [];
        $isMarked = false;
        if ($classId && $date) {
            $service = new AttendanceService();
            $students = $service->getAttendanceList($classId, $date);
            $isMarked = $service->isAttendanceMarked($classId, $date);
        }

        $this->render('Modules/Attendance/Views/index', [
            'pageTitle'      => 'Mark Attendance',
            'classes'        => $classes,
            'filterClass'    => $classId,
            'attendanceDate' => $date,
            'students'       => $students,
            'isMarked'       => $isMarked
        ], 'teacher');
    }

    public function saveAttendance(): void
    {
        $this->validateCsrf();
        
        $data = $this->allInput();
        $classId = (int)($data['class_id'] ?? 0);
        $date = $data['attendance_date'] ?? date('Y-m-d');
        $attendanceData = $data['attendance'] ?? [];

        if (!$classId || empty($attendanceData)) {
            $this->flash('error', 'No attendance data submitted.');
            $this->redirect('/teacher/attendance?class_id=' . $classId . '&attendance_date=' . $date);
            return;
        }

        $service = new AttendanceService();
        if ($service->isAttendanceMarked($classId, $date)) {
            $this->flash('error', 'Attendance for this class has already been marked for this date and cannot be modified.');
            $this->redirect('/teacher/attendance?class_id=' . $classId . '&attendance_date=' . $date);
            return;
        }

        $validator = new \App\Modules\Attendance\Validators\AttendanceValidator();
        if (!$validator->validateBulkSave($data)) {
            $this->flash('error', $validator->firstError());
            $this->redirect('/teacher/attendance?class_id=' . $classId . '&attendance_date=' . $date);
            return;
        }

        $service = new AttendanceService();
        $result = $service->saveBulkAttendance($classId, $date, $attendanceData, (int)$_SESSION['user_id']);

        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/teacher/attendance?class_id=' . $classId . '&attendance_date=' . $date);
    }

    // ─────────────────────────────────────────────
    //  Examinations (Teacher exam requests)
    // ─────────────────────────────────────────────
    public function examinations(): void
    {
        $teacher = $this->getCurrentTeacher();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $exams = [];
        $teacherClasses = [];
        $teacherSubjects = [];
        if ($session) {
            $examRepo = new ExamRepository();
            $exams = $examRepo->findAllBySession($session['session_id']);
            foreach ($exams as &$exam) {
                $exam['classes'] = $examRepo->getExamClasses($exam['exam_id']);
            }

            // Filter exams to only those that include classes this teacher teaches
            if ($teacher) {
                $myClassData = $this->db->fetchAll(
                    "SELECT DISTINCT cs.class_id, c.class_name, c.section 
                     FROM class_subjects cs
                     JOIN classes c ON cs.class_id = c.class_id 
                     WHERE cs.teacher_id = ? AND cs.session_id = ?",
                    [$teacher['teacher_id'], $session['session_id']]
                );
                $teacherClasses = $myClassData;
                $myClassIds = array_column($myClassData, 'class_id');

                $teacherSubjects = $this->db->fetchAll(
                    "SELECT DISTINCT cs.subject_id, s.subject_name, s.subject_code
                     FROM class_subjects cs
                     JOIN subjects s ON cs.subject_id = s.subject_id
                     WHERE cs.teacher_id = ? AND cs.session_id = ?",
                    [$teacher['teacher_id'], $session['session_id']]
                );

                $subjectClassMap = [];
                $mappingQuery = $this->db->fetchAll(
                    "SELECT subject_id, class_id FROM class_subjects WHERE teacher_id = ? AND session_id = ?",
                    [$teacher['teacher_id'], $session['session_id']]
                );
                foreach ($mappingQuery as $row) {
                    $subjectClassMap[$row['subject_id']][] = $row['class_id'];
                }

                $teacherStats = [];
                if (!empty($exams) && !empty($myClassIds)) {
                    $examIds = array_column($exams, 'exam_id');
                    $resultRepo = new ResultRepository();
                    $teacherStats = $resultRepo->getTeacherExamStats($teacher['teacher_id'], $session['session_id'], $examIds, $myClassIds);
                }

                $filteredExams = [];
                foreach ($exams as $ex) {
                    $filteredClasses = [];
                    foreach ($ex['classes'] as $c) {
                        if (in_array($c['class_id'], $myClassIds)) {
                            
                            $totalSchedules = 0;
                            $enteredSchedules = 0;
                            
                            if (isset($teacherStats[$ex['exam_id']][$c['class_id']])) {
                                $stats = $teacherStats[$ex['exam_id']][$c['class_id']];
                                $totalSchedules = $stats['total'];
                                $enteredSchedules = $stats['entered'];
                            }

                            $c['has_results'] = ($totalSchedules > 0 && $enteredSchedules >= $totalSchedules);
                            $filteredClasses[] = $c;
                        }
                    }
                    if (!empty($filteredClasses)) {
                        $ex['classes'] = $filteredClasses;
                        $filteredExams[] = $ex;
                    }
                }
                $exams = $filteredExams;
            }
        }

        $this->render('Modules/Teacher/Views/portal/examinations', [
            'pageTitle'       => 'Exams',
            'teacher'         => $teacher,
            'exams'           => $exams,
            'session'         => $session,
            'teacherClasses'  => $teacherClasses,
            'teacherSubjects' => $teacherSubjects,
            'subjectClassMap' => $subjectClassMap ?? []
        ], 'teacher');
    }

    public function handleExamAction(): void
    {
        $action = $this->input('action', '');
        
        if ($action === 'create_exam') {
            $this->createExam();
            return;
        }

        $this->flash('info', 'Exam actions are managed by the administrator.');
        $this->redirect(moduleUrl('teacher', 'examinations'));
    }

    private function createExam(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        // Enforce Class Test type for teachers
        $data['exam_type'] = 'Class Test';

        $validator = new \App\Modules\Exams\Validators\ExamValidator();
        if (!$validator->validateExam($data)) {
            $this->flash('error', $validator->firstError());
            $this->redirect(moduleUrl('teacher', 'examinations'));
            return;
        }

        $subjectId = (int)($data['subject_id'] ?? 0);
        if (!$subjectId) {
            $this->flash('error', 'Please select a subject for the class test.');
            $this->redirect(moduleUrl('teacher', 'examinations'));
            return;
        }

        // Security check: Only allow assigning to classes where this teacher teaches the selected subject
        $teacher = $this->getCurrentTeacher();
        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        $assignedClasses = $this->db->fetchAll(
            "SELECT class_id FROM class_subjects WHERE teacher_id = ? AND subject_id = ? AND session_id = ?",
            [$teacher['teacher_id'], $subjectId, $session['session_id']]
        );
        $assignedClassIds = array_column($assignedClasses, 'class_id');
        $validClassIds = [];
        if (!empty($data['class_ids'])) {
            foreach ($data['class_ids'] as $cid) {
                if (in_array((int)$cid, $assignedClassIds)) {
                    $validClassIds[] = (int)$cid;
                }
            }
        }
        
        if (empty($validClassIds)) {
            $this->flash('error', 'You are not assigned to teach this subject in any of the selected classes.');
            $this->redirect(moduleUrl('teacher', 'examinations'));
            return;
        }
        $data['class_ids'] = $validClassIds;

        $service = new \App\Modules\Exams\Services\ExamService();
        $result = $service->createExam($data, (int)$_SESSION['user_id'], $_SESSION['user_type']);

        // Auto-create schedule entries for the selected subject across all assigned classes
        if ($result['success'] && !empty($result['exam_id']) && !empty($data['class_ids'])) {
            $fullMarks = (int)($data['full_marks'] ?? 50);
            $passMarks = (int)($data['pass_marks'] ?? 17);
            foreach ($data['class_ids'] as $classId) {
                try {
                    $service->createSchedule([
                        'exam_id'    => $result['exam_id'],
                        'class_id'   => (int)$classId,
                        'subject_id' => $subjectId,
                        'exam_date'  => $data['start_date'],
                        'start_time' => '09:00:00',
                        'end_time'   => '10:00:00',
                        'full_marks' => $fullMarks,
                        'pass_marks' => $passMarks,
                    ]);
                } catch (\Exception $e) {
                    // Schedule might already exist, skip silently
                }
            }
        }

        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('teacher', 'examinations'));
    }

    // ─────────────────────────────────────────────
    //  Results Entry
    // ─────────────────────────────────────────────
    public function results(): void
    {
        $teacher = $this->getCurrentTeacher();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $scheduleId = (int)$this->input('schedule_id', 0);
        $classId = (int)$this->input('class_id', 0);
        $examId = (int)$this->input('exam_id', 0);

        $schedules = [];
        $students = [];
        $schedule = null;

        // Get exams with classes for this teacher
        $exams = [];
        if ($session && $teacher) {
            $examRepo = new ExamRepository();
            $allExams = $examRepo->findAllBySession($session['session_id']);
            $myClassIds = $this->db->fetchAll(
                "SELECT DISTINCT class_id FROM class_subjects WHERE teacher_id = ? AND session_id = ?",
                [$teacher['teacher_id'], $session['session_id']]
            );
            $myClassIds = array_column($myClassIds, 'class_id');

            foreach ($allExams as $ex) {
                $ex['classes'] = $examRepo->getExamClasses($ex['exam_id']);
                $filteredClasses = [];
                foreach ($ex['classes'] as $c) {
                    if (in_array($c['class_id'], $myClassIds)) {
                        $filteredClasses[] = $c;
                    }
                }
                if (!empty($filteredClasses)) {
                    $ex['classes'] = $filteredClasses;
                    $exams[] = $ex;
                }
            }
        }

        if ($examId && $classId && $session) {
            $scheduleRepo = new ScheduleRepository();
            $schedules = $scheduleRepo->findByExamAndClass($examId, $classId);

            // Filter schedules to only subjects this teacher teaches
            if ($teacher) {
                $mySubjectIds = $this->db->fetchAll(
                    "SELECT DISTINCT subject_id FROM class_subjects WHERE teacher_id = ? AND class_id = ? AND session_id = ?",
                    [$teacher['teacher_id'], $classId, $session['session_id']]
                );
                $mySubjectIds = array_column($mySubjectIds, 'subject_id');
                $schedules = array_filter($schedules, function ($s) use ($mySubjectIds) {
                    return in_array($s['subject_id'], $mySubjectIds);
                });
            }
        }

        if ($scheduleId && $classId && $session) {
            $resultRepo = new ResultRepository();
            $schedule = $resultRepo->getScheduleDetails($scheduleId);
            $students = $resultRepo->getResultsBySchedule($classId, $session['session_id'], $scheduleId);
        }

        $currentExam = null;
        if ($examId) {
            foreach ($exams as $e) {
                if ($e['exam_id'] == $examId) {
                    $currentExam = $e;
                    break;
                }
            }
        }

        $this->render('Modules/Teacher/Views/portal/results', [
            'pageTitle'   => 'Enter Results',
            'teacher'     => $teacher,
            'exams'       => $exams,
            'currentExam' => $currentExam,
            'examId'      => $examId,
            'classId'     => $classId,
            'schedules'   => $schedules,
            'scheduleId'  => $scheduleId,
            'schedule'    => $schedule,
            'students'    => $students,
            'session'     => $session
        ], 'teacher');
    }

    public function saveResults(): void
    {
        $this->validateCsrf();
        $scheduleId = (int)$this->input('schedule_id', 0);
        $classId = (int)$this->input('class_id', 0);
        $examId = (int)$this->input('exam_id', 0);

        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $marks = $_POST['marks'] ?? [];
        $absent = $_POST['absent'] ?? [];
        $remarks = $_POST['remarks'] ?? [];

        if (!$scheduleId || empty($marks)) {
            $this->flash('error', 'No result data to save.');
            $this->redirect(moduleUrl('teacher', 'results'));
            return;
        }

        // Backend validation: reject marks if exam hasn't ended
        if ($examId) {
            $examRepo = new ExamRepository();
            $exam = $examRepo->findById($examId);
            if ($exam) {
                if (date('Y-m-d') < $exam['end_date']) {
                    $this->flash('error', 'Marks can only be entered on or after the exam has ended (' . date('d M Y', strtotime($exam['end_date'])) . ').');
                    $this->redirect('/teacher/results?exam_id=' . $examId . '&class_id=' . $classId);
                    return;
                }
                if (!$exam['is_approved']) {
                    $this->flash('error', 'This exam has not been approved by the administrator yet.');
                    $this->redirect('/teacher/results?exam_id=' . $examId . '&class_id=' . $classId);
                    return;
                }
            }
        }

        // Get schedule details for max marks and subject validation
        $resultRepo = new ResultRepository();
        $schedule = $resultRepo->getScheduleDetails($scheduleId);
        
        if (!$schedule) {
            $this->flash('error', 'Invalid schedule.');
            $this->redirect(moduleUrl('teacher', 'results'));
            return;
        }

        // Security Check: Verify teacher is assigned to this class and subject
        $teacher = $this->getCurrentTeacher();
        if ($teacher) {
            $isAssigned = $this->db->fetch(
                "SELECT 1 FROM class_subjects WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND session_id = ?",
                [$teacher['teacher_id'], $classId, $schedule['subject_id'], $session['session_id']]
            );
            if (!$isAssigned) {
                $this->flash('error', 'You do not have permission to enter marks for this subject in this class.');
                $this->redirect('/teacher/results?exam_id=' . $examId . '&class_id=' . $classId);
                return;
            }
        }

        $maxMarks = $schedule['full_marks'] ?? 100;

        foreach ($marks as $studentId => $marksVal) {
            $isAbsent = isset($absent[$studentId]) ? 1 : 0;
            
            // Skip if teacher left it completely blank and not marked absent (optional subject handling)
            if (trim((string)$marksVal) === '' && !$isAbsent) {
                continue;
            }

            $marksObtained = $isAbsent ? 0 : (float)$marksVal;

            // Calculate grade
            $grade = null;
            if (!$isAbsent && $maxMarks > 0) {
                $pct = ($marksObtained / $maxMarks) * 100;
                if ($pct >= 90) $grade = 'A+';
                elseif ($pct >= 80) $grade = 'A';
                elseif ($pct >= 70) $grade = 'B+';
                elseif ($pct >= 60) $grade = 'B';
                elseif ($pct >= 50) $grade = 'C';
                elseif ($pct >= 40) $grade = 'D';
                else $grade = 'F';
            }

            $resultRepo->upsertResult([
                'student_id'     => (int)$studentId,
                'schedule_id'    => $scheduleId,
                'session_id'     => $session['session_id'],
                'marks_obtained' => $marksObtained,
                'is_absent'      => $isAbsent,
                'grade'          => $grade,
                'remarks'        => $remarks[$studentId] ?? null,
                'entered_by'     => $_SESSION['user_id']
            ]);
        }

        $this->flash('success', 'Results saved successfully.');
        $this->redirect('/teacher/results?exam_id=' . $examId . '&class_id=' . $classId . '&schedule_id=' . $scheduleId);
    }

    // ─────────────────────────────────────────────
    //  Notices (read-only for teachers)
    // ─────────────────────────────────────────────
    public function notices(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        
        $paginated = $this->db->paginate(
            "SELECT * FROM notices WHERE target_audience IN ('All', 'Teachers') ORDER BY created_at DESC",
            [],
            $page,
            $perPage
        );

        $this->render('Modules/Teacher/Views/portal/notices', [
            'pageTitle'  => 'Notices',
            'notices'    => $paginated['data'],
            'page'       => $paginated['current_page'],
            'totalPages' => $paginated['pages']
        ], 'teacher');
    }

    // ─────────────────────────────────────────────
    //  Timetable (read-only for teachers)
    // ─────────────────────────────────────────────
    public function timetable(): void
    {
        $teacher = $this->getCurrentTeacher();
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $timetable = [];
        if ($teacher && $session) {
            $timetable = $this->db->fetchAll(
                "SELECT tt.*, c.class_name, c.section, s.subject_name
                 FROM timetable tt
                 JOIN classes c ON tt.class_id = c.class_id
                 JOIN subjects s ON tt.subject_id = s.subject_id
                 WHERE tt.teacher_id = ? AND tt.session_id = ?
                 ORDER BY FIELD(tt.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), tt.start_time",
                [$teacher['teacher_id'], $session['session_id']]
            );
        }

        $this->render('Modules/Teacher/Views/portal/timetable', [
            'pageTitle'  => 'My Timetable',
            'teacher'    => $teacher,
            'timetable'  => $timetable,
            'session'    => $session
        ], 'teacher');
    }
}
