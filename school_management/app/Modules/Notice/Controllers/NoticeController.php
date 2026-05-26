<?php
namespace App\Modules\Notice\Controllers;

/**
 * NoticeController — Manage school notices/announcements
 */
class NoticeController extends \Controller
{
    public function index(): void
    {
        $page = (int)$this->input('page', 1);
        $filterType = $this->input('filter_type', '');
        $filterValue = $this->input('filter_value', '');

        $query = "SELECT * FROM notices WHERE 1=1";
        $params = [];

        // Automatically hide broadcasted notices after 7 days
        // Show: non-broadcasted notices, OR broadcasted notices within 7 days
        $query .= " AND (is_broadcasted = 0 OR (is_broadcasted = 1 AND (broadcast_date IS NULL OR DATE_ADD(broadcast_date, INTERVAL 7 DAY) > NOW())))";

        // Build filter conditions
        if (!empty($filterType) && !empty($filterValue)) {
            if ($filterType === 'year') {
                $query .= " AND YEAR(created_at) = ?";
                $params[] = (int)$filterValue;
            } elseif ($filterType === 'date') {
                $query .= " AND DATE(created_at) = ?";
                $params[] = $filterValue;
            } elseif ($filterType === 'day') {
                // Day of week: 1=Sunday, 2=Monday, ..., 7=Saturday
                $query .= " AND DAYOFWEEK(created_at) = ?";
                $params[] = (int)$filterValue;
            }
        }

        $query .= " ORDER BY created_at DESC";

        $paginatedNotices = $this->db->paginate($query, $params, $page, 20);

        // Get available years for filter dropdown
        $years = $this->db->fetchAll("SELECT DISTINCT YEAR(created_at) as year FROM notices ORDER BY year DESC");
        $yearList = array_map(fn($row) => $row['year'], $years);

        $this->render('Modules/Notice/Views/index', [
            'pageTitle'    => 'Notices & Announcements',
            'notices'      => $paginatedNotices['data'] ?? [],
            'pagination'   => $paginatedNotices,
            'filterType'   => $filterType,
            'filterValue'  => $filterValue,
            'years'        => $yearList,
        ], 'admin');
    }

    public function apiNotices(): void
    {
        $notices = $this->db->fetchAll("SELECT notice_id, title, description, created_at, attachment_path, target_audience, is_broadcasted FROM notices WHERE is_active = 1 ORDER BY created_at DESC LIMIT 50");
        $this->json($notices);
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');
        $this->validateCsrf();

        if ($action === 'create') {
            $data = [
                'title'           => $this->input('title', ''),
                'description'     => $this->input('content', ''),
                'target_audience' => ucfirst($this->input('target_role', 'all')),
                'is_active'       => 1,
                'posted_by'       => $_SESSION['user_id'] ?? 0,
                'created_at'      => date('Y-m-d H:i:s'),
                'notice_date'     => date('Y-m-d'),
                'attachment_path' => '',
            ];

            if (empty($data['title'])) {
                $this->flash('error', 'Notice title is required.');
                $this->redirect(moduleUrl('admin', 'notices'));
                return;
            }

            // Handle file attachment
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $attachment = $this->handleAttachmentUpload($_FILES['attachment']);
                if ($attachment) {
                    $data['attachment_path'] = $attachment;
                } else {
                    $this->flash('error', 'Invalid file. Only PDF, Word, Excel, PowerPoint, Images, and ZIP files are allowed (Max 5MB).');
                    $this->redirect(moduleUrl('admin', 'notices'));
                    return;
                }
            }

            $this->db->insert('notices', $data);
            $this->flash('success', 'Notice published successfully.');
        } elseif ($action === 'delete') {
            $noticeId = (int)$this->input('notice_id', 0);

            // Delete attachment if exists
            $notice = $this->db->fetch("SELECT attachment_path FROM notices WHERE notice_id = ?", [$noticeId]);
            if ($notice && $notice['attachment_path']) {
                $filePath = dirname(APP_ROOT) . '/' . $notice['attachment_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $this->db->delete('notices', 'notice_id = ?', [$noticeId]);
            $this->flash('success', 'Notice deleted.');
        } elseif ($action === 'toggle') {
            $noticeId = (int)$this->input('notice_id', 0);
            $notice = $this->db->fetch("SELECT * FROM notices WHERE notice_id = ?", [$noticeId]);
            if ($notice) {
                $this->db->update('notices', ['is_active' => $notice['is_active'] ? 0 : 1], 'notice_id = ?', [$noticeId]);
                $this->flash('success', 'Notice visibility updated.');
            }
        } elseif ($action === 'broadcast') {
            $noticeId = (int)$this->input('notice_id', 0);
            $notice = $this->db->fetch("SELECT * FROM notices WHERE notice_id = ?", [$noticeId]);
            if ($notice) {
                $isBroadcasted = empty($notice['is_broadcasted']) ? 1 : 0;
                $updateData = ['is_broadcasted' => $isBroadcasted];

                // Set broadcast_date when broadcasting
                if ($isBroadcasted === 1) {
                    $updateData['broadcast_date'] = date('Y-m-d H:i:s');
                }

                $this->db->update('notices', $updateData, 'notice_id = ?', [$noticeId]);
                $this->flash('success', 'Notice broadcast status updated.');
            }
        }

        $this->redirect(moduleUrl('admin', 'notices'));
    }

    private function handleAttachmentUpload($file): ?string
    {
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/zip',
            'application/x-zip-compressed'
        ];

        // Validate file size
        if ($file['size'] > $maxFileSize) {
            return null;
        }

        // Validate file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            return null;
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimes)) {
            return null;
        }

        // Create uploads directory
        $uploadDir = dirname(APP_ROOT) . '/public/uploads/notices/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $filename = uniqid('notice_') . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'public/uploads/notices/' . $filename;
        }

        return null;
    }
}
