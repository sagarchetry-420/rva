<?php
namespace App\Modules\Fees\Controllers;

use App\Modules\Fees\Repositories\FeeRepository;

/**
 * ReceiptController — Handles viewing/printing receipts
 */
class ReceiptController extends \Controller
{
    private FeeRepository $feeRepo;

    public function __construct()
    {
        parent::__construct();
        $this->feeRepo = new FeeRepository();
    }

    public function view(): void
    {
        $receiptNo = $this->input('receipt_no', '');
        $isStudent = (isset($_SESSION['role']) && $_SESSION['role'] === 'student');
        $redirectUrl = $isStudent ? '/student/fees' : '/admin/fee_collection';

        if (empty($receiptNo)) {
            $this->flash('error', 'Invalid receipt number.');
            $this->redirect($redirectUrl);
            return;
        }

        $receiptData = $this->feeRepo->getReceiptData($receiptNo);

        if (!$receiptData) {
            $this->flash('error', 'Receipt not found.');
            $this->redirect($redirectUrl);
            return;
        }

        if ($isStudent) {
            $studentRepo = new \App\Modules\Student\Repositories\StudentRepository();
            $student = $studentRepo->findByUserId($_SESSION['user_id']);
            if (!$student || $receiptData['student_id'] != $student['student_id']) {
                $this->flash('error', 'Unauthorized access to this receipt.');
                $this->redirect($redirectUrl);
                return;
            }
        }

        // Render the receipt without any surrounding layout
        $this->render('Modules/Fees/Views/receipt', [
            'pageTitle' => 'Receipt: ' . $receiptNo,
            'receipt'   => $receiptData
        ]);
    }
}
