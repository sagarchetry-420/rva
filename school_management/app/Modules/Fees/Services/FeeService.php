<?php
namespace App\Modules\Fees\Services;

use Database;
use App\Modules\Fees\Repositories\FeeRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;

/**
 * FeeService — Business logic for fee generation and collection
 */
class FeeService
{
    private FeeRepository $feeRepo;
    private ClassSubjectRepository $academicRepo;

    public function __construct()
    {
        $this->feeRepo = new FeeRepository();
        $this->academicRepo = new ClassSubjectRepository();
    }

    /**
     * Process a fee payment, marking it paid and generating a receipt.
     */
    public function collectFee(int $feeId, string $paymentMethod, string $remarks, int $userId, int $studentId = 0, float $amount = null): array
    {
        $fee = $this->feeRepo->findById($feeId);
        if (!$fee) {
            return ['success' => false, 'message' => 'Fee record not found.'];
        }

        if ($studentId > 0 && (int)$fee['student_id'] !== $studentId) {
            return ['success' => false, 'message' => 'Fee does not belong to the selected student.'];
        }

        if ($amount !== null && (float)$fee['amount'] !== $amount) {
            return ['success' => false, 'message' => 'Submitted amount does not match the actual fee amount.'];
        }

        if ($fee['payment_status'] === 'Paid') {
            return ['success' => false, 'message' => 'This fee is already paid.'];
        }

        // Generate a unique receipt number: RVA-YYYYMMDD-FEEID-RANDOM
        $receiptNumber = 'RVA-' . date('Ymd') . '-' . $feeId . '-' . strtoupper(substr(uniqid(), -4));

        try {
            Database::getInstance()->transaction(function() use ($feeId, $paymentMethod, $remarks, $userId, $receiptNumber) {
                $this->feeRepo->updateFee($feeId, [
                    'payment_status' => 'Paid',
                    'payment_date'   => date('Y-m-d'),
                    'payment_method' => $paymentMethod,
                    'receipt_number' => $receiptNumber,
                    'remarks'        => $remarks,
                    'created_by'     => $userId // the user who collected it
                ]);
            });
            return [
                'success' => true, 
                'message' => 'Payment collected successfully.',
                'receipt_number' => $receiptNumber
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()];
        }
    }

    /**
     * Generate a manual fee for a student
     */
    public function generateFee(array $data, int $userId): array
    {
        $session = $this->academicRepo->getActiveSession();
        if (!$session) {
            return ['success' => false, 'message' => 'No active academic session found.'];
        }

        // Prevent duplicate generation for the same student, category, and due date
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT fee_id FROM fees WHERE student_id = ? AND session_id = ? AND category_id = ? AND due_date = ?", [
            $data['student_id'],
            $session['session_id'],
            $data['category_id'],
            $data['due_date']
        ]);

        if ($existing) {
            return ['success' => false, 'message' => 'A fee for this category and due date already exists for this student!'];
        }

        $feeId = $this->feeRepo->createFee([
            'student_id'  => $data['student_id'],
            'session_id'  => $session['session_id'],
            'category_id' => $data['category_id'],
            'service_id'  => $data['service_id'] ?? null,
            'amount'      => $data['amount'],
            'due_date'    => $data['due_date'],
            'created_by'  => $userId
        ]);

        return ['success' => true, 'message' => 'Fee generated successfully.'];
    }
}
