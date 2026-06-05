<?php
/**
 * Cron Script to Generate Recurring Service Fees
 * 
 * This script should be run via a cron job daily or monthly (e.g., at 1 AM).
 * It will find active student services that are billed 'Monthly', 'Quarterly', 
 * 'Term-wise', or 'Yearly', and generate the appropriate fee invoices if they 
 * haven't been generated for the current billing period.
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/app/Core/App.php';
App::boot();

$cronSecret = App::env('CRON_SECRET_KEY', 'rva_cron_secret');

// Basic CLI check or simple security key for web invocation
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== $cronSecret)) {
    die("Access denied.");
}

require_once APP_ROOT . '/app/Core/Database.php';
$db = Database::getInstance();

echo "Starting recurring fee generation...\n";

// Get active session
$session = $db->fetch("SELECT session_id FROM academic_sessions WHERE is_current = 1");
if (!$session) {
    die("No active academic session found.\n");
}
$sessionId = $session['session_id'];

// Ensure 'Service Fee' category exists
$category = $db->fetch("SELECT category_id FROM fee_categories WHERE category_name = 'Service Fee'");
if (!$category) {
    $db->insert('fee_categories', [
        'category_name' => 'Service Fee',
        'description' => 'Charges for additional student services',
        'is_active' => 1
    ]);
    $categoryId = (int)$db->getConnection()->lastInsertId();
} else {
    $categoryId = (int)$category['category_id'];
}

// 1. Find all active subscriptions to recurring services
// Recurring cycles: Monthly, Quarterly, Term-wise, Yearly
$recurringServices = $db->fetchAll("
    SELECT ss.student_id, ss.service_id, s.service_name, s.billing_cycle, st.current_class_id
    FROM student_services ss
    JOIN services s ON ss.service_id = s.service_id
    JOIN students st ON ss.student_id = st.student_id
    WHERE ss.is_active = 1 
      AND ss.session_id = ?
      AND s.billing_cycle IN ('Monthly', 'Quarterly', 'Term-wise', 'Yearly')
", [$sessionId]);

if (empty($recurringServices)) {
    echo "No active recurring services found.\n";
    exit;
}

$currentMonth = (int)date('m');
$currentYear = (int)date('Y');
$currentQuarter = ceil($currentMonth / 3);

$generatedCount = 0;

$db->beginTransaction();

try {
    foreach ($recurringServices as $sub) {
        $studentId = $sub['student_id'];
        $serviceId = $sub['service_id'];
        $cycle = $sub['billing_cycle'];
        $classId = $sub['current_class_id'];
        
        // Determine the period identifier for remarks and duplicate checking
        $periodIdentifier = '';
        
        if ($cycle === 'Monthly') {
            $periodIdentifier = date('M Y'); // e.g., "Jun 2026"
        } elseif ($cycle === 'Quarterly') {
            $periodIdentifier = "Q{$currentQuarter} {$currentYear}";
        } elseif ($cycle === 'Yearly') {
            $periodIdentifier = "Year {$currentYear}";
        } elseif ($cycle === 'Term-wise') {
            // Approximation for term, assuming 2 terms per year:
            // Term 1: Jan - Jun, Term 2: Jul - Dec
            $term = ($currentMonth <= 6) ? 1 : 2;
            $periodIdentifier = "Term {$term} {$currentYear}";
        }
        
        $remarks = "{$sub['service_name']} - {$periodIdentifier}";
        
        // Check if fee is already generated for this period for this student
        $existingFee = $db->fetch("
            SELECT fee_id FROM fees 
            WHERE student_id = ? AND service_id = ? AND session_id = ? 
            AND remarks = ?
        ", [$studentId, $serviceId, $sessionId, $remarks]);
        
        if ($existingFee) {
            // Already generated, skip
            continue;
        }

        // Determine fee amount (check class-specific override first)
        $override = $db->fetch("SELECT fee_amount FROM class_service_fees WHERE service_id = ? AND class_id = ?", [$serviceId, $classId]);
        
        if ($override) {
            $amount = (float)$override['fee_amount'];
        } else {
            // Fallback to default
            $svc = $db->fetch("SELECT fee_amount FROM services WHERE service_id = ?", [$serviceId]);
            $amount = $svc ? (float)$svc['fee_amount'] : 0.00;
        }
        
        if ($amount > 0) {
            $dueDate = date('Y-m-d', strtotime(date('Y-m-15'))); // Due on 15th of the month
            if ($dueDate < date('Y-m-d')) {
                $dueDate = date('Y-m-d', strtotime('+15 days'));
            }

            $db->insert('fees', [
                'student_id'     => $studentId,
                'session_id'     => $sessionId,
                'category_id'    => $categoryId,
                'service_id'     => $serviceId,
                'amount'         => $amount,
                'due_date'       => $dueDate,
                'payment_status' => 'Pending',
                'remarks'        => $remarks,
                'created_by'     => 1, // System admin
            ]);
            
            $generatedCount++;
        }
    }

    $db->commit();
    echo "Successfully generated {$generatedCount} recurring fee invoices.\n";
    
} catch (\Exception $e) {
    $db->rollback();
    echo "Error generating fees: " . $e->getMessage() . "\n";
}
