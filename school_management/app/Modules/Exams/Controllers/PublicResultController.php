<?php
namespace App\Modules\Exams\Controllers;

use Database;
use App\Modules\Academic\Repositories\ClassSubjectRepository;
use App\Modules\Exams\Repositories\ExamRepository;

/**
 * PublicResultController — Check results without logging in
 */
class PublicResultController extends \Controller
{
    public function check(): void
    {
        $db = Database::getInstance();
        
        $exams = $db->fetchAll("SELECT * FROM examinations ORDER BY start_date DESC");
        // Using classes from db directly for simplicity, but better via repo
        $classes = $db->fetchAll("SELECT * FROM classes ORDER BY LENGTH(class_name), class_name");

        $result_data = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $eid = (int)$this->input('exam_id');
            $cid = (int)$this->input('class_id');
            $roll = trim($this->input('roll_number', ''));

            // Check if exam is published (using simple queries for brevity in public check)
            $exam_chk = $db->fetch("SELECT exam_name, session_id FROM examinations WHERE exam_id=? AND is_published=1", [$eid]);
            
            if (!$exam_chk) {
                $error = "Result for this examination is not published yet.";
            } else {
                // Find student by looking up their academic record in the exam's session
                $stu = $db->fetch("
                    SELECT s.student_id, s.first_name, s.last_name 
                    FROM student_academics sa
                    JOIN students s ON sa.student_id = s.student_id
                    WHERE sa.class_id = ? AND sa.roll_number = ? AND sa.session_id = ?
                ", [$cid, $roll, $exam_chk['session_id']]);
                if (!$stu) {
                    $error = "Invalid Class or Roll Number. Student not found.";
                } else {
                    $sid = $stu['student_id'];
                    
                    // Fetch results
                    $sql = "SELECT sub.subject_name, r.marks_obtained, r.is_absent, r.grade, sch.full_marks, sch.pass_marks
                            FROM results r 
                            JOIN exam_schedules sch ON r.schedule_id = sch.schedule_id
                            JOIN subjects sub ON sch.subject_id = sub.subject_id
                            WHERE sch.exam_id = ? AND sch.class_id = ? AND r.student_id = ? 
                            ORDER BY sub.subject_name";
                    $res = $db->fetchAll($sql, [$eid, $cid, $sid]);
                        
                    if (empty($res)) {
                        $error = "No results found for this student in the selected examination.";
                    } else {
                        $result_data = [
                            'student' => $stu,
                            'exam_name' => $exam_chk['exam_name'],
                            'marks' => $res,
                            'class_name' => ''
                        ];
                        // Get class name
                        $classInfo = $db->fetch("SELECT class_name, section FROM classes WHERE class_id=?", [$cid]);
                        if ($classInfo) {
                            $result_data['class_name'] = $classInfo['class_name'] . ' ' . $classInfo['section'];
                        }
                    }
                }
            }
        }

        // We use a blank layout for public routes
        $this->render('Modules/Exams/Views/public_result', [
            'pageTitle' => 'Check Result',
            'exams' => $exams,
            'classes' => $classes,
            'result_data' => $result_data,
            'error' => $error,
            'roll_number' => $this->input('roll_number', '')
        ], 'blank'); // blank layout!
    }
}
