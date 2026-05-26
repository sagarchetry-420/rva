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
            // Simplified: omitted update/delete for brevity, similar structure to previous modules
            default:
                $this->flash('error', 'Invalid action.');
                $this->redirect(moduleUrl('admin', 'fee_config'));
        }
    }

    private function addCategory(): void
    {
        $this->validateCsrf();
        $name = trim($this->input('category_name', ''));
        
        if (empty($name)) {
            $this->flash('error', 'Category name is required.');
        } elseif ($this->categoryRepo->categoryExists($name)) {
            $this->flash('error', 'This fee category already exists.');
        } else {
            $this->categoryRepo->create([
                'category_name' => $name,
                'description'   => $this->input('description', null)
            ]);
            $this->flash('success', 'Fee category created.');
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
                'description'  => $this->input('description', null)
            ]);
            $this->flash('success', 'Service created.');
        }
        $this->redirect(moduleUrl('admin', 'fee_config'));
    }
}
