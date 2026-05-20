<?php
/**
 * TranscriptGenerator - Generate academic transcripts
 * Supports PDF and Excel export
 */

class TranscriptGenerator {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Generate complete academic transcript (HTML format)
     */
    public function generateTranscriptHTML($student_id) {
        $student = $this->getStudentInfo($student_id);
        $performance = $this->getCumulativePerformance($student_id);
        $session_wise = $this->getSessionWiseData($student_id);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Academic Transcript - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; background: #f5f5f5; }
                .transcript { max-width: 900px; margin: 20px auto; background: white; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #800000; padding-bottom: 15px; }
                .header h1 { color: #800000; font-size: 24px; margin-bottom: 5px; }
                .header p { color: #666; font-size: 14px; }
                .student-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
                .info-block { padding: 10px; }
                .info-label { font-weight: bold; color: #333; margin-bottom: 3px; }
                .info-value { color: #666; }
                .summary-stats { background: #f9f6f0; padding: 20px; border-radius: 6px; margin-bottom: 30px; }
                .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
                .stat-box { text-align: center; }
                .stat-value { font-size: 24px; font-weight: bold; color: #800000; }
                .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
                .section-title { font-size: 16px; font-weight: bold; color: #800000; margin-top: 25px; margin-bottom: 15px; border-bottom: 2px solid #d4c5b9; padding-bottom: 8px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background: #f9f6f0; padding: 12px; text-align: left; font-weight: bold; border: 1px solid #d4c5b9; }
                td { padding: 10px 12px; border: 1px solid #d4c5b9; }
                tr:nth-child(even) { background: #fafafa; }
                .grade-a { color: #2e7d32; font-weight: bold; }
                .grade-f { color: #c62828; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #d4c5b9; font-size: 12px; color: #999; }
                @media print {
                    body { background: white; }
                    .transcript { box-shadow: none; }
                }
            </style>
        </head>
        <body>
            <div class="transcript">
                <!-- Header -->
                <div class="header">
                    <h1>ACADEMIC TRANSCRIPT</h1>
                    <p><?php echo APP_NAME; ?></p>
                </div>

                <!-- Student Information -->
                <div class="student-info">
                    <div class="info-block">
                        <div class="info-label">Student Name:</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-label">Student ID:</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-label">Date of Birth:</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['date_of_birth']); ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-label">Gender:</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['gender']); ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-label">Admission Date:</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['admission_date']); ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-label">Transcript Date:</div>
                        <div class="info-value"><?php echo date('d M Y'); ?></div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="summary-stats">
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $performance['cumulative_percentage']; ?>%</div>
                            <div class="stat-label">Cumulative Average</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $performance['total_sessions']; ?></div>
                            <div class="stat-label">Sessions Completed</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $performance['times_promoted']; ?></div>
                            <div class="stat-label">Times Promoted</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $performance['total_a_grades']; ?></div>
                            <div class="stat-label">A Grades</div>
                        </div>
                    </div>
                </div>

                <!-- Session-wise Performance -->
                <div class="section-title">Session-wise Performance</div>
                <table>
                    <thead>
                        <tr>
                            <th>Session</th>
                            <th>Class</th>
                            <th>Roll Number</th>
                            <th>Average %</th>
                            <th>Subjects</th>
                            <th>A Grades</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($session_wise as $session): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session['session_name']); ?></td>
                                <td><?php echo htmlspecialchars($session['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($session['roll_number']); ?></td>
                                <td><strong><?php echo round($session['session_percentage'], 2); ?>%</strong></td>
                                <td><?php echo $session['subjects_taken']; ?></td>
                                <td><?php echo $session['a_grades']; ?></td>
                                <td>
                                    <?php 
                                    $status = ucfirst($session['promotion_type'] ?? 'Active');
                                    $status_color = $session['promotion_type'] === 'promoted' ? '#2e7d32' : 
                                                   ($session['promotion_type'] === 'detained' ? '#c62828' : '#f57f17');
                                    ?>
                                    <span style="color: <?php echo $status_color; ?>; font-weight: bold;">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Subject-wise Details -->
                <div class="section-title">Subject-wise Performance</div>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Session</th>
                            <th>Marks Obtained</th>
                            <th>Max Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $all_results = $this->getSubjectWiseResults($student_id);
                        foreach ($all_results as $result): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['session_name']); ?></td>
                                <td><?php echo $result['marks_obtained']; ?></td>
                                <td><?php echo $result['max_marks']; ?></td>
                                <td><?php echo round(($result['marks_obtained'] / $result['max_marks']) * 100, 2); ?>%</td>
                                <td>
                                    <span class="<?php echo $result['grade'] === 'A' ? 'grade-a' : ($result['grade'] === 'F' ? 'grade-f' : ''); ?>">
                                        <?php echo htmlspecialchars($result['grade']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Certification -->
                <div style="margin-top: 40px; padding: 20px; background: #f9f6f0; border-radius: 6px; text-align: center;">
                    <p><strong>This is a true and certified academic record of the student.</strong></p>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        Generated on <?php echo date('d M Y H:i'); ?> | This document is valid for academic purposes only.
                    </p>
                </div>

                <!-- Download Options -->
                <div style="margin-top: 30px; padding: 20px; background: #e8f4f8; border-radius: 6px; text-align: center; display: none;" id="download-options">
                    <p style="margin: 0 0 15px 0; font-weight: bold; color: #0c5460;">Download this transcript:</p>
                    <a href="download_transcript.php?format=html" style="display: inline-block; padding: 10px 15px; margin: 0 5px; background: #0c5460; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                        📄 View/Print (HTML)
                    </a>
                    <a href="download_transcript.php?format=pdf" style="display: inline-block; padding: 10px 15px; margin: 0 5px; background: #c62828; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                        📕 Download (PDF)
                    </a>
                    <a href="download_transcript.php?format=csv" style="display: inline-block; padding: 10px 15px; margin: 0 5px; background: #2e7d32; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                        📊 Export (CSV)
                    </a>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <p><?php echo APP_NAME; ?> - School Management System</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate CSV export
     */
    public function generateCSV($student_id) {
        $student = $this->getStudentInfo($student_id);
        $all_results = $this->getSubjectWiseResults($student_id);

        $csv = "Academic Transcript - " . $student['first_name'] . " " . $student['last_name'] . "\n";
        $csv .= "Generated on: " . date('d M Y H:i') . "\n\n";
        $csv .= "Student ID,Name,Date of Birth,Session,Class,Roll Number,Subject,Marks Obtained,Max Marks,Percentage,Grade\n";

        foreach ($all_results as $result) {
            $percentage = ($result['marks_obtained'] / $result['max_marks']) * 100;
            $csv .= "{$student['student_id']},";
            $csv .= "\"{$student['first_name']} {$student['last_name']}\",";
            $csv .= "{$student['date_of_birth']},";
            $csv .= "{$result['session_name']},";
            $csv .= "{$result['class_name']},";
            $csv .= "{$result['roll_number']},";
            $csv .= "\"{$result['subject_name']}\",";
            $csv .= "{$result['marks_obtained']},";
            $csv .= "{$result['max_marks']},";
            $csv .= round($percentage, 2) . ",";
            $csv .= "{$result['grade']}\n";
        }

        return $csv;
    }

    /**
     * Helper: Get student info
     */
    private function getStudentInfo($student_id) {
        $query = "SELECT * FROM students WHERE student_id = $student_id";
        $result = mysqli_query($this->conn, $query);
        return mysqli_fetch_assoc($result);
    }

    /**
     * Helper: Get cumulative performance
     */
    private function getCumulativePerformance($student_id) {
        $query = "
            SELECT 
                ROUND(AVG(CASE WHEN r.max_marks > 0 
                    THEN (r.marks_obtained / r.max_marks) * 100 
                    ELSE 0 END), 2) as cumulative_percentage,
                COUNT(DISTINCT sa.session_id) as total_sessions,
                COUNT(CASE WHEN r.grade = 'A' THEN 1 END) as total_a_grades,
                COUNT(CASE WHEN ph.promotion_type = 'promoted' THEN 1 END) as times_promoted
            FROM results r
            JOIN student_academics sa ON r.student_id = sa.student_id
            LEFT JOIN promotion_history ph ON sa.student_id = ph.student_id
            WHERE r.student_id = $student_id AND r.is_absent = 0
        ";
        $result = mysqli_query($this->conn, $query);
        return mysqli_fetch_assoc($result);
    }

    /**
     * Helper: Get session-wise data
     */
    private function getSessionWiseData($student_id) {
        $query = "
            SELECT 
                ac.session_name,
                cl.class_name,
                sa.roll_number,
                ROUND(AVG(CASE WHEN r.max_marks > 0 
                    THEN (r.marks_obtained / r.max_marks) * 100 
                    ELSE 0 END), 2) as session_percentage,
                COUNT(DISTINCT r.subject_id) as subjects_taken,
                COUNT(CASE WHEN r.grade = 'A' THEN 1 END) as a_grades,
                ph.promotion_type
            FROM student_academics sa
            JOIN academic_sessions ac ON sa.session_id = ac.session_id
            JOIN classes cl ON sa.class_id = cl.class_id
            LEFT JOIN results r ON sa.student_id = r.student_id
            LEFT JOIN promotion_history ph ON sa.academic_id = ph.from_academic_id
            WHERE sa.student_id = $student_id
            GROUP BY sa.academic_id
            ORDER BY ac.session_id DESC
        ";
        $result = mysqli_query($this->conn, $query);
        $sessions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sessions[] = $row;
        }
        return $sessions;
    }

    /**
     * Helper: Get subject-wise results
     */
    private function getSubjectWiseResults($student_id) {
        $query = "
            SELECT 
                s.subject_name,
                ac.session_name,
                cl.class_name,
                sa.roll_number,
                r.marks_obtained,
                r.max_marks,
                r.grade
            FROM results r
            JOIN subjects s ON r.subject_id = s.subject_id
            JOIN student_academics sa ON r.student_id = sa.student_id
            JOIN academic_sessions ac ON sa.session_id = ac.session_id
            JOIN classes cl ON sa.class_id = cl.class_id
            WHERE r.student_id = $student_id AND r.is_absent = 0
            ORDER BY ac.session_id DESC, s.subject_name
        ";
        $result = mysqli_query($this->conn, $query);
        $results = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
        return $results;
    }
}
?>
