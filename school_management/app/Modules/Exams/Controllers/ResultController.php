<?php
namespace App\Modules\Exams\Controllers;

use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Repositories\ResultRepository;
use App\Modules\Exams\Repositories\ExamRepository;
use App\Modules\Academic\Repositories\ClassRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;

/**
 * ResultController — Handles bulk result entry by teachers
 */
class ResultController extends \Controller
{
    private ExamService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ExamService();
    }

    public function index(): void
    {
        $examId = (int)$this->input('exam_id', 0);
        $classId = (int)$this->input('class_id', 0);
        $scheduleId = (int)$this->input('schedule_id', 0);

        $academicRepo = new ClassSubjectRepository();
        $session = $academicRepo->getActiveSession();

        $examRepo = new ExamRepository();
        $exams = $session ? $examRepo->findAllBySession($session['session_id']) : [];

        // For simplicity, fetch all classes. Ideally filter by teacher's assigned classes
        $classRepo = new ClassRepository();
        $classes = $classRepo->findAll();

        $scheduleDetails = null;
        $students = [];

        if ($classId && $scheduleId && $session) {
            $resultRepo = new ResultRepository();
            $scheduleDetails = $resultRepo->getScheduleDetails($scheduleId);
            $students = $resultRepo->getResultsBySchedule($classId, $session['session_id'], $scheduleId);
        }

        $this->render('Modules/Exams/Views/results', [
            'pageTitle'       => 'Enter Marks',
            'exams'           => $exams,
            'classes'         => $classes,
            'filterExam'      => $examId,
            'filterClass'     => $classId,
            'filterSchedule'  => $scheduleId,
            'scheduleDetails' => $scheduleDetails,
            'students'        => $students
        ], $_SESSION['user_type']); // Support both admin and teacher layouts
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');

        switch ($action) {
            case 'save_results':
                $this->saveResults();
                break;
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('exams', 'results'));
        }
    }

    private function saveResults(): void
    {
        $this->validateCsrf();
        $data = $this->allInput();
        $scheduleId = (int)$data['schedule_id'];

        if (!$scheduleId || empty($data['results'])) {
            $this->flash('error', 'Invalid data submitted.');
            $this->redirect(moduleUrl('exams', 'results'));
            return;
        }

        $result = $this->service->saveBulkResults($scheduleId, $data['results'], $_SESSION['user_id']);

        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/exams/results?exam_id=' . $data['exam_id'] . '&class_id=' . $data['class_id'] . '&schedule_id=' . $scheduleId);
    }
}
