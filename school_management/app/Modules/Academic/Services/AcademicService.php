<?php
namespace App\Modules\Academic\Services;

use App\Modules\Academic\Repositories\ClassRepository;
use App\Modules\Academic\Repositories\SubjectRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository;

/**
 * AcademicService — Business logic for Academic Entities
 */
class AcademicService
{
    private ClassRepository $classRepo;
    private SubjectRepository $subjectRepo;
    private ClassSubjectRepository $assignmentRepo;

    public function __construct()
    {
        $this->classRepo = new ClassRepository();
        $this->subjectRepo = new SubjectRepository();
        $this->assignmentRepo = new ClassSubjectRepository();
    }

    // --- Class Operations ---

    public function createClass(array $data): array
    {
        $this->classRepo->create([
            'class_name'       => $data['class_name'],
            'section'          => $data['section'],
            'class_teacher_id' => $data['class_teacher_id'] ?: null,
            'admission_fee'    => $data['admission_fee'] ?? 0.00,
            'exam_fee'         => $data['exam_fee'] ?? 0.00,
            'is_active'        => 1
        ]);
        return ['success' => true, 'message' => 'Class created successfully.'];
    }

    public function updateClass(int $classId, array $data): array
    {
        $updated = $this->classRepo->update($classId, [
            'class_name'       => $data['class_name'],
            'section'          => $data['section'],
            'class_teacher_id' => $data['class_teacher_id'] ?: null,
            'admission_fee'    => $data['admission_fee'] ?? 0.00,
            'exam_fee'         => $data['exam_fee'] ?? 0.00
        ]);

        if (!$updated) {
            return ['success' => true, 'message' => 'No changes made.', 'no_change' => true];
        }
        return ['success' => true, 'message' => 'Class updated successfully.'];
    }

    public function deleteClass(int $classId): array
    {
        if ($this->classRepo->delete($classId)) {
            return ['success' => true, 'message' => 'Class deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to delete class.'];
    }

    // --- Subject Operations ---

    public function createSubject(array $data): array
    {
        $this->subjectRepo->create([
            'subject_name' => $data['subject_name'],
            'subject_code' => $data['subject_code'],
            'description'  => $data['description'] ?: null,
            'is_active'    => 1
        ]);
        return ['success' => true, 'message' => 'Subject created successfully.'];
    }

    public function updateSubject(int $subjectId, array $data): array
    {
        $updated = $this->subjectRepo->update($subjectId, [
            'subject_name' => $data['subject_name'],
            'subject_code' => $data['subject_code'],
            'description'  => $data['description'] ?: null
        ]);

        if (!$updated) {
            return ['success' => true, 'message' => 'No changes made.', 'no_change' => true];
        }
        return ['success' => true, 'message' => 'Subject updated successfully.'];
    }

    public function deleteSubject(int $subjectId): array
    {
        if ($this->subjectRepo->delete($subjectId)) {
            return ['success' => true, 'message' => 'Subject deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to delete subject.'];
    }

    // --- Assignment Operations ---

    public function assignSubjectToClass(int $classId, int $subjectId, ?int $teacherId): array
    {
        $session = $this->assignmentRepo->getActiveSession();
        if (!$session) {
            return ['success' => false, 'message' => 'No active academic session found.'];
        }

        if ($this->assignmentRepo->assignmentExists($classId, $subjectId, (int)$session['session_id'])) {
            return ['success' => false, 'message' => 'This subject is already assigned to this class for the active session.'];
        }

        $this->assignmentRepo->createAssignment([
            'class_id'   => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId ?: null,
            'session_id' => $session['session_id']
        ]);

        return ['success' => true, 'message' => 'Subject assigned successfully.'];
    }

    public function removeAssignment(int $assignmentId): array
    {
        if ($this->assignmentRepo->deleteAssignment($assignmentId)) {
            return ['success' => true, 'message' => 'Assignment removed successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to remove assignment.'];
    }

    public function updateAssignmentTeacher(int $assignmentId, ?int $teacherId): array
    {
        if ($this->assignmentRepo->update($assignmentId, ['teacher_id' => $teacherId])) {
            return ['success' => true, 'message' => 'Teacher assignment updated successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to update teacher assignment.'];
    }
}
