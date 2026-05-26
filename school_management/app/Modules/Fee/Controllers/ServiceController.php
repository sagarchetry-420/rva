<?php
namespace App\Modules\Fee\Controllers;

/**
 * ServiceController — Manage student services (hostel, transport, library, etc.)
 */
class ServiceController extends \Controller
{
    public function index(): void
    {
        $services = $this->db->fetchAll("SELECT * FROM services ORDER BY service_name ASC");

        $this->render('Modules/Fee/Views/services', [
            'pageTitle' => 'Student Services',
            'services'  => $services ?? [],
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');
        $this->validateCsrf();

        if ($action === 'create') {
            $data = [
                'service_name' => $this->input('service_name', ''),
                'description'  => $this->input('description', ''),
                'fee_amount'   => (float)$this->input('fee_amount', 0),
                'is_active'    => $this->input('is_active', 1) ? 1 : 0,
            ];

            if (empty($data['service_name'])) {
                $this->flash('error', 'Service name is required.');
                $this->redirect(moduleUrl('admin', 'services'));
                return;
            }

            $this->db->insert('services', $data);
            $this->flash('success', 'Service created successfully.');
        } elseif ($action === 'edit') {
            $serviceId = (int)$this->input('service_id', 0);
            $data = [
                'service_name' => $this->input('service_name', ''),
                'description'  => $this->input('description', ''),
                'fee_amount'   => (float)$this->input('fee_amount', 0),
                'is_active'    => $this->input('is_active', 1) ? 1 : 0,
            ];

            if (empty($data['service_name']) || $serviceId <= 0) {
                $this->flash('error', 'Service name is required.');
                $this->redirect(moduleUrl('admin', 'services'));
                return;
            }

            $this->db->update('services', $data, 'service_id = ?', [$serviceId]);
            $this->flash('success', 'Service updated successfully.');
        } elseif ($action === 'delete') {
            $serviceId = (int)$this->input('service_id', 0);
            $this->db->delete('services', 'service_id = ?', [$serviceId]);
            $this->flash('success', 'Service deleted.');
        } elseif ($action === 'toggle') {
            $serviceId = (int)$this->input('service_id', 0);
            $service = $this->db->fetch("SELECT * FROM services WHERE service_id = ?", [$serviceId]);
            if ($service) {
                $this->db->update('services', ['is_active' => $service['is_active'] ? 0 : 1], 'service_id = ?', [$serviceId]);
                $this->flash('success', 'Service status updated.');
            }
        }

        $this->redirect(moduleUrl('admin', 'services'));
    }
}
