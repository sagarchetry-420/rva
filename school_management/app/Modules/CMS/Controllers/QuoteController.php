<?php
namespace App\Modules\CMS\Controllers;

class QuoteController extends \Controller
{
    public function index(): void
    {
        $quotes = $this->db->fetchAll("SELECT * FROM quotes ORDER BY created_at DESC");

        $this->render('Modules/CMS/Views/quotes/index', [
            'pageTitle'  => 'Quotes Management',
            'quotes'     => $quotes
        ], 'admin');
    }

    public function apiQuotes(): void
    {
        $quotes = $this->db->fetchAll("SELECT * FROM quotes WHERE is_active = 1 ORDER BY created_at DESC");
        $this->json($quotes);
    }

    public function handleAction(): void
    {
        $action = $this->input('action', '');
        $this->validateCsrf();

        if ($action === 'create') {
            $quote_text = $this->input('quote_text', '');
            $author = $this->input('author', '');

            if (empty($quote_text) || empty($author)) {
                $this->flash('error', 'Quote text and author are required.');
                $this->redirect(moduleUrl('admin', 'quotes'));
                return;
            }

            $this->db->insert('quotes', [
                'quote_text' => $quote_text,
                'author' => $author,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->flash('success', 'Quote added successfully.');
            
        } elseif ($action === 'delete') {
            $id = (int)$this->input('id', 0);
            $this->db->delete('quotes', 'id = ?', [$id]);
            $this->flash('success', 'Quote deleted.');
            
        } elseif ($action === 'toggle') {
            $id = (int)$this->input('id', 0);
            $entry = $this->db->fetch("SELECT is_active FROM quotes WHERE id = ?", [$id]);
            if ($entry) {
                $this->db->update('quotes', ['is_active' => $entry['is_active'] ? 0 : 1], 'id = ?', [$id]);
                $this->flash('success', 'Visibility updated.');
            }
        }

        $this->redirect(moduleUrl('admin', 'quotes'));
    }
}
