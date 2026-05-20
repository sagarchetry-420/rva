<?php
/**
 * Transcript Download API
 * Allows students to download their academic transcripts
 * Supports: HTML (view/print), CSV (spreadsheet), PDF (professional)
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/TranscriptGenerator.php';
require_once dirname(__DIR__) . '/includes/PdfExporter.php';
requireStudent();

$uid = getUserId();
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE user_id=$uid"));
$sid = $student['student_id'];

$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html'; // html, pdf, csv

try {
    $generator = new TranscriptGenerator($conn);

    if ($format === 'csv') {
        // CSV Export
        $csv_content = $generator->generateCSV($sid);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transcript_' . $sid . '_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $csv_content;

    } elseif ($format === 'pdf') {
        // PDF Export using dompdf
        if (!PdfExporter::isAvailable()) {
            // If dompdf not available, show HTML with print instructions
            echo "<!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Transcript PDF</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .alert { background: #fef3c7; border: 1px solid #fcd34d; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
                    .alert h3 { margin-top: 0; color: #92400e; }
                </style>
            </head>
            <body>
                <div class='alert'>
                    <h3>PDF Library Not Installed</h3>
                    <p>PDF generation requires dompdf library. Please contact your administrator.</p>
                    <p>You can:</p>
                    <ul>
                        <li><a href='?format=html'>View as HTML</a> and use your browser's Print function (Ctrl+P)</li>
                        <li><a href='?format=csv'>Download as CSV</a> for spreadsheet use</li>
                    </ul>
                </div>";
            echo $generator->generateTranscriptHTML($sid);
        } else {
            // Generate PDF and download
            $html_content = $generator->generateTranscriptHTML($sid);
            $student_name = str_replace(' ', '_', trim($student['first_name'] . '_' . $student['last_name']));
            $filename = "transcript_" . $student_name . "_" . date('Y-m-d');

            $exporter = new PdfExporter();
            $exporter->downloadPdf($html_content, $filename);
        }

    } else {
        // Default: HTML view (view/print in browser)
        header('Content-Type: text/html; charset=utf-8');
        echo $generator->generateTranscriptHTML($sid);
    }

} catch (Exception $e) {
    die("Error generating transcript: " . htmlspecialchars($e->getMessage()));
}
?>
