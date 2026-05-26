# Rose Valley Academy (RVA) - School Management System

A modernized, modular MVC-based School Management System built with PHP and MySQL. This system handles the administrative, academic, and financial operations of the institution.

## Architecture

The application has been migrated from a legacy procedural architecture to a modern, robust **Modular MVC** (Model-View-Controller) structure.

### Core Architecture Features
* **Front Controller (`index.php`)**: All requests route through a single entry point.
* **PSR-4 Autoloading**: Custom autoloader automatically resolves namespaces to file paths (no Composer required).
* **PDO Database Singleton**: Secure, thread-safe database access enforcing the use of prepared statements to prevent SQL injection.
* **Modular Structure**: Features are isolated into self-contained modules (`Auth`, `Student`, `Teacher`, etc.) in `app/Modules`.
* **Layered Separation**:
    * **Controllers**: Dispatch HTTP requests and return views.
    * **Services**: Encapsulate complex business logic (e.g., account creation, email dispatch).
    * **Repositories**: Handle all direct database queries and CRUD operations.
    * **Validators**: Ensure strict data integrity before database interaction.
* **Security**: Enforced CSRF tokens on all POST requests, bcrypt password hashing, and HTML sanitization on output.

## Directory Structure

```text
school_management/
├── app/
│   ├── Core/                # Base classes (App, Router, Database, Controller, Validator)
│   └── Modules/             # Feature modules
│       ├── Auth/            # Login, Logout, Password Reset
│       ├── Student/         # Student CRUD, Roll Number Gen, Auto-credentialing
│       └── Teacher/         # (In Progress) Teacher Management
├── assets/                  # CSS, JS, Images (Dibru College Theme)
├── config/                  # Environment variables (.env) and DB config
├── includes/                # Base UI partials (header, sidebar, footer)
└── index.php                # Front Controller & Bootstrap
```

## Features

### Authentication Module
* Email-based login system with bcrypt hashing.
* Secure "Forgot Password" flow with expiring reset tokens sent via PHPMailer.
* Legacy MD5 passwords automatically migrated to bcrypt upon first successful login.

### Student Module
* **Admin Management**: Full CRUD operations with AJAX-powered Modals.
* **Auto-Credentialing**: Automatically generates secure usernames (`S...`) and passwords for new students.
* **Automated Emails**: Welcomes parents/students by emailing credentials directly via SMTP.
* **Smart Roll Numbers**: Auto-generates roll numbers based on Year and Section (e.g., `2024-A-01`).
* **Exports**: CSV and PDF export functionality for student rosters.

## Installation & Setup

1. **Environment Config**:
   Rename or create a `.env` file in the root directory and configure your Database and SMTP settings:
   ```env
   APP_ENV=development
   BASE_URL=http://localhost/RVA/school_management

   DB_HOST=localhost
   DB_NAME=RVA
   DB_USER=root
   DB_PASS=

   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=your_email@gmail.com
   SMTP_PASS=your_app_password
   ```

2. **Database Import**:
   Import the RVA schema into your MySQL server.

3. **Web Server**:
   Serve the `school_management` folder using Apache/Nginx. Ensure `mod_rewrite` is enabled if moving away from query-string routing in the future.

## Development

To add a new module (e.g., `Library`):
1. Create `app/Modules/Library/`.
2. Add `Controllers/LibraryController.php`, `Repositories/LibraryRepository.php`, etc.
3. Register routes or rely on the dynamic query-string router (`?module=library&action=index`).
