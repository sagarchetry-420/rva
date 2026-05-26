<?php
namespace App\Modules\Student\Controllers;

/**
 * PromotionController — Manage student class promotions
 */
class PromotionController extends \Controller
{
    public function index(): void
    {
        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        $classes = $this->db->fetchAll("SELECT * FROM classes ORDER BY class_name, section");

        $students = [];
        $selectedClassId = (int)$this->input('class_id', 0);
        
        if ($selectedClassId && $session) {
            $students = $this->db->fetchAll(
                "SELECT s.*, sa.roll_number, c.class_name, c.section, sa.promotion_status 
                 FROM student_academics sa
                 JOIN students s ON sa.student_id = s.student_id
                 JOIN classes c ON sa.class_id = c.class_id 
                 WHERE sa.class_id = ? AND sa.session_id = ? AND sa.admission_status = 'Active'
                 ORDER BY sa.roll_number",
                [$selectedClassId, $session['session_id']]
            );

            // Determine if each student has passed the Final exam
            foreach ($students as &$student) {
                if ($student['promotion_status'] === 'Promoted') {
                    $student['final_status'] = 'Already Promoted';
                    $student['can_promote'] = false;
                    continue;
                }

                $finalExamsCount = $this->db->fetch("
                    SELECT COUNT(*) as cnt 
                    FROM results r
                    JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                    JOIN examinations e ON es.exam_id = e.exam_id
                    WHERE r.student_id = ? AND r.session_id = ? AND e.exam_type = 'Final'
                ", [$student['student_id'], $session['session_id']]);
                
                if ($finalExamsCount['cnt'] == 0) {
                    $student['final_status'] = 'No Final Exam';
                    $student['can_promote'] = false;
                } else {
                    $failedCount = $this->db->fetch("
                        SELECT COUNT(*) as cnt 
                        FROM results r
                        JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                        JOIN examinations e ON es.exam_id = e.exam_id
                        WHERE r.student_id = ? AND r.session_id = ? AND e.exam_type = 'Final'
                          AND (r.grade = 'F' OR r.is_absent = 1 OR r.marks_obtained < es.pass_marks)
                    ", [$student['student_id'], $session['session_id']]);
                    
                    if ($failedCount['cnt'] > 0) {
                        $student['final_status'] = 'Failed';
                        $student['can_promote'] = true; // Can be processed for Detention
                    } else {
                        $student['final_status'] = 'Passed';
                        $student['can_promote'] = true;
                    }
                }
            }
        }

        $this->render('Modules/Student/Views/promotions', [
            'pageTitle'       => 'Student Promotions',
            'session'         => $session,
            'classes'         => $classes,
            'students'        => $students,
            'selectedClassId' => $selectedClassId,
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');

        if ($action === 'promote') {
            $this->promoteStudents();
            return;
        } elseif ($action === 'pass_out') {
            $this->passOutStudents();
            return;
        }

        $this->flash('info', 'Unknown action.');
        $this->redirect(moduleUrl('admin', 'promotions'));
    }

    private function promoteStudents(): void
    {
        $this->validateCsrf();
        $studentIds = $_POST['student_ids'] ?? [];
        $targetClassId = (int)$this->input('target_class_id', 0);
        $sourceClassId = (int)$this->input('source_class_id', 0);

        if (empty($studentIds) || !$targetClassId) {
            $this->flash('error', 'Please select students and a target class.');
            $this->redirect('/admin/promotions?class_id=' . $sourceClassId);
            return;
        }

        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $nextSession = $this->db->fetch("SELECT * FROM academic_sessions WHERE is_current = 0 ORDER BY start_date DESC LIMIT 1");
        $currentSession = $academicRepo->getActiveSession();

        if (!$nextSession) {
            $this->flash('error', 'No next academic session found. Please create a new session before promoting students.');
            $this->redirect('/admin/promotions?class_id=' . $sourceClassId);
            return;
        }

        $promoted = 0;
        foreach ($studentIds as $studentId) {
            // Check pass status
            $failedCount = $this->db->fetch("
                SELECT COUNT(*) as cnt 
                FROM results r
                JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                JOIN examinations e ON es.exam_id = e.exam_id
                WHERE r.student_id = ? AND r.session_id = ? AND e.exam_type = 'Final'
                  AND (r.grade = 'F' OR r.is_absent = 1 OR r.marks_obtained < es.pass_marks)
            ", [(int)$studentId, $currentSession['session_id']]);
            
            $finalExamsCount = $this->db->fetch("
                SELECT COUNT(*) as cnt 
                FROM results r
                JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                JOIN examinations e ON es.exam_id = e.exam_id
                WHERE r.student_id = ? AND r.session_id = ? AND e.exam_type = 'Final'
            ", [(int)$studentId, $currentSession['session_id']]);
            
            if ($finalExamsCount['cnt'] == 0) {
                continue; // Cannot process if they haven't passed the final exam
            }
            
            $isPassed = ($failedCount['cnt'] == 0);
            $newStatus = $isPassed ? 'Promoted' : 'Detained';
            $enrollClassId = $isPassed ? $targetClassId : $sourceClassId;

            $sa = $this->db->fetch("SELECT * FROM student_academics WHERE student_id = ? AND session_id = ?", [(int)$studentId, $currentSession['session_id']]);
            if ($sa) {
                // Update current session status
                $this->db->update('student_academics', [
                    'promotion_status' => $newStatus
                ], 'academic_id = ?', [$sa['academic_id']]);
                
                // Check if already processed for the next session
                $nextSa = $this->db->fetch("SELECT * FROM student_academics WHERE student_id = ? AND session_id = ?", [(int)$studentId, $nextSession['session_id']]);
                
                // Determine roll number for the next session
                if ($nextSa && $nextSa['class_id'] == $enrollClassId && !empty($nextSa['roll_number'])) {
                    // Keep existing roll number if class is the same
                    $newRollNumber = $nextSa['roll_number'];
                } else {
                    // Generate new roll number for the enroll class
                    $count = $this->db->count('student_academics', 'class_id = ? AND session_id = ?', [$enrollClassId, $nextSession['session_id']]) + 1;
                    $classData = $this->db->fetch("SELECT section FROM classes WHERE class_id = ?", [$enrollClassId]);
                    $section = $classData['section'] ?: 'A';
                    $year = date('Y', strtotime($nextSession['start_date']));
                    $newRollNumber = $year . '-' . $section . '-' . str_pad($count, 2, '0', STR_PAD_LEFT);
                }
                
                if ($nextSa) {
                    // Update existing record
                    $this->db->update('student_academics', [
                        'class_id'   => $enrollClassId,
                        'roll_number' => $newRollNumber,
                        'admission_status' => 'Active',
                        'promotion_status' => 'Pending',
                        'previous_session_id' => $currentSession['session_id']
                    ], 'academic_id = ?', [$nextSa['academic_id']]);
                } else {
                    // Create new record for the next session
                    $this->db->insert('student_academics', [
                        'student_id' => (int)$studentId,
                        'session_id' => $nextSession['session_id'],
                        'class_id'   => $enrollClassId,
                        'roll_number' => $newRollNumber,
                        'admission_status' => 'Active',
                        'promotion_status' => 'Pending',
                        'previous_session_id' => $currentSession['session_id']
                    ]);
                }
                
                // Automatically generate Admission Fee invoice for the new session if they passed
                if ($isPassed) {
                    $classInfo = $this->db->fetch("SELECT admission_fee FROM classes WHERE class_id = ?", [(int)$enrollClassId]);
                    if ($classInfo && $classInfo['admission_fee'] > 0) {
                        $category = $this->db->fetch("SELECT category_id FROM fee_categories WHERE category_name = 'Admission Fee'");
                        $categoryId = $category ? $category['category_id'] : 1; // Fallback to 1

                        // Check if the fee already exists for this student in the new session to prevent duplicates
                        $existingFee = $this->db->fetch("SELECT fee_id FROM fees WHERE student_id = ? AND session_id = ? AND category_id = ?", [(int)$studentId, $nextSession['session_id'], $categoryId]);
                        
                        if (!$existingFee) {
                            $dueDate = date('Y-m-d', strtotime('+30 days'));
                            $adminUserId = $_SESSION['user_id'] ?? 1; // Fallback to 1 if not set
                            $this->db->insert('fees', [
                                'student_id'     => (int)$studentId,
                                'session_id'     => $nextSession['session_id'],
                                'category_id'    => $categoryId,
                                'amount'         => $classInfo['admission_fee'],
                                'due_date'       => $dueDate,
                                'payment_status' => 'Pending',
                                'created_by'     => $adminUserId,
                            ]);
                        }
                    }
                }
                
                // Update the student's current class, session, and roll number for global lookups
                $this->db->update('students', [
                    'current_class_id'   => $enrollClassId,
                    'current_session_id' => $nextSession['session_id'],
                    'roll_number'        => $newRollNumber
                ], 'student_id = ?', [(int)$studentId]);

                $promoted++;
            }
        }

        $this->flash('success', "{$promoted} student(s) promoted successfully.");
        $this->redirect('/admin/promotions?class_id=' . $sourceClassId);
    }
    private function passOutStudents(): void
    {
        $this->validateCsrf();
        $studentIds = $_POST['student_ids'] ?? [];
        $sourceClassId = (int)$this->input('source_class_id', 0);

        if (empty($studentIds)) {
            $this->flash('error', 'Please select students to pass out.');
            $this->redirect('/admin/promotions?class_id=' . $sourceClassId);
            return;
        }

        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $currentSession = $academicRepo->getActiveSession();

        if (!$currentSession) {
            $this->flash('error', 'No active academic session found.');
            $this->redirect('/admin/promotions?class_id=' . $sourceClassId);
            return;
        }

        $passedOut = 0;
        foreach ($studentIds as $studentId) {
            // Check pass status
            $failedCount = $this->db->fetch("
                SELECT COUNT(*) as cnt 
                FROM results r
                JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                JOIN examinations e ON es.exam_id = e.exam_id
                WHERE r.student_id = ? AND r.session_id = ? AND e.exam_type = 'Final'
                  AND (r.grade = 'F' OR r.is_absent = 1 OR r.marks_obtained < es.pass_marks)
            ", [(int)$studentId, $currentSession['session_id']]);
            
            $finalExamsCount = $this->db->fetch("
                SELECT COUNT(*) as cnt 
                FROM results r
                JOIN exam_schedules es ON r.schedule_id = es.schedule_id
                JOIN examinations e ON es.exam_id = e.exam_id
                WHERE r.student_id = ? AND r.session_id = ? AND e.exam_type = 'Final'
            ", [(int)$studentId, $currentSession['session_id']]);
            
            if ($finalExamsCount['cnt'] == 0 || $failedCount['cnt'] > 0) {
                continue; // Cannot pass out if they haven't passed the final exam
            }

            $sa = $this->db->fetch("SELECT * FROM student_academics WHERE student_id = ? AND session_id = ?", [(int)$studentId, $currentSession['session_id']]);
            if ($sa) {
                // Update current session status
                $this->db->update('student_academics', [
                    'promotion_status' => 'Passed Out',
                    'admission_status' => 'Passed Out'
                ], 'academic_id = ?', [$sa['academic_id']]);
                
                // Update student leaving info
                $this->db->update('students', [
                    'leaving_date' => date('Y-m-d'),
                    'leaving_reason' => 'Passed Out'
                ], 'student_id = ?', [(int)$studentId]);

                // Disable login
                $stu = $this->db->fetch("SELECT user_id FROM students WHERE student_id = ?", [(int)$studentId]);
                if ($stu && $stu['user_id']) {
                    $this->db->update('users', ['is_active' => 0], 'user_id = ?', [$stu['user_id']]);
                }

                $passedOut++;
            }
        }

        $this->flash('success', "{$passedOut} student(s) passed out successfully.");
        $this->redirect('/admin/promotions?class_id=' . $sourceClassId);
    }
}
