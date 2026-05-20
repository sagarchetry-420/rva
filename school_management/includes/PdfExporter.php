<?php
/**
 * PdfExporter - Convert HTML to PDF transcripts
 * Uses dompdf library for professional PDF generation
 *
 * Installation:
 * composer require dompdf/dompdf
 * OR download from https://github.com/dompdf/dompdf
 */

class PdfExporter {
    private $html_content;

    public function __construct() {
        // Check if dompdf is available
        if (!class_exists('\Dompdf\Dompdf')) {
            // Attempt to auto-load if composer is installed
            $composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';
            if (file_exists($composer_autoload)) {
                require_once $composer_autoload;
            }
        }
    }

    /**
     * Convert HTML to PDF
     * @param string $html_content - HTML content to convert
     * @param string $filename - Output filename (without .pdf)
     * @return bool|string - PDF binary content on success, false on failure
     */
    public function generatePdf($html_content, $filename = 'transcript') {
        try {
            // Check if dompdf is available
            if (!class_exists('\Dompdf\Dompdf')) {
                throw new Exception("dompdf library not found. Install with: composer require dompdf/dompdf");
            }

            // Create PDF instance
            $dompdf = new \Dompdf\Dompdf([
                'enable_html5_parser' => true,
                'enable_remote' => false,
                'is_php_enabled' => false,
                'default_font' => 'Arial',
                'font_dir' => dirname(__DIR__) . '/assets/fonts',
            ]);

            // Set HTML content
            $dompdf->loadHtml($html_content);

            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');

            // Render PDF
            $dompdf->render();

            // Return PDF content
            return $dompdf->output();

        } catch (Exception $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save PDF to file
     * @param string $html_content - HTML content
     * @param string $filepath - Full file path to save
     * @return bool - Success/failure
     */
    public function savePdf($html_content, $filepath) {
        try {
            $pdf_content = $this->generatePdf($html_content);
            if ($pdf_content === false) {
                return false;
            }

            $bytes_written = file_put_contents($filepath, $pdf_content);
            return $bytes_written > 0;

        } catch (Exception $e) {
            error_log("PDF Save Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Output PDF directly to browser for download
     * @param string $html_content - HTML content
     * @param string $filename - Download filename (without .pdf extension)
     * @return void
     */
    public function downloadPdf($html_content, $filename = 'transcript') {
        try {
            $pdf_content = $this->generatePdf($html_content);
            if ($pdf_content === false) {
                die("Error generating PDF. dompdf library may not be installed.");
            }

            // Set headers for download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . sanitize_filename($filename) . '.pdf"');
            header('Content-Length: ' . strlen($pdf_content));
            header('Cache-Control: private');
            header('Pragma: private');

            // Output PDF
            echo $pdf_content;
            exit;

        } catch (Exception $e) {
            die("Error: " . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Sanitize filename for security
     */
    private function sanitize_filename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        return substr($filename, 0, 100);
    }

    /**
     * Check if dompdf is available
     */
    public static function isAvailable() {
        return class_exists('\Dompdf\Dompdf');
    }

    /**
     * Get installation instructions
     */
    public static function getInstallationInstructions() {
        return "
        PDF Export requires dompdf library. Install using:

        Option 1 (Recommended): Using Composer
        $ composer require dompdf/dompdf

        Option 2: Manual download
        1. Download from: https://github.com/dompdf/dompdf/releases
        2. Extract to: school_management/vendor/dompdf/dompdf
        3. Ensure autoloader is available

        Option 3: Include dompdf directly
        Download dompdf and include the autoloader in your script.
        ";
    }
}
?>
