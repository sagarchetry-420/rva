# Phase 7: Financial (Fees)

## Overview
Phase 7 migrates the financial aspects of the School Management System into the new modular MVC architecture, creating `app/Modules/Fees`. This module is designed to track fee structures, assign them to students based on the active academic session, and securely collect payments while generating printable receipts.

## Module Structure

### Data Access (Repositories)
- **`FeeCategoryRepository`**: Manages the `fee_categories` lookup table (e.g., "Tuition Fee", "Library Fee").
- **`ServiceRepository`**: Manages optional `services` (e.g., "Bus Transport", "Hostel").
- **`FeeRepository`**: The core repository that queries the `fees` table, joining it with `fee_categories`, `services`, and `students` to provide a comprehensive view of a student's financial standing for a specific `session_id`.

### Business Logic & Validation
- **`FeeService`**: Contains the critical `collectFee` method. This method is wrapped in a database transaction to ensure that when a fee's `payment_status` is updated to 'Paid', the `payment_date`, `payment_method`, and uniquely generated `receipt_number` are securely and atomically committed to the database.
- **`FeeValidator`**: Enforces strict input rules (e.g., amount must be > 0, valid payment methods, ensuring fee records aren't double-paid).

### Interface (Controllers & Views)
- **`FeeConfigController` / `Views/config.php`**: The administrative setup for defining the fee categories and optional services.
- **`FeeController` / `Views/collection.php`**: The primary operational interface. Admins can search for a student, view their pending vs. paid fees, generate manual dues, and open a modal to log a payment collection.
- **`ReceiptController` / `Views/receipt.php`**: A dedicated endpoint that strips away the standard admin layout to present a clean, printable HTML receipt immediately after a successful transaction.

## Security & Integrity
Like the academic modules, the Fees module depends on `ClassSubjectRepository::getActiveSession()`. Fees cannot be generated or queried without an active academic year, preventing cross-session financial leakage. All payment actions are CSRF protected.
