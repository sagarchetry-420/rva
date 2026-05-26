<?php
namespace App\Modules\Academic\Controllers;

use App\Modules\Academic\Services\AcademicService;
use App\Modules\Academic\Validators\AcademicValidator;
use App\Modules\Academic\Repositories\SubjectRepository;

/**
 * SubjectController — Admin CRUD for subject management
 */
class SubjectController extends \Controller
{
    private AcademicService $service;
    private SubjectRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AcademicService();
        $this->repo = new SubjectRepository();
    }

    public function index(): void
    {
        $subjects = $this->repo->findAll();

        $this->render('Modules/Academic/Views/subjects', [
            'pageTitle' => 'Subjects Management',
            'subjects'  => $subjects
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
                $this->redirect(moduleUrl('admin', 'subjects'));
        }
    }

    private function store(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        $validator = new AcademicValidator();
        if (!$validator->validateSubject($data)) {
            $this->flash('error', $validator->firstError());
            setOldInput($data);
            $this->redirect(moduleUrl('admin', 'subjects'));
            return;
        }

        $result = $this->service->createSubject($data);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'subjects'));
    }

    private function update(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();
        $subjectId = (int)($data['subject_id'] ?? 0);

        if (!$subjectId) {
            $this->flash('error', 'Invalid subject ID.');
            $this->redirect(moduleUrl('admin', 'subjects'));
            return;
        }

        $validator = new AcademicValidator();
        if (!$validator->validateSubject($data, $subjectId)) {
            $this->flash('error', $validator->firstError());
            $this->redirect(moduleUrl('admin', 'subjects'));
            return;
        }

        $result = $this->service->updateSubject($subjectId, $data);
        $type = $result['success'] ? (($result['no_change'] ?? false) ? 'info' : 'success') : 'error';
        $this->flash($type, $result['message']);
        $this->redirect(moduleUrl('admin', 'subjects'));
    }

    private function destroy(): void
    {
        $this->validateCsrf();
        $subjectId = (int)$this->input('subject_id', 0);

        if (!$subjectId) {
            $this->flash('error', 'Invalid subject ID.');
            $this->redirect(moduleUrl('admin', 'subjects'));
            return;
        }

        $result = $this->service->deleteSubject($subjectId);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect(moduleUrl('admin', 'subjects'));
    }
}
