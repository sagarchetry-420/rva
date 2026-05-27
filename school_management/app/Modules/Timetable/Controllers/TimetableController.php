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
        $selectedClassId = (int)$this->input('class_id', 0);

        $timetable = [];
        $subjects = [];
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

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $this->render('Modules/Timetable/Views/index', [
            'pageTitle'       => 'Class Timetable',
            'classes'         => $classes,
            'selectedClassId' => $selectedClassId,
            'timetable'       => $timetable,
            'subjects'        => $subjects,
            'days'            => $days,
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');
        $this->validateCsrf();

        if ($action === 'add_column') {
            $classId = (int)$this->input('class_id', 0);
            $slotType = $this->input('slot_type', 'subject'); // 'subject' or 'break'
            $startTime = $this->input('start_time', '');
            $endTime = $this->input('end_time', '');
            $breakName = $slotType === 'break' ? $this->input('break_name', '') : null;

            if (!$classId || !$startTime || !$endTime) {
                $this->flash('error', 'Common fields are required.');
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }

            $start = strtotime($startTime);
            $end = strtotime($endTime);
            
            if ($start >= $end) {
                $this->flash('error', 'End Time must be after Start Time.');
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }
            
            if (($end - $start) < 45 * 60 && $slotType === 'subject') {
                $this->flash('error', 'One period must be at least 45 minutes.');
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }

            if ($slotType === 'break' && empty($breakName)) {
                $this->flash('error', 'Break name is required for a break slot.');
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }

            // Check for overlapping time periods
            $existingSlots = $this->db->fetchAll(
                "SELECT DISTINCT start_time, end_time FROM timetable WHERE class_id = ?",
                [$classId]
            );

            foreach ($existingSlots as $slot) {
                $eStart = strtotime($slot['start_time']);
                $eEnd = strtotime($slot['end_time']);

                if ($start < $eEnd && $end > $eStart) {
                    $this->flash('error', 'The selected time period overlaps with an existing column (' . date('h:i A', $eStart) . ' - ' . date('h:i A', $eEnd) . '). Please choose a valid time.');
                    $this->redirect('/admin/timetable?class_id=' . $classId);
                    return;
                }
            }

            $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
            $session = $academicRepo->getActiveSession();
            $sessionId = $session ? $session['session_id'] : null;

            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            foreach ($days as $day) {
                $data = [
                    'class_id'     => $classId,
                    'day_of_week'  => $day,
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'is_break'     => $slotType === 'break' ? 1 : 0,
                    'break_name'   => $breakName,
                    'subject_id'   => null,
                    'teacher_id'   => null,
                    'session_id'   => $sessionId,
                ];
                $this->db->insert('timetable', $data);
            }

            $this->flash('success', 'Time column added successfully.');
            $this->redirect('/admin/timetable?class_id=' . $classId);

        } elseif ($action === 'delete_column') {
            $classId = (int)$this->input('class_id', 0);
            $startTime = $this->input('start_time', '');
            $endTime = $this->input('end_time', '');
            
            $this->db->delete('timetable', 'class_id = ? AND start_time = ? AND end_time = ?', [$classId, $startTime, $endTime]);
            $this->flash('success', 'Time column removed.');
            $this->redirect('/admin/timetable?class_id=' . $classId);

        } elseif ($action === 'edit_column') {
            $classId = (int)$this->input('class_id', 0);
            $oldStartTime = $this->input('old_start_time', '');
            $oldEndTime = $this->input('old_end_time', '');
            
            $newStartTime = $this->input('start_time', '');
            $newEndTime = $this->input('end_time', '');
            $breakName = $this->input('break_name', ''); // This might be empty for subject periods

            if (!$classId || !$newStartTime || !$newEndTime || !$oldStartTime || !$oldEndTime) {
                $this->flash('error', 'Common fields are required.');
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }

            $start = strtotime($newStartTime);
            $end = strtotime($newEndTime);
            
            if ($start >= $end) {
                $this->flash('error', 'End Time must be after Start Time.');
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }

            // Check for overlapping time periods EXCLUDING the column we are currently editing
            $existingSlots = $this->db->fetchAll(
                "SELECT DISTINCT start_time, end_time FROM timetable WHERE class_id = ? AND (start_time != ? OR end_time != ?)",
                [$classId, $oldStartTime, $oldEndTime]
            );

            foreach ($existingSlots as $slot) {
                $eStart = strtotime($slot['start_time']);
                $eEnd = strtotime($slot['end_time']);

                if ($start < $eEnd && $end > $eStart) {
                    $this->flash('error', 'The new time period overlaps with an existing column (' . date('h:i A', $eStart) . ' - ' . date('h:i A', $eEnd) . '). Please choose a valid time.');
                    $this->redirect('/admin/timetable?class_id=' . $classId);
                    return;
                }
            }

            $updateData = [
                'start_time' => $newStartTime,
                'end_time'   => $newEndTime
            ];
            
            // Only update break_name if it was provided (for breaks). 
            // Since we aren't using break_name for subject periods anymore.
            if ($breakName !== '') {
                $updateData['break_name'] = $breakName;
            }

            $this->db->update('timetable', $updateData, 'class_id = ? AND start_time = ? AND end_time = ?', [$classId, $oldStartTime, $oldEndTime]);
            
            $this->flash('success', 'Time column updated successfully.');
            $this->redirect('/admin/timetable?class_id=' . $classId);

        } elseif ($action === 'update_timetable') {
            $classId = (int)$this->input('class_id', 0);
            $subjectsMap = $_POST['timetable_subjects'] ?? [];

            $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
            $session = $academicRepo->getActiveSession();
            $sessionId = $session ? $session['session_id'] : 0;

            foreach ($subjectsMap as $timetableId => $subjectId) {
                $subjectId = (int)$subjectId;
                $teacherId = null;

                if ($subjectId > 0 && $sessionId) {
                    $classSubject = $this->db->fetch(
                        "SELECT teacher_id FROM class_subjects WHERE class_id = ? AND subject_id = ? AND session_id = ?",
                        [$classId, $subjectId, $sessionId]
                    );
                    if ($classSubject && $classSubject['teacher_id']) {
                        $teacherId = $classSubject['teacher_id'];
                    }
                }

                $updateData = [];
                if ($subjectId > 0) {
                    $updateData['subject_id'] = $subjectId;
                    $updateData['teacher_id'] = $teacherId;
                } else {
                    $updateData['subject_id'] = null;
                    $updateData['teacher_id'] = null;
                }

                $this->db->update('timetable', $updateData, 'timetable_id = ?', [$timetableId]);
            }

            $this->flash('success', 'Timetable subjects updated successfully.');
            $this->redirect('/admin/timetable?class_id=' . $classId);
        } elseif ($action === 'download_template') {
            $this->downloadTemplate();
        } elseif ($action === 'import_csv') {
            $this->importCsv();
        }

        $this->redirect(moduleUrl('admin', 'timetable'));
    }

    private function downloadTemplate(): void
    {
        $filename = "timetable_template.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Day', 'Start Time (HH:MM)', 'End Time (HH:MM)', 'Subject Code (or Break)']);
        
        // Example row
        fputcsv($output, ['Monday', '09:00', '10:00', 'MATH101']);
        fputcsv($output, ['Monday', '10:00', '10:15', 'Break']);
        
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
        $header = fgetcsv($handle); // Skip header
        $validRows = [];
        $rowNum = 1;

        $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($data) < 4) continue; // Skip empty/invalid rows
            
            $day = ucfirst(strtolower(trim($data[0])));
            $startTime = date('H:i:s', strtotime(trim($data[1])));
            $endTime = date('H:i:s', strtotime(trim($data[2])));
            $subjectCode = strtoupper(trim($data[3]));

            if (!in_array($day, $validDays)) {
                $this->flash('error', "Invalid day '$day' on row $rowNum.");
                fclose($handle);
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }

            if ($startTime >= $endTime) {
                $this->flash('error', "Start time must be before end time on row $rowNum.");
                fclose($handle);
                $this->redirect('/admin/timetable?class_id=' . $classId);
                return;
            }

            $isBreak = 0;
            $breakName = null;
            $subjectId = null;
            $teacherId = null;

            if ($subjectCode === 'BREAK' || empty($subjectCode)) {
                $isBreak = 1;
                $breakName = 'Break';
            } else {
                if (!isset($subjectMap[$subjectCode])) {
                    $this->flash('error', "Subject code '$subjectCode' on row $rowNum is not assigned to this class.");
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
        fclose($handle);

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
            $this->flash('error', "Failed to import timetable: " . $e->getMessage());
        }

        $this->redirect('/admin/timetable?class_id=' . $classId);
    }
}
