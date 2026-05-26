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
        $page = $this->input('page', 1);
        
        // If status=all, fetch everything. Otherwise filter.
        $fetchStatus = $status === 'all' ? null : $status;
        $paginatedTeachers = $this->repo->findAll($fetchStatus, $page, 20);

        $this->render('Modules/Teacher/Views/index', [
            'pageTitle'     => 'Teachers Management',
            'teachers'      => $paginatedTeachers['data'],
            'pagination'    => $paginatedTeachers,
            'currentStatus' => $status
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
        $this->redirect(moduleUrl('admin', 'teachers'));
    }

    /**
     * Delete a teacher
     */
    private function destroy(): void
    {
        $this->validateCsrf();
        $teacherId = (int)$this->input('teacher_id', 0);

        if (!$teacherId) {
            $this->flash('error', 'Invalid teacher ID.');
            $this->redirect(moduleUrl('admin', 'teachers'));
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
        $this->service->exportCsv();
    }
    /**
     * Deactivate a teacher
     */
    private function deactivate(): void
    {
        $this->validateCsrf();
        $teacherId = (int)$this->input('teacher_id', 0);
        $leavingDate = $this->input('leaving_date', date('Y-m-d'));
        $leavingReason = trim($this->input('leaving_reason', ''));

        if (!$teacherId || empty($leavingDate) || empty($leavingReason)) {
            $this->flash('error', 'Invalid deactivation data provided.');
            $this->redirect(moduleUrl('admin', 'teachers'));
            return;
        }

        $result = $this->service->deactivateTeacher($teacherId, $leavingDate, $leavingReason);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'teachers'));
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
        $this->redirect(moduleUrl('admin', 'teachers'));
    }
}
