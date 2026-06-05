<?php
namespace App\Modules\CMS\Controllers;

class GalleryController extends \Controller
{
    public function index(): void
    {
        $page = (int)$this->input('page', 1);
        $paginatedGallery = $this->db->paginate("SELECT * FROM gallery ORDER BY created_at DESC", [], $page, 12);

        $this->render('Modules/CMS/Views/gallery/index', [
            'pageTitle'  => 'Gallery Management',
            'gallery'    => $paginatedGallery['data'] ?? [],
            'pagination' => $paginatedGallery
        ], 'admin');
    }

    public function apiGallery(): void
    {
        $gallery = $this->db->fetchAll("SELECT * FROM gallery WHERE is_active = 1 ORDER BY created_at DESC");
        $this->json($gallery);
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');
        $this->validateCsrf();

        if ($action === 'create') {
            $title = trim($this->input('title', ''));
            $category = trim($this->input('category', ''));

            // Backend validation
            if (!preg_match('/^[a-zA-Z0-9\s.,\-]+$/', $title)) {
                $this->flash('error', 'Title contains invalid characters.');
                $this->redirect(moduleUrl('admin', 'gallery'));
                return;
            }
            if (!empty($category) && !preg_match('/^[a-zA-Z0-9\s.,\-]+$/', $category)) {
                $this->flash('error', 'Category contains invalid characters.');
                $this->redirect(moduleUrl('admin', 'gallery'));
                return;
            }

            // Sanitize
            $title = strip_tags($title);
            $category = strip_tags($category);
            
            // Handle image upload
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // File validation
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileMimeType = @mime_content_type($_FILES['image']['tmp_name']);
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

                if (!in_array($fileMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
                    $this->flash('error', 'Invalid file format. Only JPG, PNG, GIF, and WebP are allowed.');
                    $this->redirect(moduleUrl('admin', 'gallery'));
                    return;
                }

                if ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB limit
                    $this->flash('error', 'File size exceeds the 5MB limit.');
                    $this->redirect(moduleUrl('admin', 'gallery'));
                    return;
                }

                $uploadDir = dirname(APP_ROOT) . '/assets/gallery/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Sanitize filename
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME));
                $filename = uniqid('img_') . '_' . $safeFilename . '.' . $fileExtension;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = 'assets/gallery/' . $filename;
                }
            }

            if (empty($title) || empty($imagePath)) {
                $this->flash('error', 'Title and image are required.');
                $this->redirect(moduleUrl('admin', 'gallery'));
                return;
            }

            $this->db->insert('gallery', [
                'title' => $title,
                'category' => $category,
                'image_path' => $imagePath,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->flash('success', 'Image added successfully.');
            
        } elseif ($action === 'delete') {
            $id = (int)$this->input('id', 0);
            
            // Optionally delete file
            $entry = $this->db->fetch("SELECT image_path FROM gallery WHERE id = ?", [$id]);
            if ($entry && $entry['image_path']) {
                $filePath = dirname(APP_ROOT) . '/' . $entry['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $this->db->delete('gallery', 'id = ?', [$id]);
            $this->flash('success', 'Image deleted.');
            
        } elseif ($action === 'toggle') {
            $id = (int)$this->input('id', 0);
            $entry = $this->db->fetch("SELECT is_active FROM gallery WHERE id = ?", [$id]);
            if ($entry) {
                $this->db->update('gallery', ['is_active' => $entry['is_active'] ? 0 : 1], 'id = ?', [$id]);
                $this->flash('success', 'Visibility updated.');
            }
        }

        $this->redirect(moduleUrl('admin', 'gallery'));
    }
}
