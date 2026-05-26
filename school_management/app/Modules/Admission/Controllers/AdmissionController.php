<?php
namespace App\Modules\Admission\Controllers;

/**
 * AdmissionController — Manage student applications and admission settings
 */
class AdmissionController extends \Controller
{
    public function applications(): void
    {
        $filterClass = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        $filterStatus = isset($_GET['status']) ? $_GET['status'] : 'pending';
        $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Handle PDF Download
        if (isset($_GET['download_pdf']) && $_GET['download_pdf'] == '1' && $filterStatus === 'approved') {
            $this->downloadMeritListPdf($filterClass);
            return;
        }
        
        $query = "SELECT a.*, c.class_name, c.section 
                  FROM student_applications a 
                  LEFT JOIN classes c ON a.class_id = c.class_id 
                  WHERE a.status = ? ";
        $params = [$filterStatus];
        
        if ($filterClass > 0) {
            $query .= "AND a.class_id = ? ";
            $params[] = $filterClass;
        }

        if (!empty($searchQuery)) {
            if (stripos($searchQuery, 'APP-') === 0) {
                $searchId = (int)substr($searchQuery, 4);
                $query .= "AND a.id = ? ";
                $params[] = $searchId;
            } else {
                $query .= "AND (a.student_name LIKE ? OR a.phone LIKE ? OR a.id = ?) ";
                $params[] = '%' . $searchQuery . '%';
                $params[] = '%' . $searchQuery . '%';
                $params[] = (int)$searchQuery;
            }
        }
        
        $query .= "ORDER BY a.created_at DESC";

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $paginatedApplications = $this->db->paginate($query, $params, $page, 20);
        
        $classes = $this->db->fetchAll("SELECT * FROM classes ORDER BY class_name");

        $this->render('Modules/Admission/Views/applications', [
            'pageTitle'    => 'Student Applications',
            'applications' => $paginatedApplications['data'],
            'pagination'   => $paginatedApplications,
            'classes'      => $classes,
            'filterClass'  => $filterClass,
            'filterStatus' => $filterStatus,
            'searchQuery'  => $searchQuery,
        ], 'admin');
    }

    private function downloadMeritListPdf(int $classId): void
    {
        $query = "SELECT a.*, c.class_name, c.section 
                  FROM student_applications a 
                  LEFT JOIN classes c ON a.class_id = c.class_id 
                  WHERE a.status = 'approved' ";
        $params = [];
        
        if ($classId > 0) {
            $query .= "AND a.class_id = ? ";
            $params[] = $classId;
        }
        $query .= "ORDER BY c.class_name, a.student_name ASC";
        $meritList = $this->db->fetchAll($query, $params);

        $this->render('Modules/Admission/Views/merit_list_print', [
            'pageTitle' => 'Merit List',
            'meritList' => $meritList,
            'classId'   => $classId
        ], 'admin');
    }

    public function settings(): void
    {
        $settingsRaw = $this->db->fetchAll("SELECT setting_name, setting_value FROM admission_settings");
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        
        // Handle mapping from legacy DB seed names
        if (isset($settings['admission_form_open']) && !isset($settings['is_open'])) {
            $settings['is_open'] = ($settings['admission_form_open'] === 'yes' || $settings['admission_form_open'] == 1) ? 1 : 0;
        }
        if (isset($settings['application_deadline']) && !isset($settings['deadline'])) {
            $settings['deadline'] = $settings['application_deadline'];
        }
        if (isset($settings['instructions_for_applicants']) && !isset($settings['instructions'])) {
            $settings['instructions'] = $settings['instructions_for_applicants'];
        }

        if (!isset($settings['is_open'])) $settings['is_open'] = 0;
        if (!isset($settings['deadline'])) $settings['deadline'] = null;
        if (!isset($settings['max_applications'])) $settings['max_applications'] = 100;
        if (!isset($settings['instructions'])) $settings['instructions'] = '';

        $this->render('Modules/Admission/Views/settings', [
            'pageTitle' => 'Admission Settings',
            'settings'  => $settings,
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');
        $this->validateCsrf();

        if ($action === 'approve') {
            $appId = (int)$this->input('application_id', 0);
            $application = $this->db->fetch("SELECT * FROM student_applications WHERE id = ?", [$appId]);
            
            if ($application) {
                $this->db->update('student_applications', ['status' => 'approved'], 'id = ?', [$appId]);
                $this->flash('success', 'Application added to Merit List (Approved).');
            } else {
                $this->flash('error', 'Application not found.');
            }
        } elseif ($action === 'enroll') {
            $appId = (int)$this->input('application_id', 0);
            $application = $this->db->fetch("SELECT * FROM student_applications WHERE id = ?", [$appId]);
            
            if ($application && $application['status'] === 'approved') {
                // Fallback for legacy data without first_name/last_name
                $firstName = $application['first_name'];
                $lastName = $application['last_name'];
                if (empty($firstName)) {
                    $nameParts = explode(' ', trim($application['student_name'] ?? ''));
                    $firstName = array_shift($nameParts) ?: 'Student';
                    $lastName = count($nameParts) > 0 ? implode(' ', $nameParts) : 'Name';
                }

                // Prepare data for StudentService
                $studentData = [
                    'first_name'    => $firstName,
                    'last_name'     => $lastName,
                    'date_of_birth' => $application['date_of_birth'] ?: null,
                    'gender'        => $application['gender'] ?: 'Other',
                    'address'       => $application['address'] ?? '',
                    'phone'         => $application['phone'] ?: '',
                    'email'         => $application['email'] ?: '',
                    'parent_name'   => $application['parent_name'] ?: '',
                    'parent_phone'  => $application['parent_phone'] ?: '',
                    'class_id'      => $application['class_id'],
                ];
                
                $studentService = new \App\Modules\Student\Services\StudentService();
                $result = $studentService->createStudent($studentData);
                
                if ($result['success']) {
                    $this->db->update('student_applications', ['status' => 'enrolled'], 'id = ?', [$appId]);
                    $this->flash('success', 'Student officially enrolled! Credentials have been emailed.');
                    
                    // Redirect directly to the fee collection page for this new student
                    $this->redirect('/admin/fee_collection?student_id=' . $result['student_id']);
                    return;
                } else {
                    $this->flash('error', 'Error auto-enrolling student: ' . $result['message']);
                }
            } else {
                $this->flash('error', 'Application not found or not approved.');
            }
        } elseif ($action === 'reject') {
            $appId = (int)$this->input('application_id', 0);
            $this->db->update('student_applications', ['status' => 'rejected'], 'id = ?', [$appId]);
            $this->flash('success', 'Application rejected.');
        } elseif ($action === 'delete') {
            $appId = (int)$this->input('application_id', 0);
            $this->db->delete('student_applications', 'id = ?', [$appId]);
            $this->flash('success', 'Application deleted.');
        }

        $this->redirect(moduleUrl('admin', 'applications'));
    }

    public function saveSettings(): void
    {
        $this->validateCsrf();
        $data = [
            'is_open'          => $this->input('is_open', 0) ? 1 : 0,
            'deadline'         => $this->input('deadline', null),
            'max_applications' => (int)$this->input('max_applications', 100),
            'instructions'     => $this->input('instructions', ''),
        ];

        foreach ($data as $key => $value) {
            $exists = $this->db->fetch("SELECT setting_id FROM admission_settings WHERE setting_name = ?", [$key]);
            if ($exists) {
                $this->db->update('admission_settings', ['setting_value' => $value], 'setting_name = ?', [$key]);
            } else {
                $this->db->insert('admission_settings', ['setting_name' => $key, 'setting_value' => $value]);
            }
        }

        $this->flash('success', 'Admission settings saved.');
        $this->redirect(moduleUrl('admin', 'admission-settings'));
    }
}
