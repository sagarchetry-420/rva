<?php
namespace App\Modules\Academic\Controllers;

use App\Modules\Academic\Repositories\AcademicSessionRepository;

/**
 * AcademicSessionController — Manage academic sessions (create, edit, set active)
 */
class AcademicSessionController extends \Controller
{
    private AcademicSessionRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new AcademicSessionRepository();
    }

    public function index(): void
    {
        $sessions = $this->repo->findAll();

        $this->render('Modules/Academic/Views/academic_sessions', [
            'pageTitle' => 'Academic Sessions',
            'sessions'  => $sessions
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');

        if ($action === 'create') {
            $this->createSession();
        } elseif ($action === 'update') {
            $this->updateSession();
        } elseif ($action === 'set_active') {
            $this->setActiveSession();
        } elseif ($action === 'delete') {
            $this->deleteSession();
        } else {
            $this->flash('info', 'Unknown action.');
            $this->redirect(moduleUrl('admin', 'academic_sessions'));
        }
    }

    private function createSession(): void
    {
        $this->validateCsrf();

        $name = trim($this->input('session_name', ''));
        $startMonth = (int)$this->input('start_month', 0);
        $startYear  = (int)$this->input('start_year', 0);
        $endMonth   = (int)$this->input('end_month', 0);
        $endYear    = (int)$this->input('end_year', 0);

        $description = trim($this->input('description', ''));
        $isActive = (int)$this->input('is_current', 0);

        $startDate = '';
        $endDate = '';

        if ($startMonth && $startYear) {
            $startDate = sprintf('%04d-%02d-01', $startYear, $startMonth);
        }
        if ($endMonth && $endYear) {
            $endDateRaw = sprintf('%04d-%02d-01', $endYear, $endMonth);
            $endDate = date('Y-m-t', strtotime($endDateRaw));
        }

        if (empty($name) || empty($startDate) || empty($endDate)) {
            $this->flash('error', 'Session name, start date, and end date are required.');
            $this->redirect(moduleUrl('admin', 'academic_sessions'));
            return;
        }

        if (strtotime($endDate) <= strtotime($startDate)) {
            $this->flash('error', 'End date must be after start date.');
            $this->redirect(moduleUrl('admin', 'academic_sessions'));
            return;
        }

        $adminUserId = $_SESSION['user_id'] ?? 1;

        try {
            $this->repo->create([
                'session_name' => $name,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
                'description'  => $description,
                'is_current'   => $isActive,
                'created_by'   => $adminUserId
            ]);
            $this->flash('success', 'Academic session created successfully.');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $this->flash('error', 'An academic session with the name "' . htmlspecialchars($name) . '" already exists. Please choose a different name.');
            } else {
                $this->flash('error', 'Failed to create session: ' . $e->getMessage());
            }
        }

        $this->redirect(moduleUrl('admin', 'academic_sessions'));
    }

    private function updateSession(): void
    {
        $this->validateCsrf();

        $id = (int)$this->input('session_id', 0);
        $name = trim($this->input('session_name', ''));
        $startMonth = (int)$this->input('start_month', 0);
        $startYear  = (int)$this->input('start_year', 0);
        $endMonth   = (int)$this->input('end_month', 0);
        $endYear    = (int)$this->input('end_year', 0);

        $description = trim($this->input('description', ''));

        $startDate = '';
        $endDate = '';

        if ($startMonth && $startYear) {
            $startDate = sprintf('%04d-%02d-01', $startYear, $startMonth);
        }
        if ($endMonth && $endYear) {
            $endDateRaw = sprintf('%04d-%02d-01', $endYear, $endMonth);
            $endDate = date('Y-m-t', strtotime($endDateRaw));
        }

        if (!$id || empty($name) || empty($startDate) || empty($endDate)) {
            $this->flash('error', 'Invalid input for updating session.');
            $this->redirect(moduleUrl('admin', 'academic_sessions'));
            return;
        }

        if (strtotime($endDate) <= strtotime($startDate)) {
            $this->flash('error', 'End date must be after start date.');
            $this->redirect(moduleUrl('admin', 'academic_sessions'));
            return;
        }

        try {
            $this->repo->update($id, [
                'session_name' => $name,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
                'description'  => $description
            ]);
            $this->flash('success', 'Academic session updated successfully.');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $this->flash('error', 'An academic session with the name "' . htmlspecialchars($name) . '" already exists. Please choose a different name.');
            } else {
                $this->flash('error', 'Failed to update session: ' . $e->getMessage());
            }
        }

        $this->redirect(moduleUrl('admin', 'academic_sessions'));
    }

    private function setActiveSession(): void
    {
        $this->validateCsrf();
        $id = (int)$this->input('session_id', 0);

        if (!$id) {
            $this->flash('error', 'Invalid session ID.');
            $this->redirect(moduleUrl('admin', 'academic_sessions'));
            return;
        }

        if ($this->repo->setAsCurrent($id)) {
            $this->flash('success', 'Active academic session has been switched successfully.');
        } else {
            $this->flash('error', 'Failed to switch the active academic session.');
        }

        $this->redirect(moduleUrl('admin', 'academic_sessions'));
    }

    private function deleteSession(): void
    {
        $this->validateCsrf();
        $id = (int)$this->input('session_id', 0);

        if (!$id) {
            $this->flash('error', 'Invalid session ID.');
            $this->redirect(moduleUrl('admin', 'academic_sessions'));
            return;
        }

        try {
            if ($this->repo->delete($id)) {
                $this->flash('success', 'Academic session deleted successfully.');
            } else {
                $this->flash('error', 'Cannot delete the currently active session or a session that is in use.');
            }
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to delete session. It might be linked to existing records.');
        }

        $this->redirect(moduleUrl('admin', 'academic_sessions'));
    }
}
