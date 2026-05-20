# Promotion System - Testing & Deployment Guide

## Pre-Flight Checklist

### Database Migration
**Status: REQUIRED - Must run before testing**

The promotion system requires database schema updates. Run the migration:

```bash
# Option 1: Using MySQL CLI
mysql -u root -p school_management < school_management/database/migration_promotion_system.sql

# Option 2: Using phpMyAdmin
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select database: "school_management"
3. Click "SQL" tab
4. Copy-paste contents of: database/migration_promotion_system.sql
5. Click "Go"
```

**Verify Migration Success:**
```sql
SHOW TABLES LIKE 'academic_sessions';  -- Should return 1 row
SELECT COUNT(*) FROM academic_sessions;  -- Should return 3 rows (sample data)
SELECT COUNT(*) FROM class_promotion_rules;  -- Should return 5 rows
```

---

## Phase 1: Navigation Testing

### Admin Sidebar Links ✅
**What changed:** Added "Student Promotions" link in Academic section

**Steps:**
1. Login as Admin user
2. Verify sidebar shows these sections in order:
   - Main → Dashboard
   - Management → Students, Teachers, Classes, Subjects
   - **Academic** → Attendance, Examinations, **✅ Student Promotions** (NEW)
   - Finance & Info → Fees, Notices, Timetable

3. Click "Student Promotions" link
4. Verify you see the Promotion Panel with:
   - Filter options (From Session, To Session, Select Class)
   - Empty student list message

### Student Sidebar Links ✅
**What changed:** Added two new links in My Academic section

**Steps:**
1. Login as Student user
2. Verify sidebar shows these sections in order:
   - Main → Dashboard
   - **My Academic** → My Profile, My Attendance, My Results, **✅ Academic Results (Enhanced)** (NEW), **✅ Download Transcript** (NEW)
   - Info → Notices, Timetable

3. Click "Academic Results (Enhanced)"
4. Verify you see enhanced results page with:
   - Session selection dropdown
   - Performance sidebar (cumulative stats)
   - Timeline view

5. Click "Download Transcript"
6. Verify you see transcript download page with format options

---

## Phase 2: Database & Sample Data Testing

### Verify Sample Data
**Run in phpMyAdmin or CLI:**

```sql
-- Check academic sessions
SELECT session_id, session_name, is_current FROM academic_sessions ORDER BY session_id;
-- Expected: 3 records (2024-25, 2025-26 [current], 2026-27)

-- Check promotion rules
SELECT from_class_id, to_class_id, min_percentage, min_subjects_passed 
FROM class_promotion_rules 
ORDER BY from_class_id;
-- Expected: 5 rules (1→2, 2→3, 3→4, 4→5, 5→6)
```

---

## Phase 3: Admin Promotion Panel Testing

### Test 1: Load Students for Promotion

**Prerequisites:**
- At least one student exists in system
- Student has exam results recorded
- Student has an academic record

**Steps:**
1. Login as Admin
2. Go to "Student Promotions" (Admin → Academic → Student Promotions)
3. Select filters:
   - **From Session:** 2024-25
   - **To Session:** 2025-26
   - **Class:** 1 (or whichever class has students)
4. Click "Load Students" button
5. **Verify:**
   - Students table loads with data
   - Shows student names, roll numbers, percentages, failing subjects
   - Eligibility status (Eligible, May Fail, Uncertain) is shown
   - Statistics card updated (Total, Eligible, May Fail, Uncertain counts)

**Expected Results:**
- Table shows students from selected class in 2024-25 session
- Percentage calculated correctly (should match exam results)
- Failing subjects count displayed (F grades)

---

### Test 2: Bulk Promotion

**Prerequisites:**
- Students loaded from Test 1
- At least one "Eligible" student visible

**Steps:**
1. Check the checkbox for "Select All" to select all students (or select individual eligible students)
2. Click "Promote Selected" button
3. Confirmation modal should appear with:
   - Number of students to promote
   - Actions that will happen (create records, assign subjects, record history)
4. Click "Confirm" button
5. **Verify:**
   - Page shows success message: "Promotion completed! Promoted: X, Failed: Y"
   - Modal closes
   - Success/warning/error message displayed

**Expected Results:**
- Eligible students promoted to next class/session
- New records created in student_academics table
- Promotion history recorded
- Roll numbers generated (format: C{class_id}-{year}-{sequence})

**To verify in database:**
```sql
-- Check new academic record
SELECT academic_id, student_id, session_id, class_id, promotion_status 
FROM student_academics 
WHERE session_id = 2 AND student_id = [test_student_id];
-- Should show new record with promotion_status = 'Promoted'

-- Check promotion history
SELECT student_id, from_class_id, to_class_id, promotion_type, promotion_date 
FROM promotion_history 
WHERE student_id = [test_student_id] 
ORDER BY promotion_date DESC LIMIT 1;
-- Should show latest promotion record
```

---

## Phase 4: Student Results View Testing

### Test 1: View Enhanced Results Page

**Steps:**
1. Login as Student
2. Go to "Academic Results (Enhanced)" (Student → My Academic → Academic Results (Enhanced))
3. **Verify page loads with:**
   - Session dropdown (showing available sessions)
   - Results table (showing current session results)
   - Performance sidebar with:
     - Cumulative percentage
     - Sessions completed
     - Times promoted
     - Total A grades

4. Change session in dropdown
5. **Verify:**
   - Results table updates with selected session data
   - Percentages change appropriately
   - Grade distribution updates

---

### Test 2: Timeline View

**Prerequisites:**
- Student should have promotion history (promoted in at least 2 sessions)

**Steps:**
1. On "Academic Results (Enhanced)" page, look for timeline section
2. **Verify:**
   - Timeline shows all sessions in chronological order
   - Shows promotion status (Promoted, Detained, Active)
   - Shows class progression (Class 1 → 2 → 3, etc.)
   - Dates are correct

---

## Phase 5: Transcript Download Testing

### Test 1: HTML View/Print

**Steps:**
1. Login as Student
2. Go to "Download Transcript" page
3. **Verify page shows:**
   - Student information (name, ID, DOB)
   - Summary statistics (cumulative %, sessions, promotions)
   - Session-wise performance table
   - Subject-wise results
   - Certification statement

4. Click browser Print button (Ctrl+P) or "View/Print (HTML)" link
5. **Verify:**
   - Page is printer-friendly
   - All information is visible
   - Styling looks professional (no broken layout)

---

### Test 2: CSV Export

**Steps:**
1. On Download Transcript page, click "Export (CSV)" button
2. File downloads: `transcript_[student_id].csv`
3. Open downloaded file in Excel/Sheets
4. **Verify:**
   - Headers are correct: Student ID, Name, DOB, Session, Class, Subject, Marks, Grade
   - Data is comma-separated properly
   - Each row represents one subject result
   - Grades are accurate

---

### Test 3: PDF Download

**Prerequisites:**
- dompdf library must be installed (see PDF Setup below)

**Steps:**
1. On Download Transcript page, click "Download (PDF)" button
2. **If dompdf NOT installed:**
   - Message appears: "PDF Library Not Installed"
   - Options shown: View HTML or Download CSV
   - This is EXPECTED behavior

3. **If dompdf IS installed:**
   - File downloads: `transcript_[student_name].pdf`
   - Open PDF file in reader
   - **Verify:**
     - Content matches HTML version
     - Professional formatting
     - Logo/header visible
     - All tables formatted correctly
     - Footer with certification

---

## Phase 6: PDF Setup (Optional but Recommended)

### Option 1: Install via Composer (Recommended)

**Prerequisites:**
- Composer installed on your system

**Steps:**
```bash
cd school_management
composer require dompdf/dompdf
```

**Verify:**
```bash
ls -la vendor/dompdf/dompdf/
# Should show dompdf files
```

### Option 2: Manual Installation

1. Download: https://github.com/dompdf/dompdf/releases (Latest release)
2. Extract to: `school_management/vendor/dompdf/dompdf/`
3. Verify structure:
   ```
   school_management/
   └── vendor/
       └── dompdf/
           └── dompdf/
               ├── src/
               ├── lib/
               ├── autoload.php
               └── ...
   ```

### Test PDF Installation
1. Login as Student
2. Go to Download Transcript page
3. Try downloading as PDF
4. If successful, PDF downloads and opens

---

## Phase 7: Complete Workflow Test

**Full Integration Test** (Test everything together)

### Scenario: Promote a Student from Class 1 to Class 2

**Step-by-step:**

1. **Admin: Load Students**
   - Go to Promotion Panel
   - Select: From 2024-25 → To 2025-26 → Class 1
   - Click "Load Students"
   - Verify students appear

2. **Admin: Select & Promote**
   - Select 1-2 students (check their eligible status first)
   - Click "Promote Selected"
   - Confirm in modal
   - Wait for success message

3. **Admin: Verify Database**
   ```sql
   SELECT * FROM promotion_history WHERE student_id = [promoted_student] ORDER BY promotion_date DESC LIMIT 1;
   -- Should show: from_class_id=1, to_class_id=2, promotion_type='promoted'
   ```

4. **Student: View New Results**
   - Login as promoted student
   - Go to "Academic Results (Enhanced)"
   - Select session "2025-26"
   - Verify new class shown (Class 2)
   - Verify new roll number generated

5. **Student: Download Transcript**
   - Go to "Download Transcript"
   - Verify both sessions shown:
     - 2024-25: Class 1
     - 2025-26: Class 2 (NEW)
   - Download as CSV
   - Verify both sessions in CSV

---

## Troubleshooting

### Issue: "Student Promotions" link not appearing in sidebar
**Solution:**
- Clear browser cache (Ctrl+Shift+Delete)
- Verify `includes/sidebar.php` was edited correctly
- Check file for "promotion_panel.php" text

### Issue: Promotion Panel loads but no students appear
**Solution:**
- Verify students exist in system
- Verify students have exam results
- Run query: `SELECT * FROM student_academics WHERE class_id=1 AND session_id=1;`
- Should return at least one student record

### Issue: "No academic record found" error when promoting
**Solution:**
- Admin must first create academic records for students in current session
- Or use bulk promotion for all students in a class at once

### Issue: PDF download not working
**Solution:**
- Check if dompdf is installed: `vendor/dompdf/dompdf/` should exist
- If not installed, use "View/Print (HTML)" option instead
- Install dompdf via Composer: `composer require dompdf/dompdf`

### Issue: Transcript shows no data
**Solution:**
- Verify student has results recorded in database
- Run: `SELECT COUNT(*) FROM results WHERE student_id = [student_id];`
- Should return > 0

---

## Success Criteria Checklist

- [ ] Database migration completed successfully
- [ ] "Student Promotions" appears in Admin sidebar
- [ ] "Academic Results (Enhanced)" appears in Student sidebar
- [ ] "Download Transcript" appears in Student sidebar
- [ ] Admin can load students for promotion
- [ ] Admin can promote students in bulk
- [ ] Student can view enhanced results with timeline
- [ ] Student can download transcript as HTML
- [ ] Student can download transcript as CSV
- [ ] Student can download transcript as PDF (if dompdf installed)
- [ ] Promotion history recorded in database
- [ ] New academic records created for promoted students
- [ ] Roll numbers generated correctly

---

## Next Steps (Optional Enhancements)

1. **Email Notifications**
   - Uncomment email code in PromotionEngine.php
   - Wire up to NotificationEngine.php
   - Test promotion notifications

2. **Advanced Filtering**
   - Add section filter in Promotion Panel
   - Add performance range filter
   - Add repeat status filter

3. **Undo/Rollback**
   - Add admin interface to reverse promotions
   - Requires transaction rollback implementation

4. **Reporting**
   - Create promotion statistics report
   - Show class-wise promotion summary
   - Export promotion history

---

**Version:** 1.0  
**Last Updated:** 2026-05-21  
**System:** School Management System - Student Promotion Feature
