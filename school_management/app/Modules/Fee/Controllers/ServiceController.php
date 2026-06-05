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
            $serviceName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', trim($this->input('service_name', '')));
            $description = htmlspecialchars(strip_tags(trim($this->input('description', ''))), ENT_QUOTES, 'UTF-8');

            $data = [
                'service_name'  => $serviceName,
                'description'   => $description,
                'fee_amount'    => max(0, (float)$this->input('fee_amount', 0)),
                'billing_cycle' => $this->input('billing_cycle', 'One-Time'),
                'is_active'     => $this->input('is_active', 1) ? 1 : 0,
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
            $serviceName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', trim($this->input('service_name', '')));
            $description = htmlspecialchars(strip_tags(trim($this->input('description', ''))), ENT_QUOTES, 'UTF-8');

            $data = [
                'service_name'  => $serviceName,
                'description'   => $description,
                'billing_cycle' => $this->input('billing_cycle', 'One-Time'),
                'is_active'     => $this->input('is_active', 1) ? 1 : 0,
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

    public function classFees(): void
    {
        $serviceId = (int)$this->input('service_id', 0);
        $service = $this->db->fetch("SELECT * FROM services WHERE service_id = ?", [$serviceId]);

        if (!$service) {
            $this->flash('error', 'Service not found.');
            $this->redirect(moduleUrl('admin', 'services'));
            return;
        }

        $classes = $this->db->fetchAll("SELECT * FROM classes WHERE is_active = 1 ORDER BY LENGTH(class_name), class_name ASC, section ASC");
        $classFees = $this->db->fetchAll("SELECT class_id, fee_amount FROM class_service_fees WHERE service_id = ?", [$serviceId]);
        
        $feeMap = [];
        foreach ($classFees as $cf) {
            $feeMap[$cf['class_id']] = $cf['fee_amount'];
        }

        $this->render('Modules/Fee/Views/class_service_fees', [
            'pageTitle' => 'Class Fees for ' . $service['service_name'],
            'service'   => $service,
            'classes'   => $classes,
            'feeMap'    => $feeMap
        ], 'admin');
    }

    public function saveClassFees(): void
    {
        $this->validateCsrf();
        $serviceId = (int)$this->input('service_id', 0);
        $fees = $_POST['class_fees'] ?? []; // class_id => fee_amount

        foreach ($fees as $classId => $amount) {
            $classId = (int)$classId;
            
            $amountStr = trim((string)$amount);
            if ($amountStr === '') {
                // Remove the override to fall back to the base fee
                $this->db->execute("DELETE FROM class_service_fees WHERE service_id = ? AND class_id = ?", [$serviceId, $classId]);
                continue;
            }
            
            $amount = max(0, (float)$amount);

            $exists = $this->db->fetch("SELECT id FROM class_service_fees WHERE service_id = ? AND class_id = ?", [$serviceId, $classId]);

            if ($exists) {
                $this->db->update('class_service_fees', ['fee_amount' => $amount], 'id = ?', [$exists['id']]);
            } else {
                $this->db->insert('class_service_fees', [
                    'service_id' => $serviceId,
                    'class_id' => $classId,
                    'fee_amount' => $amount
                ]);
            }
        }

        $this->flash('success', 'Class-specific fees updated successfully.');
        $this->redirect(moduleUrl('admin', 'services'));
    }
}
