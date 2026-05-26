<?php
namespace App\Modules\CMS\Controllers;

class HallOfFameController extends \Controller
{
    public function index(): void
    {
        $page = (int)$this->input('page', 1);
        $paginatedEntries = $this->db->paginate("SELECT * FROM hall_of_fame ORDER BY created_at DESC", [], $page, 15);

        $this->render('Modules/CMS/Views/hall_of_fame/index', [
            'pageTitle'  => 'Hall of Fame Management',
            'entries'    => $paginatedEntries['data'] ?? [],
            'pagination' => $paginatedEntries
        ], 'admin');
    }

    public function apiHallOfFame(): void
    {
        $entries = $this->db->fetchAll("SELECT * FROM hall_of_fame WHERE is_active = 1 ORDER BY created_at DESC");
        $this->json($entries);
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');
        $this->validateCsrf();

        if ($action === 'create') {
            $name = $this->input('name', '');
            $achievement = $this->input('achievement', '');
            $percentage = $this->input('percentage', '');
            
            // Handle image upload
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(APP_ROOT) . '/assets/gallery/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = uniqid('fame_') . '_' . basename($_FILES['image']['name']);
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = 'assets/gallery/' . $filename;
                }
            }

            if (empty($name) || empty($achievement) || empty($imagePath)) {
                $this->flash('error', 'Name, achievement, and image are required.');
                $this->redirect(moduleUrl('admin', 'hall-of-fame'));
                return;
            }

            $this->db->insert('hall_of_fame', [
                'name' => $name,
                'achievement' => $achievement,
                'percentage' => $percentage,
                'image_path' => $imagePath,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->flash('success', 'Hall of Fame entry added successfully.');
            
        } elseif ($action === 'delete') {
            $id = (int)$this->input('id', 0);
            
            // Optionally delete file
            $entry = $this->db->fetch("SELECT image_path FROM hall_of_fame WHERE id = ?", [$id]);
            if ($entry && $entry['image_path']) {
                $filePath = dirname(APP_ROOT) . '/' . $entry['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $this->db->delete('hall_of_fame', 'id = ?', [$id]);
            $this->flash('success', 'Entry deleted.');
            
        } elseif ($action === 'toggle') {
            $id = (int)$this->input('id', 0);
            $entry = $this->db->fetch("SELECT is_active FROM hall_of_fame WHERE id = ?", [$id]);
            if ($entry) {
                $this->db->update('hall_of_fame', ['is_active' => $entry['is_active'] ? 0 : 1], 'id = ?', [$id]);
                $this->flash('success', 'Visibility updated.');
            }
        }

        $this->redirect(moduleUrl('admin', 'hall-of-fame'));
    }
}
