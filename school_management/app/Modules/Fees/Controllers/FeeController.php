<?php
namespace App\Modules\Fees\Controllers;

use App\Modules\Fees\Services\FeeService;
use App\Modules\Fees\Validators\FeeValidator;
use App\Modules\Fees\Repositories\FeeRepository;
use App\Modules\Fees\Repositories\FeeCategoryRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;

/**
 * FeeController — Collect fees and manage student dues
 */
class FeeController extends \Controller
{
    private FeeService $service;
    private FeeRepository $feeRepo;

    public function __construct()
    {
        parent::__construct();
        $this->service = new FeeService();
        $this->feeRepo = new FeeRepository();
    }

    public function collection(): void
    {
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        if (!$session) {
            $this->render('Modules/Fees/Views/collection', [
                'pageTitle' => 'Fee Collection',
                'session'   => null,
                'students'  => [],
                'search'    => '',
                'exams'     => []
            ], 'admin');
            return;
        }

        $search = strip_tags(trim($this->input('search', '')));
        $filterClassId = (int)$this->input('filter_class_id', 0);
        $page = (int)$this->input('page', 1);
        $paginatedStudents = ['data' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1, 'per_page' => 20];

        $totalPendingCount = 0;
        if (!empty($search)) {
            $paginatedStudents = $this->feeRepo->searchStudents($search, $session['session_id'], $page, 20);
            $totalPendingCount = $this->feeRepo->countStudentsWithPendingDues($session['session_id'], $search, 0);
        } elseif ($filterClassId > 0) {
            $paginatedStudents = $this->feeRepo->findStudentsByClass($filterClassId, $session['session_id'], $page, 20);
            $totalPendingCount = $this->feeRepo->countStudentsWithPendingDues($session['session_id'], '', $filterClassId);
        } else {
            // Default: load recent active students
            $paginatedStudents = $this->feeRepo->searchStudents('', $session['session_id'], $page, 20);
            $totalPendingCount = $this->feeRepo->countStudentsWithPendingDues($session['session_id'], '', 0);
        }
        
        $classes = $this->db->fetchAll("SELECT * FROM classes ORDER BY LENGTH(class_name), class_name");
        $exams = $this->db->fetchAll("SELECT * FROM examinations WHERE session_id = ? AND exam_type != 'Class Test' ORDER BY start_date DESC", [$session['session_id']]);

        $this->render('Modules/Fees/Views/collection', [
            'pageTitle'   => 'Fee Collection',
            'session'     => $session,
            'search'      => $search,
            'filterClassId' => $filterClassId,
            'students'    => $paginatedStudents['data'],
            'pagination'  => $paginatedStudents,
            'totalPendingCount' => $totalPendingCount,
            'classes'     => $classes,
            'exams'       => $exams
        ], 'admin');
    }

    public function studentFees(): void
    {
        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        if (!$session) {
            $this->flash('error', 'No active academic session.');
            $this->redirect(moduleUrl('admin', 'fee_collection'));
            return;
        }

        $selectedStudentId = (int)$this->input('student_id', 0);
        if (!$selectedStudentId) {
            $this->redirect(moduleUrl('admin', 'fee_collection'));
            return;
        }

        $search = $this->input('search', '');
        $filterClassId = (int)$this->input('filter_class_id', 0);
        
        $studentFees = $this->feeRepo->findFeesByStudent($selectedStudentId, $session['session_id']);
        
        $catRepo = new FeeCategoryRepository();
        $categories = $catRepo->findAll();

        $this->render('Modules/Fees/Views/student_fees', [
            'pageTitle'   => 'Student Fee History',
            'session'     => $session,
            'search'      => $search,
            'filterClassId' => $filterClassId,
            'selectedStudentId' => $selectedStudentId,
            'studentFees' => $studentFees,
            'categories'  => $categories
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');

        switch ($action) {
            case 'collect':
                $this->collectFee();
                break;
            case 'generate':
                $this->generateFee();
                break;
            case 'generate_exam_fees':
                $this->generateExamFees();
                break;
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('admin', 'fee_collection'));
        }
    }

    private function collectFee(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();
        $redirectTo = $this->input('redirect_to', 'fee_collection');
        $redirectUrl = $redirectTo === 'student_fees' 
            ? '/admin/student_fees?student_id=' . ($data['student_id'] ?? '')
            : '/admin/fee_collection';

        $validator = new FeeValidator();
        if (!$validator->validateCollection($data)) {
            $this->flash('error', $validator->firstError());
            $this->redirect($redirectUrl);
            return;
        }

        $remarks = strip_tags(trim($data['remarks'] ?? ''));
        $remarks = htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8');

        $result = $this->service->collectFee(
            (int)$data['fee_id'], 
            $data['payment_method'], 
            $remarks, 
            $_SESSION['user_id'],
            (int)($data['student_id'] ?? 0),
            isset($data['amount']) ? (float)$data['amount'] : null
        );

        if ($result['success']) {
            $this->flash('success', $result['message']);
        } else {
            $this->flash('error', $result['message']);
        }

        $this->redirect($redirectUrl);
    }

    private function generateFee(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();
        $redirectTo = $this->input('redirect_to', 'fee_collection');
        $redirectUrl = $redirectTo === 'student_fees' 
            ? '/admin/student_fees?student_id=' . ($data['student_id'] ?? '')
            : '/admin/fee_collection';

        $validator = new FeeValidator();
        if (!$validator->validateFeeGeneration($data)) {
            $this->flash('error', $validator->firstError());
            $this->redirect($redirectUrl);
            return;
        }

        $result = $this->service->generateFee($data, $_SESSION['user_id']);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect($redirectUrl);
    }

    private function generateExamFees(): void
    {
        $this->validateCsrf();
        $classId = (int)$this->input('class_id', 0);
        $redirectTo = $this->input('redirect_to', 'fee_collection');
        
        if (!$classId) {
            $this->flash('error', 'Invalid class selected.');
            $this->redirect(moduleUrl('admin', $redirectTo));
            return;
        }

        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();
        if (!$session) {
            $this->flash('error', 'No active academic session.');
            $this->redirect(moduleUrl('admin', $redirectTo));
            return;
        }

        $classInfo = $this->db->fetch("SELECT class_name, exam_fee FROM classes WHERE class_id = ?", [$classId]);
        if (!$classInfo || $classInfo['exam_fee'] <= 0) {
            $this->flash('error', 'This class does not have an exam fee configured.');
            $this->redirect(moduleUrl('admin', $redirectTo));
            return;
        }

        $category = $this->db->fetch("SELECT category_id FROM fee_categories WHERE category_name = 'Exam Fee'");
        $categoryId = $category ? $category['category_id'] : 1; // Fallback

        $examId = (int)$this->input('exam_id', 0);
        $examName = '';
        if ($examId > 0) {
            $exam = $this->db->fetch("SELECT exam_name FROM examinations WHERE exam_id = ? AND session_id = ?", [$examId, $session['session_id']]);
            if ($exam) {
                $examName = $exam['exam_name'];
            }
        }
        
        $remarks = $examName ? "Exam Fee: " . $examName : "Exam Fee";

        // Find all active students in this class
        $students = $this->db->fetchAll("
            SELECT student_id 
            FROM student_academics 
            WHERE class_id = ? AND session_id = ? AND admission_status = 'Active'
        ", [$classId, $session['session_id']]);

        if (empty($students)) {
            $this->flash('info', 'No active students found in this class.');
            $this->redirect(moduleUrl('admin', $redirectTo));
            return;
        }

        $generatedCount = 0;
        $dueDate = date('Y-m-d', strtotime('+30 days'));
        $adminUserId = $_SESSION['user_id'] ?? 1;

        $this->db->beginTransaction();
        try {
            $insertFee = $this->db->getConnection()->prepare("
                INSERT INTO fees (student_id, session_id, category_id, exam_id, amount, due_date, payment_status, created_by, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?)
            ");

            foreach ($students as $student) {
                // Check if this exam fee already exists to prevent duplicate generation using exam_id
                if ($examId > 0) {
                    $existingFee = $this->db->fetch(
                        "SELECT fee_id FROM fees WHERE student_id = ? AND session_id = ? AND category_id = ? AND exam_id = ?",
                        [$student['student_id'], $session['session_id'], $categoryId, $examId]
                    );
                } else {
                    $existingFee = $this->db->fetch(
                        "SELECT fee_id FROM fees WHERE student_id = ? AND session_id = ? AND category_id = ? AND (exam_id IS NULL OR exam_id = 0)",
                        [$student['student_id'], $session['session_id'], $categoryId]
                    );
                }

                if (!$existingFee) {
                    $insertFee->execute([
                        $student['student_id'],
                        $session['session_id'],
                        $categoryId,
                        $examId > 0 ? $examId : null,
                        $classInfo['exam_fee'],
                        $dueDate,
                        $adminUserId,
                        $remarks
                    ]);
                    $generatedCount++;
                }
            }
            $this->db->commit();
            $this->flash('success', "Exam fee generated successfully for {$generatedCount} student(s) in {$classInfo['class_name']}.");
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Failed to generate exam fees: ' . $e->getMessage());
        }

        $this->redirect(moduleUrl('admin', $redirectTo));
    }
}
