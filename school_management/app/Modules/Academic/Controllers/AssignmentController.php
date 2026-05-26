<?php
namespace App\Modules\Academic\Controllers;

use App\Modules\Academic\Services\AcademicService;
use App\Modules\Academic\Validators\AcademicValidator;
use App\Modules\Academic\Repositories\ClassRepository;
use App\Modules\Academic\Repositories\SubjectRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;
use App\Modules\Teacher\Repositories\TeacherRepository;

/**
 * AssignmentController — Manages assigning subjects and teachers to classes
 */
class AssignmentController extends \Controller
{
    private AcademicService $service;
    private ClassSubjectRepository $assignmentRepo;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AcademicService();
        $this->assignmentRepo = new ClassSubjectRepository();
    }

    public function index(): void
    {
        $classId = (int)$this->input('class_id', 0);
        
        $classRepo = new ClassRepository();
        $classes = $classRepo->findAll();
        
        $session = $this->assignmentRepo->getActiveSession();
        $assignments = [];

        if ($classId && $session) {
            $assignments = $this->assignmentRepo->findByClass($classId, $session['session_id']);
        }

        $subjectRepo = new SubjectRepository();
        $subjects = $subjectRepo->findAll();

        $teacherRepo = new TeacherRepository();
        $teachersResult = $teacherRepo->findAll('Active');
        $teachers = $teachersResult['data'] ?? [];

        $this->render('Modules/Academic/Views/assignments', [
            'pageTitle'   => 'Class Subjects & Teacher Assignment',
            'classes'     => $classes,
            'subjects'    => $subjects,
            'teachers'    => $teachers,
            'assignments' => $assignments,
            'filterClass' => $classId,
            'session'     => $session
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');

        switch ($action) {
            case 'assign':
                $this->assign();
                break;
            case 'remove':
                $this->remove();
                break;
            case 'update_teacher':
                $this->updateTeacher();
                break;
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('admin', 'assignments'));
        }
    }

    private function assign(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();

        $validator = new AcademicValidator();
        if (!$validator->validateAssignment($data)) {
            $this->flash('error', $validator->firstError());
            $this->redirect('/admin/assignments?class_id=' . ($data['class_id'] ?? ''));
            return;
        }

        $result = $this->service->assignSubjectToClass(
            (int)$data['class_id'], 
            (int)$data['subject_id'], 
            empty($data['teacher_id']) ? null : (int)$data['teacher_id']
        );

        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/admin/assignments?class_id=' . $data['class_id']);
    }

    private function remove(): void
    {
        $this->validateCsrf();
        $assignmentId = (int)$this->input('assignment_id', 0);
        $classId = (int)$this->input('class_id', 0);

        if (!$assignmentId) {
            $this->flash('error', 'Invalid assignment ID.');
            $this->redirect('/admin/assignments?class_id=' . $classId);
            return;
        }

        $result = $this->service->removeAssignment($assignmentId);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/admin/assignments?class_id=' . $classId);
    }

    private function updateTeacher(): void
    {
        $this->validateCsrf();
        $assignmentId = (int)$this->input('assignment_id', 0);
        $classId = (int)$this->input('class_id', 0);
        $teacherId = $this->input('teacher_id', '');
        
        $teacherId = $teacherId === '' ? null : (int)$teacherId;

        if (!$assignmentId) {
            $this->flash('error', 'Invalid assignment ID.');
            $this->redirect('/admin/assignments?class_id=' . $classId);
            return;
        }

        $result = $this->service->updateAssignmentTeacher($assignmentId, $teacherId);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/admin/assignments?class_id=' . $classId);
    }
}
