<?php
namespace App\Modules\Attendance\Services;

require_once APP_ROOT . '/includes/fpdf/fpdf.php';
use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\Academic\Repositories\ClassRepository;

class AttendancePdfService extends \FPDF
{
    public function generateClassReport(int $classId, string $date, array $students)
    {
        $classRepo = new ClassRepository();
        $classInfo = $classRepo->findById($classId);
        $className = $classInfo ? $classInfo['class_name'] . ' ' . $classInfo['section'] : 'Unknown Class';

        $this->AddPage();
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Daily Attendance Report', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Class: ' . $className . ' | Date: ' . date('d-m-Y', strtotime($date)), 0, 1, 'C');
        $this->Ln(5);

        // Table Header
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(20, 10, 'Roll', 1, 0, 'C', true);
        $this->Cell(60, 10, 'Student Name', 1, 0, 'L', true);
        $this->Cell(30, 10, 'Status', 1, 0, 'C', true);
        $this->Cell(80, 10, 'Remarks', 1, 1, 'L', true);

        // Table Body
        $this->SetFont('Arial', '', 10);
        foreach ($students as $s) {
            $status = $s['status'] ?? 'Not Marked';
            $this->Cell(20, 10, $s['roll_number'], 1, 0, 'C');
            $this->Cell(60, 10, substr($s['first_name'] . ' ' . $s['last_name'], 0, 30), 1, 0, 'L');
            $this->Cell(30, 10, $status, 1, 0, 'C');
            $this->Cell(80, 10, substr($s['remarks'] ?? '', 0, 60), 1, 1, 'L');
        }

        if (ob_get_length()) ob_end_clean();
        $this->Output('D', 'Attendance_' . $className . '_' . $date . '.pdf');
        exit;
    }

    public function generateStudentReport(array $studentInfo, array $attendanceSummary)
    {
        $this->AddPage();
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Student Attendance Summary', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Student: ' . $studentInfo['first_name'] . ' ' . $studentInfo['last_name'] . ' (' . $studentInfo['roll_number'] . ')', 0, 1, 'C');
        $this->Ln(10);

        // Table Header
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(95, 10, 'Status', 1, 0, 'C', true);
        $this->Cell(95, 10, 'Total Days', 1, 1, 'C', true);

        // Table Body
        $this->SetFont('Arial', '', 12);
        foreach ($attendanceSummary as $status => $total) {
            $this->Cell(95, 10, $status, 1, 0, 'C');
            $this->Cell(95, 10, $total, 1, 1, 'C');
        }

        if (ob_get_length()) ob_end_clean();
        $this->Output('D', 'Student_Attendance_' . $studentInfo['roll_number'] . '.pdf');
        exit;
    }

    public function generateMonthlyReport(int $classId, string $yearMonth, array $students)
    {
        $classRepo = new ClassRepository();
        $classInfo = $classRepo->findById($classId);
        $className = $classInfo ? $classInfo['class_name'] . ' ' . $classInfo['section'] : 'Unknown Class';
        $monthName = date('F Y', strtotime($yearMonth . '-01'));

        $this->AddPage('L'); // Landscape for many columns
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Monthly Attendance Report', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Class: ' . $className . ' | Month: ' . $monthName, 0, 1, 'C');
        $this->Ln(5);

        $daysInMonth = (int)date('t', strtotime($yearMonth . '-01'));

        // Table Header
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15, 8, 'Roll', 1, 0, 'C');
        $this->Cell(45, 8, 'Student Name', 1, 0, 'L');
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $isWeekend = date('N', strtotime($yearMonth . '-' . sprintf('%02d', $i))) == 7;
            if ($isWeekend) {
                $this->SetFillColor(255, 200, 200); // Light red for weekends
                $this->Cell(7, 8, $i, 1, 0, 'C', true);
            } else {
                $this->Cell(7, 8, $i, 1, 0, 'C');
            }
        }
        $this->Ln();

        // Table Body
        $this->SetFont('Arial', '', 8);
        foreach ($students as $s) {
            $this->Cell(15, 8, $s['roll_number'], 1, 0, 'C');
            $this->Cell(45, 8, substr($s['name'], 0, 25), 1, 0, 'L');
            
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $isWeekend = date('N', strtotime($yearMonth . '-' . sprintf('%02d', $i))) == 7;
                $status = $s['attendance'][$i] ?? '-';
                
                if ($status === 'Present') $status = 'P';
                elseif ($status === 'Absent') $status = 'A';
                elseif ($status === 'Leave') $status = 'L';
                elseif ($status === 'Half Leave') $status = 'HL';
                
                if ($isWeekend && $status === '-') {
                    $this->SetFillColor(255, 230, 230);
                    $this->Cell(7, 8, $status, 1, 0, 'C', true);
                } else {
                    $this->Cell(7, 8, $status, 1, 0, 'C');
                }
            }
            $this->Ln();
        }

        if (ob_get_length()) ob_end_clean();
        $this->Output('D', 'Monthly_Attendance_' . $className . '_' . $yearMonth . '.pdf');
        exit;
    }
}
