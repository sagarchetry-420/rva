<?php
namespace App\Modules\Teacher\Controllers;

use App\Modules\Teacher\Services\TeacherService;
use App\Modules\Teacher\Validators\TeacherValidator;
use App\Modules\Teacher\Repositories\TeacherRepository;

/**
 * TeacherController — Admin CRUD for teacher management
 */
class TeacherController extends \Controller
{
    private TeacherService $service;
    private TeacherRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->service = new TeacherService();
        $this->repo = new TeacherRepository();
    }

    /**
     * List all teachers
     */
    public function index(): void
    {
        $status = $this->input('status', 'Active'); // Default to showing only active teachers
        $page = (int)$this->input('page', 1);
        $search = htmlspecialchars(trim($this->input('search', '')), ENT_QUOTES, 'UTF-8');
        
        // If status=all, fetch everything. Otherwise filter.
        $fetchStatus = $status === 'all' ? null : $status;
        $paginatedTeachers = $this->repo->findAll($fetchStatus, $page, 20, $search);

        $this->render('Modules/Teacher/Views/index', [
            'pageTitle'     => 'Teachers Management',
            'teachers'      => $paginatedTeachers['data'],
            'pagination'    => $paginatedTeachers,
            'currentStatus' => $status,
            'searchQuery'   => $search
        ], 'admin');
    }

    /**
     * Show dedicated teacher details page
     */
    public function details(): void
    {
        $teacherId = (int)$this->input('id', 0);
        if (!$teacherId) {
            $this->flash('error', 'Invalid teacher ID.');
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        $teacher = $this->repo->findById($teacherId);
        if (!$teacher) {
            $this->flash('error', 'Teacher not found.');
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        $this->render('Modules/Teacher/Views/details', [
            'pageTitle' => 'Teacher Profile',
            'teacher'   => $teacher
        ], 'admin');
    }

    /**
     * Handle POST actions (add, edit, delete, export_csv)
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
            case 'deactivate':
                $this->deactivate();
                break;
            case 'reactivate':
                $this->reactivate();
                break;
            case 'export_csv':
                $this->exportCsv();
                break;
            case 'export_pdf':
                $this->exportPdf();
                break;
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('admin', 'teachers'));
        }
    }

    /**
     * Add a new teacher
     */
    private function store(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        // Validate
        $validator = new TeacherValidator();
        if (!$validator->validate($data)) {
            $this->flash('error', $validator->firstError());
            setOldInput($data);
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        // Create via service
        $result = $this->service->createTeacher($data);

        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'teachers'));
    }

    /**
     * Update an existing teacher
     */
    private function update(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();
        $teacherId = (int)($data['teacher_id'] ?? 0);

        if (!$teacherId) {
            $this->flash('error', 'Invalid teacher ID.');
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        // Get current teacher to find associated user_id
        $current = $this->repo->findById($teacherId);
        if (!$current) {
            $this->flash('error', 'Teacher not found.');
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        // Validate
        $validator = new TeacherValidator();
        if (!$validator->validate($data, $teacherId, $current['user_id'])) {
            $this->flash('error', $validator->firstError());
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        // Update via service
        $result = $this->service->updateTeacher($teacherId, $data);

        $type = $result['success'] ? (($result['no_change'] ?? false) ? 'info' : 'success') : 'error';
        $this->flash($type, $result['message']);
        
        $origin = $this->input('origin', '');
        if ($origin === 'details') {
            $this->redirect(moduleUrl('admin', 'teacher_details', ['id' => $teacherId]));
        } else {
            $this->redirect(moduleUrl('admin', 'teachers'));
        }
    }

    /**
     * Delete a teacher
     */
    private function destroy(): void
    {
        $this->validateCsrf();
        $teacherId = (int)$this->input('teacher_id', 0);
        $confirmName = trim($this->input('confirm_teacher_name', ''));

        if (!$teacherId) {
            $this->flash('error', 'Invalid teacher ID.');
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        // Fetch teacher to compare full name
        $teacher = $this->repo->findById($teacherId);
        if (!$teacher) {
            $this->flash('error', 'Teacher not found.');
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        $expectedName = trim($teacher['first_name'] . ' ' . $teacher['last_name']);

        // Case-insensitive comparison
        if (strcasecmp($confirmName, $expectedName) !== 0) {
            $this->flash('error', 'Deletion failed: Confirmation name did not match.');
            $this->redirect(moduleUrl('admin', 'teacher_details', ['id' => $teacherId]));
            return;
        }

        $result = $this->service->deleteTeacher($teacherId);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'teachers'));
    }

    /**
     * Export teachers to CSV
     */
    private function exportCsv(): void
    {
        $status = $this->input('status', 'Active');
        $fetchStatus = $status === 'all' ? null : $status;
        $search = htmlspecialchars(trim($this->input('search', '')), ENT_QUOTES, 'UTF-8');
        
        $this->service->exportCsv($fetchStatus, $search);
    }

    /**
     * Export teachers to PDF
     */
    private function exportPdf(): void
    {
        $status = $this->input('status', 'Active');
        $fetchStatus = $status === 'all' ? null : $status;
        $search = htmlspecialchars(trim($this->input('search', '')), ENT_QUOTES, 'UTF-8');
        
        $this->service->exportPdf($fetchStatus, $search);
    }

    /**
     * Deactivate a teacher
     */
    private function deactivate(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        $validator = new \App\Modules\Teacher\Validators\TeacherValidator();
        if (!$validator->validateDeactivate($data)) {
            $this->flash('error', $validator->firstError());
            $origin = $data['origin'] ?? '';
            $teacherId = (int)($data['teacher_id'] ?? 0);
            if ($origin === 'details' && $teacherId) {
                $this->redirect(moduleUrl('admin', 'teacher_details', ['id' => $teacherId]));
            } else {
                $this->redirect(moduleUrl('admin', 'teachers'));
            }
            return;
        }

        $teacherId = (int)$data['teacher_id'];
        $leavingDate = $data['leaving_date'];
        $leavingReason = trim($data['leaving_reason']);

        $result = $this->service->deactivateTeacher($teacherId, $leavingDate, $leavingReason);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        
        $origin = $data['origin'] ?? '';
        if ($origin === 'details') {
            $this->redirect(moduleUrl('admin', 'teacher_details', ['id' => $teacherId]));
        } else {
            $this->redirect(moduleUrl('admin', 'teachers'));
        }
    }

    /**
     * Reactivate a teacher
     */
    private function reactivate(): void
    {
        $this->validateCsrf();
        $teacherId = (int)$this->input('teacher_id', 0);

        if (!$teacherId) {
            $this->flash('error', 'Invalid teacher ID.');
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        $result = $this->service->reactivateTeacher($teacherId);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        
        $origin = $this->input('origin', '');
        if ($origin === 'details') {
            $this->redirect(moduleUrl('admin', 'teacher_details', ['id' => $teacherId]));
        } else {
            $this->redirect(moduleUrl('admin', 'teachers'));
        }
    }
}
