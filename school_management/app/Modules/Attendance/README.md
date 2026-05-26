# Phase 6: Core Operations (Attendance & Exams)

## Overview
Phase 6 tackles the daily operations of the School Management System. Due to the distinct and complex nature of Attendance Tracking and Examination Grading, this phase was implemented across two dedicated modules:
1. `app/Modules/Attendance`
2. `app/Modules/Exams`

## 1. Attendance Module
### Structure
- **`AttendanceRepository`**: Handles optimized queries joining the `student_academics` table with the `attendance` table to fetch lists of active students for a specific date and class.
- **`AttendanceService`**: Uses a database transaction to process bulk attendance submission. It ensures data consistency by using `ON DUPLICATE KEY UPDATE` to effortlessly handle both initial attendance marking and subsequent corrections.
- **`AttendanceValidator`**: Strict date and status validation.
- **`AttendanceController` & `Views/index.php`**: A unified grid where teachers/admins select a Class and Date, load the active students, and quickly mark Present/Absent/Late/Excused via radio buttons.

## 2. Exams Module
### Structure
The exams module required handling a deeply relational data model: `examinations` → `exam_classes` → `exam_schedules` → `results`.
- **Repositories**:
  - `ExamRepository`: Manages the root exams and which classes are taking them.
  - `ScheduleRepository`: Manages the specific subject schedules (Date, Start Time, End Time, Full Marks).
  - `ResultRepository`: Joins active students with their marks for a specific schedule.
- **`ExamService`**: Handles the complex logic of saving bulk results. It validates that marks entered do not exceed the configured `full_marks` for the schedule, automatically calculates a basic Grade string (A+, A, B, etc.), and wraps the entire bulk insert/update in a database transaction to prevent partial grading failures.
- **Controllers & Views**:
  - **`ExamController` & `Views/exams.php`**: Admins define global exams (e.g., "Term 1 Final") and assign them to classes.
  - **`Views/schedules.php`**: For a specific exam and class, admins define the timetable and max marks for each assigned subject.
  - **`ResultController` & `Views/results.php`**: Teachers select the Exam, Class, and Subject Schedule to load the grading grid. They can enter marks or flag a student as Absent (which dynamically disables the marks input via JavaScript).

## Data Integrity
Both modules strictly rely on the `ClassSubjectRepository::getActiveSession()` method from the Academic module. If no active session exists, neither attendance can be taken nor exams created. This enforces strict temporal isolation of records year-over-year.
