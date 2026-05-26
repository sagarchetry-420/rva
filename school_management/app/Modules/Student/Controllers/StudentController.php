<?php
namespace App\Modules\Student\Controllers;

use App\Modules\Student\Services\StudentService;
use App\Modules\Student\Validators\StudentValidator;
use App\Modules\Student\Repositories\StudentRepository;

/**
 * StudentController — Admin CRUD for student management
 */
class StudentController extends \Controller
{
    private StudentService $service;
    private StudentRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->service = new StudentService();
        $this->repo = new StudentRepository();
    }

    /**
     * List all students with class filter
     */
    public function index(): void
    {
        $filterClass = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        $paginatedStudents = $this->repo->findAll($filterClass > 0 ? $filterClass : null, $page, 20);
        $classes = $this->db->fetchAll("SELECT * FROM classes ORDER BY class_name");
        $services = $this->db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");

        $this->render('Modules/Student/Views/index', [
            'pageTitle'   => 'Students Management',
            'students'    => $paginatedStudents['data'],
            'pagination'  => $paginatedStudents,
            'classes'     => $classes,
            'services'    => $services,
            'filterClass' => $filterClass,
        ], 'admin');
    }

    /**
     * Handle POST actions (add, edit, delete, export, generate_roll_number)
     */
    public function handleAction(): void
    {
        $action = $this->input('action', '');

        switch ($action) {
            case 'add':
                $this->store();
                break;
            case 'edit':
                $this->update();
                break;
            case 'delete':
                $this->destroy();
                break;
            case 'withdraw':
                $this->withdraw();
                break;
            case 'export_csv':
                $this->exportCsv();
                break;
            case 'generate_roll_number':
                $this->generateRollNumber();
                break;
            case 'import_csv':
                $this->importCsv();
                break;
            case 'download_template':
                $this->downloadTemplate();
                break;
            case 'assign_services':
                $this->assignServices();
                break;
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('admin', 'students'));
        }
    }

    /**
     * Add a new student
     */
    private function store(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        // Validate
        $validator = new StudentValidator();
        if (!$validator->validate($data)) {
            $this->flash('error', $validator->firstError());
            setOldInput($data);
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        // Create via service
        $result = $this->service->createStudent($data);

        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        if ($result['success']) {
            $this->redirect('/admin/fee_collection?student_id=' . $result['student_id']);
        } else {
            $this->redirect(moduleUrl('admin', 'students'));
        }
    }

    /**
     * Update an existing student
     */
    private function update(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();
        $studentId = (int)($data['student_id'] ?? 0);

        if (!$studentId) {
            $this->flash('error', 'Invalid student ID.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        // Get current student for user_id
        $current = $this->repo->findById($studentId);
        if (!$current) {
            $this->flash('error', 'Student not found.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        // Validate
        $validator = new StudentValidator();
        if (!$validator->validate($data, $studentId, $current['user_id'])) {
            $this->flash('error', $validator->firstError());
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        // Update via service
        $result = $this->service->updateStudent($studentId, $data);

        $type = $result['success'] ? (($result['no_change'] ?? false) ? 'info' : 'success') : 'error';
        $this->flash($type, $result['message']);
        $this->redirect(moduleUrl('admin', 'students'));
    }

    /**
     * Delete a student
     */
    private function destroy(): void
    {
        $this->validateCsrf();
        $studentId = (int)$this->input('student_id', 0);

        if (!$studentId) {
            $this->flash('error', 'Invalid student ID.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        $result = $this->service->deleteStudent($studentId);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'students'));
    }

    /**
     * Export students to CSV
     */
    private function exportCsv(): void
    {
        $filterClass = (int)$this->input('filter_class', 0);
        $this->service->exportCsv($filterClass > 0 ? $filterClass : null);
    }

    /**
     * Withdraw a student
     */
    private function withdraw(): void
    {
        $this->validateCsrf();
        $studentId = (int)$this->input('student_id', 0);
        $leavingDate = $this->input('leaving_date', date('Y-m-d'));
        $leavingReason = trim($this->input('leaving_reason', ''));

        if (!$studentId || empty($leavingDate) || empty($leavingReason)) {
            $this->flash('error', 'Invalid withdrawal data provided.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        $result = $this->service->withdrawStudent($studentId, $leavingDate, $leavingReason);

        if ($result['success']) {
            $this->flash('success', $result['message']);
        } else {
            $this->flash('error', $result['message']);
        }
        $this->redirect(moduleUrl('admin', 'students'));
    }

    /**
     * AJAX: Generate roll number for a class
     */
    private function generateRollNumber(): void
    {
        $classId = (int)$this->input('class_id', 0);
        $rollNumber = $this->service->generateRollNumber($classId);
        $this->json(['roll_number' => $rollNumber]);
    }

    /**
     * Import students from CSV
     */
    private function importCsv(): void
    {
        $this->validateCsrf();
        $classId = (int)$this->input('class_id', 0);
        $sendEmails = (bool)$this->input('send_emails', false);
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please upload a valid CSV file.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        if ($classId <= 0) {
            $this->flash('error', 'Please select a valid class for the imported students.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        $result = $this->service->importCsv($_FILES['csv_file'], $classId, $sendEmails);
        
        if ($result['success']) {
            $this->flash('success', $result['message']);
        } else {
            $this->flash('error', $result['message']);
        }
        
        $this->redirect(moduleUrl('admin', 'students'));
    }

    /**
     * Download CSV Template for bulk import
     */
    private function downloadTemplate(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=student_import_template.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['First Name', 'Last Name', 'Gender (Male/Female/Other)', 'Date of Birth (YYYY-MM-DD)', 'Email', 'Phone', 'Parent Name', 'Parent Phone']);
        fclose($output);
        exit;
    }

    /**
     * Assign services to student
     */
    private function assignServices(): void
    {
        $this->validateCsrf();
        $studentId = (int)$this->input('student_id', 0);
        $serviceIds = $_POST['service_ids'] ?? [];

        if (!$studentId) {
            $this->flash('error', 'Invalid student ID.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        $session = $this->db->fetch("SELECT session_id FROM academic_sessions WHERE is_current = 1");
        if (!$session) {
            $this->flash('error', 'No active academic session.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }
        $sessionId = $session['session_id'];

        try {
            $this->db->beginTransaction();

            // Clear current services
            $stmt = $this->db->getConnection()->prepare("DELETE FROM student_services WHERE student_id = ? AND session_id = ?");
            $stmt->execute([$studentId, $sessionId]);

            // Add selected services and generate automatic fees
            $insertService = $this->db->getConnection()->prepare("INSERT INTO student_services (student_id, service_id, session_id, is_active) VALUES (?, ?, ?, 1)");
            $insertFee = $this->db->getConnection()->prepare("INSERT INTO fees (student_id, session_id, service_id, category_id, amount, due_date, payment_status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)");

            $today = date('Y-m-d', strtotime('+30 days'));
            $category = $this->db->fetch("SELECT category_id FROM fee_categories WHERE category_name = 'Service Fee' LIMIT 1");
            if (!$category) {
                $this->db->insert('fee_categories', [
                    'category_name' => 'Service Fee',
                    'description' => 'Charges for additional student services',
                    'is_active' => 1
                ]);
                $categoryId = (int)$this->db->getConnection()->lastInsertId();
            } else {
                $categoryId = (int)$category['category_id'];
            }

            foreach ($serviceIds as $sId) {
                $sId = (int)$sId;
                if ($sId <= 0) continue;

                $insertService->execute([$studentId, $sId, $sessionId]);

                // Auto bill
                $srv = $this->db->fetch("SELECT fee_amount FROM services WHERE service_id = ?", [$sId]);
                if ($srv && $srv['fee_amount'] > 0) {
                    $insertFee->execute([
                        $studentId,
                        $sessionId,
                        $sId,
                        $categoryId,
                        $srv['fee_amount'],
                        $today,
                        $_SESSION['user_id']
                    ]);
                }
            }

            $this->db->commit();
            $this->flash('success', 'Services assigned and fees generated successfully.');
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Failed to assign services: ' . $e->getMessage());
        }

        $this->redirect(moduleUrl('admin', 'students'));
    }
}
