<?php
namespace App\Modules\Timetable\Controllers;

/**
 * TimetableController — Manage class timetables
 */
class TimetableController extends \Controller
{
    public function index(): void
    {
        $classes = $this->db->fetchAll("SELECT * FROM classes ORDER BY LENGTH(class_name), class_name, section");
        $teachers = $this->db->fetchAll("SELECT * FROM teachers ORDER BY first_name, last_name");
        
        $selectedClassId = (int)$this->input('class_id', 0);
        $selectedTeacherId = (int)$this->input('teacher_id', 0);

        $timetable = [];
        $subjects = [];
        
        // Mode 1: Class Timetable
        if ($selectedClassId) {
            $timetable = $this->db->fetchAll(
                "SELECT t.*, s.subject_name 
                 FROM timetable t 
                 LEFT JOIN subjects s ON t.subject_id = s.subject_id 
                 WHERE t.class_id = ? 
                 ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time",
                [$selectedClassId]
            );
            
            $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
            $session = $academicRepo->getActiveSession();
            if ($session) {
                $subjects = $this->db->fetchAll(
                    "SELECT DISTINCT cs.subject_id, s.subject_name 
                     FROM class_subjects cs 
                     JOIN subjects s ON cs.subject_id = s.subject_id 
                     WHERE cs.class_id = ? AND cs.session_id = ?",
                    [$selectedClassId, $session['session_id']]
                );
            }
        }

        // Mode 2: Teacher Timetable
        $teacherTimetable = [];
        if ($selectedTeacherId) {
            $teacherTimetable = $this->db->fetchAll(
                "SELECT t.*, s.subject_name, c.class_name, c.section
                 FROM timetable t 
                 LEFT JOIN subjects s ON t.subject_id = s.subject_id 
                 LEFT JOIN classes c ON t.class_id = c.class_id
                 WHERE t.teacher_id = ? AND t.is_break = 0
                 ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time",
                [$selectedTeacherId]
            );
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $this->render('Modules/Timetable/Views/index', [
            'pageTitle'         => 'Class Timetable',
            'classes'           => $classes,
            'teachers'          => $teachers,
            'selectedClassId'   => $selectedClassId,
            'selectedTeacherId' => $selectedTeacherId,
            'timetable'         => $timetable,
            'teacherTimetable'  => $teacherTimetable,
            'subjects'          => $subjects,
            'days'              => $days,
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');
        $this->validateCsrf();

        if ($action === 'download_template') {
            $this->downloadTemplate();
        } elseif ($action === 'import_csv') {
            $this->importCsv();
        } elseif ($action === 'clone_previous') {
            $this->clonePrevious();
        }

        $this->redirect(moduleUrl('admin', 'timetable'));
    }

    private function clonePrevious(): void
    {
        $classId = (int)$this->input('class_id', 0);
        if (!$classId) {
            $this->flash('error', 'Invalid class selected.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $currentSession = $academicRepo->getActiveSession();
        if (!$currentSession) {
            $this->flash('error', 'No active session found.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        // Find the previous session (the one just before the current session based on start_date)
        $previousSession = $this->db->fetch(
            "SELECT session_id FROM academic_sessions WHERE start_date < ? ORDER BY start_date DESC LIMIT 1",
            [$currentSession['start_date']]
        );

        if (!$previousSession) {
            $this->flash('error', 'No previous academic session found to clone from.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        // Fetch previous timetable records
        $prevRecords = $this->db->fetchAll(
            "SELECT * FROM timetable WHERE class_id = ? AND session_id = ?",
            [$classId, $previousSession['session_id']]
        );

        if (empty($prevRecords)) {
            $this->flash('error', 'No timetable found in the previous session for this class.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        // Validate teacher/subject existence for cloning
        $missingTeachers = [];
        $missingSubjects = [];
        foreach ($prevRecords as $r) {
            if (!$r['is_break']) {
                if ($r['teacher_id']) {
                    $t = $this->db->fetch("SELECT teacher_id FROM teachers WHERE teacher_id=?", [$r['teacher_id']]);
                    if (!$t) {
                        $missingTeachers[] = $r['teacher_id'];
                    }
                }
                if ($r['subject_id']) {
                    $s = $this->db->fetch("SELECT subject_id FROM subjects WHERE subject_id=?", [$r['subject_id']]);
                    if (!$s) {
                        $missingSubjects[] = $r['subject_id'];
                    }
                }
            }
        }

        if (!empty($missingTeachers) || !empty($missingSubjects)) {
            $this->flash('error', 'Cannot clone: Some teachers or subjects from the previous session no longer exist in the system.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        // Proceed to clone
        try {
            $this->db->transaction(function() use ($classId, $currentSession, $prevRecords) {
                // Delete existing timetable for this class in current session
                $this->db->delete('timetable', 'class_id = ? AND session_id = ?', [$classId, $currentSession['session_id']]);
                
                // Insert cloned records
                foreach ($prevRecords as $row) {
                    unset($row['timetable_id']); // Remove primary key so it auto-increments
                    $row['session_id'] = $currentSession['session_id']; // Update session ID
                    $this->db->insert('timetable', $row);
                }
            });
            $this->flash('success', "Timetable successfully cloned from the previous session (" . count($prevRecords) . " entries).");
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '1062 Duplicate entry') !== false && strpos($msg, 'timetable.unique_slot_teacher') !== false) {
                $this->flash('error', "<strong>Teacher Double-Booking!</strong> A cloned teacher is scheduled for a class at the same time as an existing class in this session.");
            } else {
                $this->flash('error', "Failed to clone timetable: " . $msg);
            }
        }

        $this->redirect('/admin/timetable?class_id=' . $classId);
    }

    private function downloadTemplate(): void
    {
        $classId = (int)$this->input('class_id', 0);
        
        $filename = "timetable_template.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if ($classId) {
            $classRecord = $this->db->fetch("SELECT class_name, section FROM classes WHERE class_id = ?", [$classId]);
            if ($classRecord) {
                fputcsv($output, ['# Class: ' . $classRecord['class_name'] . ' ' . $classRecord['section']]);
            }
            
            $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
            $session = $academicRepo->getActiveSession();
            $sessionId = $session ? $session['session_id'] : 0;
            
            $subjects = $this->db->fetchAll(
                "SELECT s.subject_code, s.subject_name, t.first_name, t.last_name 
                 FROM class_subjects cs 
                 JOIN subjects s ON cs.subject_id = s.subject_id 
                 LEFT JOIN teachers t ON cs.teacher_id = t.teacher_id
                 WHERE cs.class_id = ? AND cs.session_id = ?",
                [$classId, $sessionId]
            );
            
            fputcsv($output, ['# INSTRUCTIONS']);
            fputcsv($output, ['# IMPORTANT: Do NOT delete the "# Class:" row above. It is required for security verification.']);
            fputcsv($output, ['# Structure: Times as rows, Days as columns.']);
            fputcsv($output, ['# Time Format: HH:MM AM/PM - HH:MM AM/PM (e.g., 09:00 AM - 10:00 AM)']);
            fputcsv($output, ['# Use "BREAK" in cells for breaks. Leave cells empty if there is no class.']);
            
            if (empty($subjects)) {
                fputcsv($output, ['# Note: No subjects are currently assigned to this class.']);
            } else {
                fputcsv($output, ['# VALID SUBJECT CODES FOR THIS CLASS:']);
                foreach ($subjects as $s) {
                    $teacherName = $s['first_name'] ? trim($s['first_name'] . ' ' . $s['last_name']) : 'Unassigned';
                    fputcsv($output, ['# ' . strtoupper(trim($s['subject_code'])) . ' (' . $s['subject_name'] . ') - Teacher: ' . $teacherName]);
                }
            }
            fputcsv($output, []); // Empty row for spacing
        }
        
        fputcsv($output, ['# --- EXAMPLE DATA BELOW (Replace with your actual timetable) ---']);
        fputcsv($output, ['Time', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
        
        // Example rows
        fputcsv($output, ['09:00 AM - 10:00 AM', 'MATH101', 'ENG101', 'SCI101', 'MATH101', 'ENG101', 'SCI101', 'HOLIDAY']);
        fputcsv($output, ['10:00 AM - 10:15 AM', 'BREAK', 'BREAK', 'BREAK', 'BREAK', 'BREAK', 'BREAK', 'HOLIDAY']);
        
        fclose($output);
        exit;
    }

    private function importCsv(): void
    {
        $this->validateCsrf();
        $classId = (int)$this->input('class_id', 0);
        
        if (!$classId || empty($_FILES['csv_file']['tmp_name'])) {
            $this->flash('error', 'Please select a class and upload a valid CSV file.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }
        
        // --- Backend Validation & Security ---
        $fileInfo = pathinfo($_FILES['csv_file']['name']);
        if (!isset($fileInfo['extension']) || strtolower($fileInfo['extension']) !== 'csv') {
            $this->flash('error', 'Security Error: Only .csv files are allowed.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        if ($_FILES['csv_file']['size'] > 2 * 1024 * 1024) {
            $this->flash('error', 'File size exceeds the 2MB limit.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }
        
        $mimeType = mime_content_type($_FILES['csv_file']['tmp_name']);
        $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values', 'application/excel', 'application/vnd.ms-excel', 'application/vnd.msexcel'];
        if (!in_array($mimeType, $allowedMimes)) {
            $this->flash('error', 'Security Error: Invalid file format detected.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }
        // -------------------------------------

        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        if (!$session) {
            $this->flash('error', 'No active session.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }
        $sessionId = $session['session_id'];

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if ($handle === false) {
            $this->flash('error', 'Could not read the uploaded file.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        // Get Class Name for verification
        $classRecord = $this->db->fetch("SELECT class_name, section FROM classes WHERE class_id = ?", [$classId]);
        $expectedClassName = $classRecord ? ($classRecord['class_name'] . ' ' . $classRecord['section']) : '';

        // Get allowed subjects for this class
        $allowedSubjects = $this->db->fetchAll(
            "SELECT cs.subject_id, s.subject_code, cs.teacher_id 
             FROM class_subjects cs 
             JOIN subjects s ON cs.subject_id = s.subject_id 
             WHERE cs.class_id = ? AND cs.session_id = ?",
            [$classId, $sessionId]
        );
        $subjectMap = [];
        foreach ($allowedSubjects as $sub) {
            $subjectMap[strtoupper(trim($sub['subject_code']))] = [
                'subject_id' => $sub['subject_id'],
                'teacher_id' => $sub['teacher_id']
            ];
        }

        // Parse CSV
        $validRows = [];
        $timeSlots = []; // Track time ranges for overlap validation
        $rowNum = 0;
        $classMatched = false;
        $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty($data) || empty(trim($data[0]))) continue;
            
            $cellZero = htmlspecialchars(strip_tags(trim($data[0]))); // Sanitize input
            
            // Check for class validation header
            if (strpos($cellZero, '# Class:') === 0) {
                $csvClass = trim(str_replace('# Class:', '', $cellZero));
                if ($csvClass !== $expectedClassName) {
                    $this->flash('error', "Security Error: The uploaded CSV does not match the selected class. Expected: $expectedClassName, but found: $csvClass");
                    fclose($handle);
                    $this->redirect('/admin/timetable?class_id=' . $classId);
                    return;
                }
                $classMatched = true;
                continue;
            }
            
            // Skip comments and instructions
            if (strpos($cellZero, '#') === 0) continue;
            
            // Skip the header row
            if (strtolower($cellZero) === 'time') continue;
            
            // Parse time range, e.g., "09:00-10:00"
            $timeRange = explode('-', $cellZero);
            if (count($timeRange) !== 2) continue; // Invalid time range
            
            // strtotime parses AM/PM seamlessly and date() converts it to 24-hour format
            $startTime = date('H:i:s', strtotime(trim($timeRange[0])));
            $endTime = date('H:i:s', strtotime(trim($timeRange[1])));

            if ($startTime >= $endTime) {
                $this->flash('error', "Start time must be before end time on row $rowNum.");
                fclose($handle);
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }
            
            // Overlap validation: check against previously parsed rows
            foreach ($timeSlots as $slot) {
                // Two time ranges A and B overlap if (StartA < EndB) AND (EndA > StartB)
                if ($startTime < $slot['end'] && $endTime > $slot['start']) {
                    $this->flash('error', "Time overlap detected on row $rowNum (" . trim($data[0]) . "). It overlaps with a previous row.");
                    fclose($handle);
                    $this->redirect('/admin/timetable?class_id=' . $classId);
                    return;
                }
            }
            $timeSlots[] = ['start' => $startTime, 'end' => $endTime];

            // Extract subjects for each day
            foreach ($validDays as $index => $day) {
                $colIndex = $index + 1; // Days start from column index 1
                if (!isset($data[$colIndex])) continue;
                
                $subjectCode = htmlspecialchars(strip_tags(strtoupper(trim($data[$colIndex])))); // Sanitize input
                if (empty($subjectCode)) continue; // Empty cell means no class
                
                $isBreak = 0;
                $breakName = null;
                $subjectId = null;
                $teacherId = null;

                if ($subjectCode === 'BREAK' || $subjectCode === 'HOLIDAY') {
                    if ($day === 'Sunday' || $subjectCode === 'HOLIDAY') {
                        $isBreak = 1;
                        $breakName = 'Holiday';
                    } else {
                        $isBreak = 1;
                        $breakName = 'Break';
                    }
                } else {
                    if (!isset($subjectMap[$subjectCode])) {
                        $this->flash('error', "Subject code '$subjectCode' on row $rowNum ($day) is not assigned to this class.");
                        fclose($handle);
                        $this->redirect('/admin/timetable?class_id=' . $classId);
                        return;
                    }
                    $subjectId = $subjectMap[$subjectCode]['subject_id'];
                    $teacherId = $subjectMap[$subjectCode]['teacher_id'];
                }

                $validRows[] = [
                    'class_id'     => $classId,
                    'day_of_week'  => $day,
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'is_break'     => $isBreak,
                    'break_name'   => $breakName,
                    'subject_id'   => $subjectId,
                    'teacher_id'   => $teacherId,
                    'session_id'   => $sessionId
                ];
            }
        }
        fclose($handle);

        if (!$classMatched) {
            $this->flash('error', 'Security Error: The uploaded CSV is missing the required "# Class: [Name]" header. Please use the exact template provided.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        if (empty($validRows)) {
            $this->flash('error', "No valid data found in CSV.");
            $this->redirect('/admin/timetable?class_id=' . $classId);
            return;
        }

        // Full Replacement
        try {
            $this->db->transaction(function() use ($classId, $validRows) {
                $this->db->delete('timetable', 'class_id = ?', [$classId]);
                foreach ($validRows as $row) {
                    $this->db->insert('timetable', $row);
                }
            });
            $this->flash('success', "Timetable imported successfully. " . count($validRows) . " records added.");
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '1062 Duplicate entry') !== false && strpos($msg, 'timetable.unique_slot_teacher') !== false) {
                preg_match("/Duplicate entry '(.*?)' for key/", $msg, $matches);
                if (!empty($matches[1])) {
                    $parts = explode('-', $matches[1]);
                    if (count($parts) >= 4) {
                        $teacherId = $parts[1];
                        $day = $parts[2];
                        $time = date('h:i A', strtotime($parts[3]));
                        
                        $teacher = $this->db->fetch("SELECT first_name, last_name FROM teachers WHERE teacher_id = ?", [$teacherId]);
                        $tName = $teacher ? htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) : "Teacher #$teacherId";
                        
                        $friendlyMsg = "<strong>Teacher Double-Booking Detected!</strong><br>" .
                                       "You assigned <strong>$tName</strong> to a class on <strong>$day</strong> at <strong>$time</strong>, but they are already scheduled to teach a different class at that exact time.<br><br>" .
                                       "<strong>How to fix:</strong><br>" .
                                       "1. Check <strong>$tName's</strong> schedule across other classes.<br>" .
                                       "2. Change the subject/time in your CSV so they don't overlap.<br>" .
                                       "3. Upload the fixed CSV again.";
                        $this->flash('error', $friendlyMsg);
                    } else {
                        $this->flash('error', "<strong>Teacher Double-Booking!</strong> A teacher is scheduled for two classes at the same time. Please fix the schedule overlaps in your CSV.");
                    }
                } else {
                    $this->flash('error', "<strong>Teacher Double-Booking!</strong> A teacher is scheduled for two classes at the same time. Please fix the schedule overlaps in your CSV.");
                }
            } else {
                $this->flash('error', "Failed to import timetable: " . $msg);
            }
        }

        $this->redirect('/admin/timetable?class_id=' . $classId);
    }
}
