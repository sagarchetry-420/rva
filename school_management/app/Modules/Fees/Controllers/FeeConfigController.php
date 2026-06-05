<?php
namespace App\Modules\Fees\Controllers;

use App\Modules\Fees\Repositories\FeeCategoryRepository;
use App\Modules\Fees\Repositories\ServiceRepository;

/**
 * FeeConfigController — Manage Fee Categories and Services
 */
class FeeConfigController extends \Controller
{
    private FeeCategoryRepository $categoryRepo;
    private ServiceRepository $serviceRepo;

    public function __construct()
    {
        parent::__construct();
        $this->categoryRepo = new FeeCategoryRepository();
        $this->serviceRepo = new ServiceRepository();
    }

    public function index(): void
    {
        $categories = $this->categoryRepo->findAll();
        $services = $this->serviceRepo->findAll();

        $this->render('Modules/Fees/Views/config', [
            'pageTitle'  => 'Fee Config: Categories & Services',
            'categories' => $categories,
            'services'   => $services
        ], 'admin');
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');

        switch ($action) {
            case 'add_category':
                $this->addCategory();
                break;
            case 'add_service':
                $this->addService();
                break;
            case 'delete_category':
                $this->deleteCategory();
                break;
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('admin', 'fee_config'));
        }
    }

    private function addCategory(): void
    {
        $this->validateCsrf();
        
        // Sanitize inputs to prevent XSS and other malicious injections
        $name = strip_tags(trim($this->input('category_name', '')));
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        
        $description = strip_tags(trim($this->input('description', '')));
        $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        if (empty($description)) {
            $description = null;
        }
        
        if (empty($name)) {
            $this->flash('error', 'Category name is required and must be valid.');
        } elseif ($this->categoryRepo->categoryExists($name)) {
            $this->flash('error', 'This fee category already exists.');
        } else {
            $this->categoryRepo->create([
                'category_name' => $name,
                'description'   => $description
            ]);
            $this->flash('success', 'Fee category created successfully.');
        }
        $this->redirect(moduleUrl('admin', 'fee_config'));
    }

    private function deleteCategory(): void
    {
        $this->validateCsrf();
        $categoryId = (int)$this->input('category_id', 0);
        
        if (!$categoryId) {
            $this->flash('error', 'Invalid category ID.');
        } elseif ($this->categoryRepo->isCategoryInUse($categoryId)) {
            // PHP-level manual block to prevent ON DELETE CASCADE wiping out records
            $this->flash('error', 'Cannot delete this category because there are existing fee records linked to it.');
        } else {
            try {
                $this->categoryRepo->delete($categoryId);
                $this->flash('success', 'Fee category deleted.');
            } catch (\Exception $e) {
                $this->flash('error', 'Cannot delete this category because there are existing fee records linked to it.');
            }
        }
        $this->redirect(moduleUrl('admin', 'fee_config'));
    }

    private function addService(): void
    {
        $this->validateCsrf();
        $name = trim($this->input('service_name', ''));
        
        if (empty($name)) {
            $this->flash('error', 'Service name is required.');
        } elseif ($this->serviceRepo->serviceExists($name)) {
            $this->flash('error', 'This service already exists.');
        } else {
            $this->serviceRepo->create([
                'service_name' => $name,
                'description'  => $this->input('description', null),
                'fee_amount'   => (float)$this->input('fee_amount', 0),
                'is_active'    => 1
            ]);
            $this->flash('success', 'Service created.');
        }
        $this->redirect(moduleUrl('admin', 'fee_config'));
    }
}
