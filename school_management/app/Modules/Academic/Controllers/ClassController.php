<?php
namespace App\Modules\Academic\Controllers;

use App\Modules\Academic\Services\AcademicService;
use App\Modules\Academic\Validators\AcademicValidator;
use App\Modules\Academic\Repositories\ClassRepository;
use App\Modules\Teacher\Repositories\TeacherRepository;

/**
 * ClassController — Admin CRUD for class management
 */
class ClassController extends \Controller
{
    private AcademicService $service;
    private ClassRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AcademicService();
        $this->repo = new ClassRepository();
    }

    public function index(): void
    {
        $classes = $this->repo->findAll();

        $teacherRepo = new TeacherRepository();
        $paginatedTeachers = $teacherRepo->findAll('Active');
        $teachers = $paginatedTeachers['data'] ?? [];

        $this->render('Modules/Academic/Views/classes', [
            'pageTitle' => 'Classes Management',
            'classes'   => $classes,
            'teachers'  => $teachers
        ], 'admin');
    }

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
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('admin', 'classes'));
        }
    }

    private function store(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        $validator = new AcademicValidator();
        if (!$validator->validateClass($data)) {
            $this->flash('error', $validator->firstError());
            setOldInput($data);
            $this->redirect(moduleUrl('admin', 'classes'));
            return;
        }

        $result = $this->service->createClass($data);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'classes'));
    }

    private function update(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();
        $classId = (int)($data['class_id'] ?? 0);

        if (!$classId) {
            $this->flash('error', 'Invalid class ID.');
            $this->redirect(moduleUrl('admin', 'classes'));
            return;
        }

        $validator = new AcademicValidator();
        if (!$validator->validateClass($data, $classId)) {
            $this->flash('error', $validator->firstError());
            $this->redirect(moduleUrl('admin', 'classes'));
            return;
        }

        $result = $this->service->updateClass($classId, $data);
        $type = $result['success'] ? (($result['no_change'] ?? false) ? 'info' : 'success') : 'error';
        $this->flash($type, $result['message']);
        $this->redirect(moduleUrl('admin', 'classes'));
    }

    private function destroy(): void
    {
        $this->validateCsrf();
        $classId = (int)$this->input('class_id', 0);

        if (!$classId) {
            $this->flash('error', 'Invalid class ID.');
            $this->redirect(moduleUrl('admin', 'classes'));
            return;
        }

        $result = $this->service->deleteClass($classId);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'classes'));
    }
}
