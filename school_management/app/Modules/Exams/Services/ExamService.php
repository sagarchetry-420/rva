<?php
namespace App\Modules\Exams\Services;

use Database;
use App\Modules\Exams\Repositories\ExamRepository;
use App\Modules\Exams\Repositories\ScheduleRepository;
use App\Modules\Exams\Repositories\ResultRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;

/**
 * ExamService — Business logic for exams, scheduling, and grading
 */
class ExamService
{
    private ExamRepository $examRepo;
    private ScheduleRepository $scheduleRepo;
    private ResultRepository $resultRepo;
    private ClassSubjectRepository $academicRepo;

    public function __construct()
    {
        $this->examRepo = new ExamRepository();
        $this->scheduleRepo = new ScheduleRepository();
        $this->resultRepo = new ResultRepository();
        $this->academicRepo = new ClassSubjectRepository();
    }

    public function createExam(array $data, int $userId, string $userRole): array
    {
        $session = $this->academicRepo->getActiveSession();
        if (!$session) {
            return ['success' => false, 'message' => 'No active academic session found.'];
        }

        // Check for duplicates
        $duplicate = $this->examRepo->findDuplicateExam(
            $session['session_id'], 
            $data['exam_type'], 
            $data['start_date'], 
            $data['end_date']
        );
        
        if ($duplicate) {
            if (in_array($data['exam_type'], ['Mid-Term', 'Final'])) {
                return ['success' => false, 'message' => "A {$data['exam_type']} exam already exists for this academic session."];
            } else {
                return ['success' => false, 'message' => "A {$data['exam_type']} already exists during these dates (overlaps with '{$duplicate['exam_name']}')."];
            }
        }

        $examId = $this->examRepo->create([
            'exam_name'      => $data['exam_name'],
            'exam_type'      => $data['exam_type'],
            'session_id'     => $session['session_id'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'created_by'     => $userId,
            'created_by_role'=> $userRole,
            'is_approved'    => ($userRole === 'admin') ? 1 : 0,
            'approved_by'    => ($userRole === 'admin') ? $userId : null,
            'approved_at'    => ($userRole === 'admin') ? date('Y-m-d H:i:s') : null,
        ]);

        if (!empty($data['class_ids'])) {
            foreach ($data['class_ids'] as $classId) {
                $this->examRepo->assignClass($examId, (int)$classId);
            }
        }

        return ['success' => true, 'message' => 'Exam created successfully.', 'exam_id' => $examId];
    }

    public function approveExam(int $examId, int $adminId): array
    {
        $data = [
            'is_approved' => 1,
            'approved_by' => $adminId,
            'approved_at' => date('Y-m-d H:i:s')
        ];
        if ($this->examRepo->update($examId, $data)) {
            return ['success' => true, 'message' => 'Exam approved successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to approve exam.'];
    }

    public function createSchedule(array $data): array
    {
        if ($this->scheduleRepo->exists($data['exam_id'], $data['class_id'], $data['subject_id'])) {
            return ['success' => false, 'message' => 'A schedule for this subject already exists for this class and exam.'];
        }

        $this->scheduleRepo->create([
            'exam_id'     => $data['exam_id'],
            'class_id'    => $data['class_id'],
            'subject_id'  => $data['subject_id'],
            'exam_date'   => $data['exam_date'],
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'full_marks'  => $data['full_marks'] ?? 100,
            'pass_marks'  => $data['pass_marks'] ?? 35,
            'room_number' => $data['room_number'] ?? null
        ]);

        return ['success' => true, 'message' => 'Schedule added successfully.'];
    }

    public function saveBulkResults(int $scheduleId, array $resultsData, int $userId): array
    {
        $session = $this->academicRepo->getActiveSession();
        if (!$session) {
            return ['success' => false, 'message' => 'No active academic session found.'];
        }

        $schedule = $this->resultRepo->getScheduleDetails($scheduleId);
        if (!$schedule) {
            return ['success' => false, 'message' => 'Invalid schedule.'];
        }

        $fullMarks = (float)$schedule['full_marks'];

        try {
            Database::getInstance()->transaction(function() use ($scheduleId, $resultsData, $userId, $session, $fullMarks) {
                foreach ($resultsData as $studentId => $data) {
                    $isAbsent = isset($data['is_absent']) && $data['is_absent'] == '1';
                    $marks = $isAbsent ? null : (isset($data['marks_obtained']) && $data['marks_obtained'] !== '' ? (float)$data['marks_obtained'] : null);
                    
                    // Validate marks range
                    if ($marks !== null && ($marks < 0 || $marks > $fullMarks)) {
                        throw new \Exception("Marks for student ID $studentId must be between 0 and $fullMarks.");
                    }

                    // Compute Grade (Simple logic for demonstration)
                    $grade = null;
                    if ($marks !== null) {
                        $pct = ($marks / $fullMarks) * 100;
                        if ($pct >= 90) $grade = 'A+';
                        elseif ($pct >= 80) $grade = 'A';
                        elseif ($pct >= 70) $grade = 'B';
                        elseif ($pct >= 60) $grade = 'C';
                        elseif ($pct >= 50) $grade = 'D';
                        elseif ($pct >= 35) $grade = 'E';
                        else $grade = 'F';
                    }

                    $this->resultRepo->upsertResult([
                        'student_id'     => $studentId,
                        'schedule_id'    => $scheduleId,
                        'session_id'     => $session['session_id'],
                        'marks_obtained' => $marks,
                        'is_absent'      => $isAbsent ? 1 : 0,
                        'grade'          => $grade,
                        'remarks'        => $data['remarks'] ?? null,
                        'entered_by'     => $userId
                    ]);
                }
            });
            return ['success' => true, 'message' => 'Results saved successfully.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
