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
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $paginatedStudents = $this->repo->findAll($filterClass > 0 ? $filterClass : null, $page, 20, $search);
        $classes = $this->db->fetchAll("SELECT * FROM classes ORDER BY LENGTH(class_name), class_name");
        $services = $this->db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");

        $this->render('Modules/Student/Views/index', [
            'pageTitle'   => 'Students Management',
            'students'    => $paginatedStudents['data'],
            'pagination'  => $paginatedStudents,
            'classes'     => $classes,
            'services'    => $services,
            'filterClass' => $filterClass,
            'searchQuery' => $search,
        ], 'admin');
    }

    /**
     * Show dedicated student details page
     */
    public function details(): void
    {
        $studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$studentId) {
            $this->flash('error', 'Invalid student ID.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        $student = $this->repo->findById($studentId);
        if (!$student) {
            $this->flash('error', 'Student not found.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        $classes = $this->db->fetchAll("SELECT * FROM classes ORDER BY LENGTH(class_name), class_name");
        $services = $this->db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");
        
        // Apply class-specific fee overrides for the student's current class
        $classId = (int)($student['current_class_id'] ?? $student['class_id'] ?? 0);
        if ($classId) {
            $overridesData = $this->db->fetchAll("SELECT service_id, fee_amount FROM class_service_fees WHERE class_id = ?", [$classId]);
            $overrides = [];
            foreach ($overridesData as $row) {
                $overrides[$row['service_id']] = (float)$row['fee_amount'];
            }
            foreach ($services as &$service) {
                $sId = $service['service_id'];
                if (isset($overrides[$sId])) {
                    $service['fee_amount'] = $overrides[$sId];
                }
            }
            unset($service);
        }
        
        $session = $this->db->fetch("SELECT session_id FROM academic_sessions WHERE is_current = 1");
        $sessionId = $session ? $session['session_id'] : 0;
        
        $assignedServices = [];
        if ($sessionId) {
            $assignedServicesData = $this->db->fetchAll("SELECT service_id, end_date FROM student_services WHERE student_id = ? AND session_id = ? AND is_active = 1", [$studentId, $sessionId]);
            $assignedServices = array_column($assignedServicesData, 'end_date', 'service_id');
        }

        // Calculate total pending dues
        $pendingDuesRow = $this->db->fetch("SELECT SUM(amount) as total_due FROM fees WHERE student_id = ? AND payment_status != 'Paid'", [$studentId]);
        $pendingDues = $pendingDuesRow ? (float)$pendingDuesRow['total_due'] : 0.0;

        $this->render('Modules/Student/Views/details', [
            'pageTitle'        => 'Student Profile',
            'student'          => $student,
            'classes'          => $classes,
            'services'         => $services,
            'assignedServices' => $assignedServices,
            'pendingDues'      => $pendingDues
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
            case 'export_pdf':
                $this->exportPdf();
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
            case 'deactivate_service':
                $this->deactivateService();
                break;
            case 'get_student_services':
                $this->getStudentServices();
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
            $this->redirect(moduleUrl('admin', 'student_fees', ['student_id' => $result['student_id']]));
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
        
        $origin = $this->input('origin', '');
        if ($origin === 'details') {
            $this->redirect(moduleUrl('admin', 'student_details', ['id' => $studentId]));
        } else {
            $this->redirect(moduleUrl('admin', 'students'));
        }
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
     * Export students to PDF
     */
    private function exportPdf(): void
    {
        $filterClass = (int)$this->input('filter_class', 0);
        $this->service->exportPdf($filterClass > 0 ? $filterClass : null);
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

        $student = $this->repo->findById($studentId);
        if ($student && !empty($student['leaving_date'])) {
            $this->flash('error', 'Student is already marked as withdrawn/inactive.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        if (strtotime($leavingDate) < strtotime(date('Y-m-d'))) {
            $this->flash('error', 'Leaving date cannot be in the past.');
            $origin = $this->input('origin', '');
            if ($origin === 'details') {
                $this->redirect(moduleUrl('admin', 'student_details', ['id' => $studentId]));
            } else {
                $this->redirect(moduleUrl('admin', 'students'));
            }
            return;
        }

        $result = $this->service->withdrawStudent($studentId, $leavingDate, $leavingReason);

        if ($result['success']) {
            $this->flash('success', $result['message']);
        } else {
            $this->flash('error', $result['message']);
        }
        
        $origin = $this->input('origin', '');
        if ($origin === 'details') {
            $this->redirect(moduleUrl('admin', 'student_details', ['id' => $studentId]));
        } else {
            $this->redirect(moduleUrl('admin', 'students'));
        }
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
        
        $headers = [
            'First Name', 'Last Name', 'Gender (Male/Female/Other)', 'Date of Birth (YYYY-MM-DD)', 
            'Email', 'Phone', 'Parent Name', 'Parent Phone', 
            'Admission Fee Paid (Yes/No)', 'Admission Payment Method (Cash/Card/Online)', 'Admission Transaction ID / Remarks'
        ];
        
        $instructions = [
            'Required (Letters only)', 'Required (Letters only)', 'Type Male, Female, or Other', 'Format: YYYY-MM-DD (e.g. 2015-05-20)', 
            'Required (Valid email)', 'Optional (Exactly 10 digits)', 'Optional (Letters only)', 'Required (Exactly 10 digits)', 
            'Type Yes if paid. Type No if unpaid.', 'Leave blank if No. Type Cash, Card, or Online if Yes.', 'Leave blank if No. Enter Txn ID if Yes.'
        ];

        // Dynamically append active services as individual Yes/No columns
        $services = $this->db->fetchAll("SELECT service_name FROM services ORDER BY service_name ASC");
        if ($services) {
            foreach ($services as $service) {
                $headers[] = 'Service: ' . $service['service_name'] . ' (Yes/No)';
                $instructions[] = 'Type Yes to enroll & charge. Type No to ignore.';
            }
        }
        
        // Append global payment columns for services at the very end
        $headers[] = 'Services Payment Method (Cash/Card/Online)';
        $instructions[] = 'Payment method for all services taken.';
        
        $headers[] = 'Services Transaction ID / Remarks';
        $instructions[] = 'Transaction ID/Remarks for all services taken.';
        
        // Write top warning row
        fputcsv($output, ['*** PLEASE READ INSTRUCTIONS ON ROW 2 ***', 'DO NOT DELETE THE TOP 3 ROWS! Enter your actual student data starting on Row 4.']);
        // Write Instructions row
        fputcsv($output, $instructions);
        // Write Header row
        fputcsv($output, $headers);
        
        fclose($output);
        exit;
    }

    /**
     * AJAX: Get student assigned services
     */
    private function getStudentServices(): void
    {
        $studentId = (int)$this->input('student_id', 0);
        $session = $this->db->fetch("SELECT session_id FROM academic_sessions WHERE is_current = 1");
        if (!$session || !$studentId) {
            $this->json(['services' => []]);
            return;
        }
        $services = $this->db->fetchAll("SELECT service_id FROM student_services WHERE student_id = ? AND session_id = ?", [$studentId, $session['session_id']]);
        $this->json(['services' => array_column($services, 'service_id')]);
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

        $student = $this->repo->findById($studentId);
        if ($student && !empty($student['leaving_date'])) {
            $this->flash('error', 'Action not permitted for inactive students.');
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

            // Instead of deleting, we only add new ones or reactivate old ones
            $checkService = $this->db->getConnection()->prepare("SELECT is_active FROM student_services WHERE student_id = ? AND service_id = ? AND session_id = ?");
            $updateService = $this->db->getConnection()->prepare("UPDATE student_services SET is_active = 1, end_date = NULL WHERE student_id = ? AND service_id = ? AND session_id = ?");
            $insertService = $this->db->getConnection()->prepare("INSERT INTO student_services (student_id, service_id, session_id, enrollment_date, is_active, end_date) VALUES (?, ?, ?, CURDATE(), 1, NULL)");
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

                $checkService->execute([$studentId, $sId, $sessionId]);
                $existing = $checkService->fetch(\PDO::FETCH_ASSOC);

                if ($existing) {
                    if ($existing['is_active'] == 0) {
                        $updateService->execute([$studentId, $sId, $sessionId]);
                    } else {
                        // Already active, skip
                        continue;
                    }
                } else {
                    $insertService->execute([$studentId, $sId, $sessionId]);
                }

                // Auto bill
                $srv = $this->db->fetch("SELECT fee_amount FROM services WHERE service_id = ?", [$sId]);
                if ($srv) {
                    $classId = (int)($student['current_class_id'] ?? 0);
                    $override = $this->db->fetch("SELECT fee_amount FROM class_service_fees WHERE service_id = ? AND class_id = ?", [$sId, $classId]);
                    $finalFeeAmount = $override ? (float)$override['fee_amount'] : (float)$srv['fee_amount'];
                    
                    if ($finalFeeAmount > 0) {
                        $insertFee->execute([
                            $studentId,
                            $sessionId,
                            $sId,
                            $categoryId,
                            $finalFeeAmount,
                            $today,
                            $_SESSION['user_id']
                        ]);
                    }
                }
            }

            $this->db->commit();
            $this->flash('success', 'Services assigned and fees generated successfully.');
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->flash('error', 'Failed to assign services: ' . $e->getMessage());
        }

        $origin = $this->input('origin', '');
        if ($origin === 'details') {
            $this->redirect(moduleUrl('admin', 'student_details', ['id' => $studentId]));
        } else {
            $this->redirect(moduleUrl('admin', 'students'));
        }
    }

    /**
     * Deactivate a service for a student with a scheduled end date
     */
    private function deactivateService(): void
    {
        $this->validateCsrf();
        $studentId = (int)$this->input('student_id', 0);
        $serviceId = (int)$this->input('service_id', 0);
        
        $isCancel = !empty($_POST['cancel_deactivation']);
        $endDate = $isCancel ? null : $this->input('end_date', date('Y-m-d'));

        if (!$studentId || !$serviceId || empty($endDate) && !$isCancel) {
            $this->flash('error', 'Missing required deactivation data.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        $student = $this->repo->findById($studentId);
        if ($student && !empty($student['leaving_date'])) {
            $this->flash('error', 'Action not permitted for inactive students.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        if (!$isCancel && strtotime($endDate) < strtotime(date('Y-m-d'))) {
            $this->flash('error', 'Deactivation date cannot be in the past.');
            $this->redirect(moduleUrl('admin', 'student_details', ['id' => $studentId]));
            return;
        }

        $session = $this->db->fetch("SELECT session_id FROM academic_sessions WHERE is_current = 1");
        if (!$session) {
            $this->flash('error', 'No active academic session.');
            $this->redirect(moduleUrl('admin', 'students'));
            return;
        }

        try {
            if ($isCancel) {
                // Cancel the scheduled deactivation
                $stmt = $this->db->getConnection()->prepare("UPDATE student_services SET end_date = NULL, is_active = 1 WHERE student_id = ? AND service_id = ? AND session_id = ?");
                $stmt->execute([$studentId, $serviceId, $session['session_id']]);
                $this->flash('success', 'Service deactivation schedule has been canceled.');
            } else {
                // Schedule or immediately deactivate
                $isActive = (strtotime($endDate) <= strtotime('today')) ? 0 : 1;
                $stmt = $this->db->getConnection()->prepare("UPDATE student_services SET end_date = ?, is_active = ? WHERE student_id = ? AND service_id = ? AND session_id = ?");
                $stmt->execute([$endDate, $isActive, $studentId, $serviceId, $session['session_id']]);
                $this->flash('success', 'Service deactivation scheduled successfully.');
            }
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to update service: ' . $e->getMessage());
        }

        $this->redirect(moduleUrl('admin', 'student_details', ['id' => $studentId]));
    }
}
