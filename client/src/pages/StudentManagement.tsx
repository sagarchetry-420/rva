import { useState, useEffect, useRef } from "react";
import { Link, useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { generateStudentPDF } from "@/lib/pdfGenerator";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
  UserPlus,
  Search,
  Loader2,
  Trash2,
  Users,
  School,
  ChevronDown,
  ChevronRight,
  Calendar,
  X,
  Mail,
  GraduationCap,
  CheckCircle2,
  XCircle,
  Clock,
  Download,
  LogOut,
  Award,
  Filter
} from "lucide-react";
import { toast } from "sonner";

type StudentStatus = 'active' | 'graduated' | 'left';

interface Student {
  id: string;
  user_id: string;
  enrollment_date: string;
  roll_number?: string | number | null;
  status?: StudentStatus;
  profiles?: {
    first_name: string;
    last_name: string;
    avatar_url?: string;
  };
  classes?: {
    id: string;
    name: string;
  };
}

interface StudentDetail {
  id: string;
  userId: string;
  firstName: string;
  lastName: string;
  email: string;
  avatarUrl?: string;
  dob?: string;
  className: string;
  classId: string | null;
  rollNumber?: string;
  enrollmentDate: string;
  status?: StudentStatus;
  attendance: {
    totalDays: number;
    presentDays: number;
    absentDays: number;
    lateDays: number;
    attendancePercentage: number;
  };
}

interface ClassGroup {
  id: string;
  name: string;
  students: Student[];
  sortOrder: number;
}

interface PromotionCandidate {
  id: string;
  fullName: string;
  rollNumber: string;
}

interface BulkPromotionResponse {
  success: boolean;
  academicYear: string;
  promotedCount: number;
  retainedCount: number;
  sourceClassName: string;
  targetClassName: string;
  reassignRetainedRollNumbers: boolean;
}

// Class sorting order
const CLASS_ORDER: Record<string, number> = {
  'pre-nursery': 0,
  'pg': 0, 'play group': 0,
  'nursery': 1,
  'kg': 2, 'kindergarten': 2,
  'lkg': 2,
  'ukg': 3,
  '1': 4, 'class 1': 4, 'grade 1': 4, 'i': 4,
  '2': 5, 'class 2': 5, 'grade 2': 5, 'ii': 5,
  '3': 6, 'class 3': 6, 'grade 3': 6, 'iii': 6,
  '4': 7, 'class 4': 7, 'grade 4': 7, 'iv': 7,
  '5': 8, 'class 5': 8, 'grade 5': 8, 'v': 8,
  '6': 9, 'class 6': 9, 'grade 6': 9, 'vi': 9,
  '7': 10, 'class 7': 10, 'grade 7': 10, 'vii': 10,
  '8': 11, 'class 8': 11, 'grade 8': 11, 'viii': 11,
  '9': 12, 'class 9': 12, 'grade 9': 12, 'ix': 12,
  '10': 13, 'class 10': 13, 'grade 10': 13, 'x': 13,
  '11': 14, 'class 11': 14, 'grade 11': 14, 'xi': 14,
  '12': 15, 'class 12': 15, 'grade 12': 15, 'xii': 15,
  'unassigned': 99
};

function getClassSortOrder(className: string): number {
  const lower = className.toLowerCase().trim();
  if (CLASS_ORDER[lower] !== undefined) return CLASS_ORDER[lower];

  // Extract number from class name
  const numMatch = lower.match(/(\d+)/);
  if (numMatch) {
    const num = parseInt(numMatch[1]);
    if (num >= 1 && num <= 12) return num + 3;
  }

  return 50; // Default for unknown classes
}

function getDefaultAcademicYear(): string {
  const currentYear = new Date().getFullYear();
  return `${currentYear}-${currentYear + 1}`;
}

function parseNumericRoll(rollNumber: string | number | null | undefined): number | null {
  if (rollNumber === null || rollNumber === undefined) return null;
  const normalized = String(rollNumber).trim();
  if (!/^\d+$/.test(normalized)) return null;
  const parsed = Number.parseInt(normalized, 10);
  return Number.isFinite(parsed) ? parsed : null;
}

function getNextClassIdForPromotion(
  fromClassId: string,
  classOptions: Array<{ id: string; name: string }>
): string {
  if (!fromClassId) return "";

  const fromClass = classOptions.find((classItem) => classItem.id === fromClassId);
  if (!fromClass) return "";

  const fromOrder = getClassSortOrder(fromClass.name);
  const nextClass = classOptions
    .filter((classItem) => classItem.id !== fromClassId && getClassSortOrder(classItem.name) > fromOrder)
    .sort((a, b) => {
      const orderDiff = getClassSortOrder(a.name) - getClassSortOrder(b.name);
      if (orderDiff !== 0) return orderDiff;
      return a.name.localeCompare(b.name);
    })[0];

  return nextClass?.id ?? "graduate";
}

export default function StudentManagement() {
  const [students, setStudents] = useState<Student[]>([]);
  const [classes, setClasses] = useState<any[]>([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [loading, setLoading] = useState(true);
  const [expandedClasses, setExpandedClasses] = useState<Set<string>>(new Set());
  const [selectedStudent, setSelectedStudent] = useState<StudentDetail | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [showPromotionPanel, setShowPromotionPanel] = useState(false);
  const [promotionFromClassId, setPromotionFromClassId] = useState("");
  const [promotionToClassId, setPromotionToClassId] = useState("");
  const [promotionAcademicYear, setPromotionAcademicYear] = useState(getDefaultAcademicYear());
  const [promotionCandidates, setPromotionCandidates] = useState<PromotionCandidate[]>([]);
  const [selectedPromotionStudentIds, setSelectedPromotionStudentIds] = useState<Set<string>>(new Set());
  const [promotionCandidatesLoading, setPromotionCandidatesLoading] = useState(false);
  const [promotionSubmitting, setPromotionSubmitting] = useState(false);
  const [reassignRetainedRollNumbers, setReassignRetainedRollNumbers] = useState(false);
  const [statusFilter, setStatusFilter] = useState<StudentStatus | 'all'>('active');
  const [statusUpdating, setStatusUpdating] = useState(false);
  const classRefs = useRef<Map<string, HTMLDivElement>>(new Map());
  const navigate = useNavigate();

  useEffect(() => {
    fetchInitialData();
  }, []);

  // Re-fetch when status filter changes
  useEffect(() => {
    fetchStudents();
  }, [statusFilter]);

  // Auto-expand classes with matching students and scroll to first match when searching
  useEffect(() => {
    if (!searchQuery.trim() || loading) return;

    // Find all class IDs that have matching students
    const matchingClassIds = new Set<string>();
    filteredStudents.forEach(student => {
      const classId = student.classes?.id || 'unassigned';
      matchingClassIds.add(classId);
    });

    // Expand all matching classes
    if (matchingClassIds.size > 0) {
      setExpandedClasses(prev => {
        const newExpanded = new Set(prev);
        matchingClassIds.forEach(id => newExpanded.add(id));
        return newExpanded;
      });

      // Scroll to the first matching class after a short delay for DOM update
      setTimeout(() => {
        // Find the first class in sorted order that has matches
        const sortedClassIds = Array.from(matchingClassIds).sort((a, b) => {
          const classA = classes.find(c => c.id === a);
          const classB = classes.find(c => c.id === b);
          const orderA = classA ? getClassSortOrder(classA.name) : (a === 'unassigned' ? 99 : 50);
          const orderB = classB ? getClassSortOrder(classB.name) : (b === 'unassigned' ? 99 : 50);
          return orderA - orderB;
        });

        if (sortedClassIds.length > 0) {
          const firstMatchingClassId = sortedClassIds[0];
          const element = classRefs.current.get(firstMatchingClassId);
          if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }
      }, 100);
    }
  }, [searchQuery, loading]);

  async function fetchStudents() {
    setLoading(true);
    try {
      const studentsData = await api.get<Student[]>(`/api/students?status=${statusFilter}`);
      setStudents(studentsData);
      // Expand all classes by default
      const allClassIds = new Set(classes.map((c: any) => c.id));
      allClassIds.add('unassigned');
      setExpandedClasses(allClassIds);
    } catch (error: any) {
      console.error("Fetch Error:", error);
      toast.error("Failed to load data");
    } finally {
      setLoading(false);
    }
  }

  async function fetchInitialData() {
    setLoading(true);
    try {
      const [classesData, studentsData] = await Promise.all([
        api.get<any[]>('/api/classes/simple'),
        api.get<Student[]>(`/api/students?status=${statusFilter}`)
      ]);
      setClasses(classesData);
      setStudents(studentsData);
      // Expand all classes by default
      const allClassIds = new Set(classesData.map((c: any) => c.id));
      allClassIds.add('unassigned');
      setExpandedClasses(allClassIds);
    } catch (error: any) {
      console.error("Fetch Error:", error);
      toast.error("Failed to load data");
    } finally {
      setLoading(false);
    }
  }

  async function loadPromotionCandidates(fromClassId: string) {
    if (!fromClassId) {
      setPromotionCandidates([]);
      setSelectedPromotionStudentIds(new Set());
      return;
    }

    setPromotionCandidatesLoading(true);
    try {
      const classStudents = await api.get<Student[]>(`/api/students?class_id=${encodeURIComponent(fromClassId)}`);
      const candidates = classStudents
        .map((student) => {
          const fullName = `${student.profiles?.first_name ?? ""} ${student.profiles?.last_name ?? ""}`.trim();
          return {
            id: student.id,
            fullName: fullName || "Unnamed Student",
            rollNumber: student.roll_number === null || student.roll_number === undefined
              ? "N/A"
              : String(student.roll_number).trim() || "N/A",
            enrollmentDate: student.enrollment_date,
          };
        })
        .sort((a, b) => {
          const rollA = parseNumericRoll(a.rollNumber === "N/A" ? undefined : a.rollNumber);
          const rollB = parseNumericRoll(b.rollNumber === "N/A" ? undefined : b.rollNumber);

          if (rollA !== null && rollB !== null && rollA !== rollB) {
            return rollA - rollB;
          }
          if (rollA !== null && rollB === null) return -1;
          if (rollA === null && rollB !== null) return 1;
          return new Date(a.enrollmentDate).getTime() - new Date(b.enrollmentDate).getTime();
        });

      setPromotionCandidates(candidates.map(({ enrollmentDate, ...candidate }) => candidate));
      setSelectedPromotionStudentIds(new Set(candidates.map((candidate) => candidate.id)));
    } catch (error: any) {
      toast.error(error.message || "Failed to load promotion candidates");
      setPromotionCandidates([]);
      setSelectedPromotionStudentIds(new Set());
    } finally {
      setPromotionCandidatesLoading(false);
    }
  }

  const handlePromotionFromClassChange = async (nextClassId: string) => {
    setPromotionFromClassId(nextClassId);
    const autoSelectedToClassId = getNextClassIdForPromotion(nextClassId, classes);
    setPromotionToClassId(autoSelectedToClassId);
    await loadPromotionCandidates(nextClassId);
  };

  const togglePromotionSelection = (studentId: string) => {
    setSelectedPromotionStudentIds((previous) => {
      const next = new Set(previous);
      if (next.has(studentId)) {
        next.delete(studentId);
      } else {
        next.add(studentId);
      }
      return next;
    });
  };

  const selectAllPromotionCandidates = () => {
    setSelectedPromotionStudentIds(new Set(promotionCandidates.map((student) => student.id)));
  };

  const unselectAllPromotionCandidates = () => {
    setSelectedPromotionStudentIds(new Set());
  };

  const handleBulkPromotion = async () => {
    if (!promotionFromClassId || !promotionToClassId) {
      toast.error("Please select both source and target classes");
      return;
    }
    if (promotionFromClassId === promotionToClassId) {
      toast.error("Source and target classes must be different");
      return;
    }
    if (!promotionAcademicYear.trim()) {
      toast.error("Please provide an academic year");
      return;
    }
    if (promotionCandidates.length === 0) {
      toast.error("No students available in the selected source class");
      return;
    }

    setPromotionSubmitting(true);
    try {
      const response = await api.post<BulkPromotionResponse>('/api/students/promotions/bulk', {
        fromClassId: promotionFromClassId,
        toClassId: promotionToClassId,
        academicYear: promotionAcademicYear.trim(),
        promotedStudentIds: Array.from(selectedPromotionStudentIds),
        reassignRetainedRollNumbers,
      });

      toast.success(
        `Promotion complete: ${response.promotedCount} promoted, ${response.retainedCount} retained`
      );
      await fetchInitialData();
      await loadPromotionCandidates(promotionFromClassId);
    } catch (error: any) {
      toast.error(error.message || "Failed to process bulk promotion");
    } finally {
      setPromotionSubmitting(false);
    }
  };

  const handleDelete = async (studentId: string) => {
    if (!confirm("Are you sure? This will remove the student record.")) return;

    try {
      await api.delete(`/api/students/${studentId}`);
      toast.success("Student removed from directory");
      setStudents(students.filter(s => s.id !== studentId));
      if (selectedStudent?.id === studentId) {
        setSelectedStudent(null);
      }
    } catch (error: any) {
      toast.error("Delete failed: " + error.message);
    }
  };

  const handleStatusUpdate = async (studentId: string, newStatus: 'graduated' | 'left') => {
    const actionLabel = newStatus === 'graduated' ? 'pass out this student' : 'mark this student as left';
    if (!confirm(`Are you sure you want to ${actionLabel}? They will be removed from their class.`)) return;

    setStatusUpdating(true);
    try {
      await api.post('/api/students/status/update', {
        studentIds: [studentId],
        status: newStatus,
      });
      toast.success(
        newStatus === 'graduated'
          ? 'Student passed out successfully'
          : 'Student marked as left'
      );
      setSelectedStudent(null);
      await fetchStudents();
    } catch (error: any) {
      toast.error(error.message || `Failed to update student status`);
    } finally {
      setStatusUpdating(false);
    }
  };

  const handleStudentClick = async (studentId: string) => {
    setDetailLoading(true);
    try {
      const data = await api.get<StudentDetail>(`/api/students/${studentId}`);
      setSelectedStudent(data);
    } catch (error: any) {
      toast.error("Failed to load student details");
    } finally {
      setDetailLoading(false);
    }
  };

  const toggleClass = (classId: string) => {
    const newExpanded = new Set(expandedClasses);
    if (newExpanded.has(classId)) {
      newExpanded.delete(classId);
    } else {
      newExpanded.add(classId);
    }
    setExpandedClasses(newExpanded);
  };

  const expandAll = () => {
    const allClassIds = new Set(classes.map(c => c.id));
    allClassIds.add('unassigned');
    setExpandedClasses(allClassIds);
  };

  const collapseAll = () => {
    setExpandedClasses(new Set());
  };

  // Filter students based on search query
  const filteredStudents = students.filter(s => {
    if (!searchQuery.trim()) return true;
    const query = searchQuery.toLowerCase().trim();
    const firstName = (s.profiles?.first_name || '').toLowerCase();
    const lastName = (s.profiles?.last_name || '').toLowerCase();
    const fullName = `${firstName} ${lastName}`;
    const className = (s.classes?.name || '').toLowerCase();

    return firstName.includes(query) ||
           lastName.includes(query) ||
           fullName.includes(query) ||
           className.includes(query);
  });

  // Group students by class and sort classes properly
  const groupedStudents = (): ClassGroup[] => {
    const groups: Map<string, ClassGroup> = new Map();

    // Initialize groups for all classes
    classes.forEach(c => {
      groups.set(c.id, {
        id: c.id,
        name: c.name,
        students: [],
        sortOrder: getClassSortOrder(c.name)
      });
    });

    // Add unassigned group
    groups.set('unassigned', {
      id: 'unassigned',
      name: 'Unassigned',
      students: [],
      sortOrder: 99
    });

    // Distribute students
    filteredStudents.forEach(student => {
      const classId = student.classes?.id || 'unassigned';
      const group = groups.get(classId);
      if (group) {
        group.students.push(student);
      } else {
        groups.get('unassigned')?.students.push(student);
      }
    });

    // Convert to array, sort by class order, and filter out empty classes
    return Array.from(groups.values())
      .filter(g => g.students.length > 0 || g.id !== 'unassigned')
      .sort((a, b) => a.sortOrder - b.sortOrder);
  };

  const classGroups = groupedStudents();
  const totalStudents = filteredStudents.length;
  const promotedCount = selectedPromotionStudentIds.size;
  const retainedCount = Math.max(promotionCandidates.length - promotedCount, 0);
  const promotionTargetClasses = (() => {
    if (!promotionFromClassId) return [];
    const fromClass = classes.find((c) => c.id === promotionFromClassId);
    if (!fromClass) return [];
    const fromOrder = getClassSortOrder(fromClass.name);
    const targetClasses = classes
      .filter((classItem) => getClassSortOrder(classItem.name) > fromOrder)
      .sort((a, b) => getClassSortOrder(a.name) - getClassSortOrder(b.name));
    
    targetClasses.push({ id: 'graduate', name: '🎓 Pass Out (Remove from Class)' } as any);
    
    return targetClasses;
  })();

  const getInitials = (firstName?: string, lastName?: string) => {
    return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase() || '?';
  };

  // Brand colors: Purple (#8B5CF6), Gold (#EAB308), Green (#16A34A)
  const brandColors = {
    purple: { bg: 'bg-orange-100', text: 'text-orange-600', border: 'border-orange-200', solid: 'bg-orange-500' },
    gold: { bg: 'bg-amber-100', text: 'text-amber-600', border: 'border-amber-200', solid: 'bg-amber-500' },
    green: { bg: 'bg-green-100', text: 'text-green-600', border: 'border-green-200', solid: 'bg-green-500' },
  };

  const getColorForIndex = (index: number) => {
    const colors = [brandColors.purple, brandColors.gold, brandColors.green];
    return colors[index % 3];
  };

  const getAvatarColor = (index: number) => {
    const colors = ['bg-orange-500', 'bg-amber-500', 'bg-green-500'];
    return colors[index % 3];
  };

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header Section */}
      <div className="flex flex-col gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">Student Directory</h1>
          <p className="text-gray-500 mt-1 text-sm sm:text-base">Manage and view all enrolled students by class.</p>
        </div>

        <div className="flex flex-col sm:flex-row gap-2 sm:self-start">
          <Button asChild className="gap-2 bg-orange-600 hover:bg-orange-700 shadow-sm rounded-xl h-10 sm:h-11 w-full sm:w-auto">
            <Link to="/dashboard/students/add">
              <UserPlus className="w-4 h-4" /> Add Student
            </Link>
          </Button>
          <Button
            variant="outline"
            className="gap-2 rounded-xl h-10 sm:h-11 border-orange-200 text-orange-700 hover:bg-orange-50"
            onClick={() => setShowPromotionPanel((previous) => !previous)}
          >
            <GraduationCap className="w-4 h-4" />
            {showPromotionPanel ? "Hide Promotion Panel" : "Promote Students"}
          </Button>
        </div>
      </div>

      {showPromotionPanel && (
        <Card className="border border-orange-200 bg-orange-50/40 rounded-2xl shadow-sm">
          <CardContent className="p-4 sm:p-5 space-y-4">
            <div>
              <h3 className="text-base sm:text-lg font-semibold text-gray-900">Annual Promotion</h3>
              <p className="text-sm text-gray-600">
                Select students who passed. Unselected students stay in the current class.
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div className="space-y-1">
                <label className="text-xs font-semibold text-gray-600 uppercase tracking-wide">From Class</label>
                <select
                  value={promotionFromClassId}
                  onChange={(event) => {
                    void handlePromotionFromClassChange(event.target.value);
                  }}
                  className="w-full h-10 rounded-xl border border-gray-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-200"
                >
                  <option value="">Select source class</option>
                  {[...classes].sort((a, b) => getClassSortOrder(a.name) - getClassSortOrder(b.name)).map((classItem) => (
                    <option key={classItem.id} value={classItem.id}>{classItem.name}</option>
                  ))}
                </select>
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-gray-600 uppercase tracking-wide">To Class (Auto)</label>
                <select
                  value={promotionToClassId}
                  onChange={(event) => setPromotionToClassId(event.target.value)}
                  className="w-full h-10 rounded-xl border border-gray-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-200"
                  disabled={!promotionFromClassId}
                >
                  <option value="">
                    {promotionFromClassId ? "No next class found" : "Select source class first"}
                  </option>
                  {promotionTargetClasses.map((classItem) => (
                    <option key={classItem.id} value={classItem.id}>{classItem.name}</option>
                  ))}
                </select>
              </div>

              <div className="space-y-1">
                <label className="text-xs font-semibold text-gray-600 uppercase tracking-wide">Academic Year</label>
                <Input
                  value={promotionAcademicYear}
                  onChange={(event) => setPromotionAcademicYear(event.target.value)}
                  placeholder="2026-2027"
                  className="h-10 rounded-xl bg-white"
                />
              </div>
            </div>

            <label className="inline-flex items-center gap-2 text-sm text-gray-700">
              <input
                type="checkbox"
                checked={reassignRetainedRollNumbers}
                onChange={(event) => setReassignRetainedRollNumbers(event.target.checked)}
                className="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-400"
              />
              Reassign roll numbers for retained students in source class
            </label>

            {promotionFromClassId && (
              <div className="rounded-xl border border-gray-200 bg-white">
                <div className="p-3 sm:p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div className="text-sm text-gray-600">
                    <span className="font-semibold text-green-700">{promotedCount}</span> selected for promotion,{" "}
                    <span className="font-semibold text-amber-700">{retainedCount}</span> retained
                  </div>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      className="rounded-lg text-xs"
                      onClick={selectAllPromotionCandidates}
                      disabled={promotionCandidatesLoading || promotionCandidates.length === 0}
                    >
                      Select All
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      className="rounded-lg text-xs"
                      onClick={unselectAllPromotionCandidates}
                      disabled={promotionCandidatesLoading || promotionCandidates.length === 0}
                    >
                      Unselect All
                    </Button>
                  </div>
                </div>

                <div className="max-h-72 overflow-y-auto divide-y divide-gray-100">
                  {promotionCandidatesLoading ? (
                    <div className="p-6 flex items-center justify-center gap-2 text-sm text-gray-500">
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Loading students...
                    </div>
                  ) : promotionCandidates.length === 0 ? (
                    <div className="p-6 text-sm text-gray-500 text-center">
                      No students found in the selected source class.
                    </div>
                  ) : (
                    promotionCandidates.map((student) => {
                      const isSelected = selectedPromotionStudentIds.has(student.id);
                      return (
                        <label
                          key={student.id}
                          className="px-3 sm:px-4 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 cursor-pointer"
                        >
                          <div className="flex items-center gap-3 min-w-0">
                            <input
                              type="checkbox"
                              checked={isSelected}
                              onChange={() => togglePromotionSelection(student.id)}
                              className="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-400 shrink-0"
                            />
                            <div className="min-w-0">
                              <p className="text-sm font-medium text-gray-800 truncate">{student.fullName}</p>
                              <p className="text-xs text-gray-500">Roll: {student.rollNumber}</p>
                            </div>
                          </div>
                          <span className={`text-xs font-semibold px-2 py-1 rounded-full ${
                            isSelected
                              ? "bg-green-100 text-green-700"
                              : "bg-amber-100 text-amber-700"
                          }`}>
                            {isSelected ? "Promote" : "Retain"}
                          </span>
                        </label>
                      );
                    })
                  )}
                </div>
              </div>
            )}

            <div className="flex justify-end">
              <Button
                className="rounded-xl bg-orange-600 hover:bg-orange-700"
                onClick={handleBulkPromotion}
                disabled={
                  promotionSubmitting ||
                  !promotionFromClassId ||
                  !promotionToClassId ||
                  promotionCandidates.length === 0
                }
              >
                {promotionSubmitting ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : null}
                Promote Selected Students
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

        {/* Stats Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
          <Card className="bg-orange-50/80 border border-black/5 hover:shadow-md transition-all duration-300 overflow-hidden relative">
            <div className="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 rounded-full bg-orange-100 mix-blend-multiply opacity-50 blur-2xl -translate-y-1/2 translate-x-1/3"></div>
            <CardContent className="p-4 sm:p-5 md:p-6 flex items-center justify-between relative z-10">
              <div>
                <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Students</p>
                <p className="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">
                  {loading ? (
                    <Loader2 className="w-6 h-6 sm:w-7 sm:h-7 animate-spin text-gray-400" />
                  ) : (
                    totalStudents
                  )}
                </p>
              </div>
              <div className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-2xl bg-orange-100 flex items-center justify-center shadow-sm">
                <Users className="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 text-orange-500" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-amber-50/80 border border-black/5 hover:shadow-md transition-all duration-300 overflow-hidden relative">
            <div className="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 rounded-full bg-amber-100 mix-blend-multiply opacity-50 blur-2xl -translate-y-1/2 translate-x-1/3"></div>
            <CardContent className="p-4 sm:p-5 md:p-6 flex items-center justify-between relative z-10">
              <div>
                <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Classes</p>
                <p className="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">
                  {loading ? (
                    <Loader2 className="w-6 h-6 sm:w-7 sm:h-7 animate-spin text-gray-400" />
                  ) : (
                    classGroups.filter(g => g.id !== 'unassigned').length
                  )}
                </p>
              </div>
              <div className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-2xl bg-amber-100 flex items-center justify-center shadow-sm">
                <School className="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 text-amber-500" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-green-50/80 border border-black/5 hover:shadow-md transition-all duration-300 overflow-hidden relative">
            <div className="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 rounded-full bg-green-100 mix-blend-multiply opacity-50 blur-2xl -translate-y-1/2 translate-x-1/3"></div>
            <CardContent className="p-4 sm:p-5 md:p-6 flex items-center justify-between relative z-10">
              <div>
                <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Avg per Class</p>
                <p className="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">
                  {loading ? (
                    <Loader2 className="w-6 h-6 sm:w-7 sm:h-7 animate-spin text-gray-400" />
                  ) : (
                    classGroups.filter(g => g.id !== 'unassigned').length > 0
                      ? Math.round(totalStudents / classGroups.filter(g => g.id !== 'unassigned').length)
                      : 0
                  )}
                </p>
              </div>
              <div className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-2xl bg-green-100 flex items-center justify-center shadow-sm">
                <GraduationCap className="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 text-green-500" />
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Search and Controls */}
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-3 sm:p-4">
            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 items-stretch sm:items-center justify-between">
              <div className="relative w-full sm:w-80 md:w-96 group">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-orange-500 transition-colors" />
                <Input
                  placeholder="Search by name or class..."
                  className="pl-10 bg-gray-50/80 hover:bg-gray-100/80 border-transparent rounded-xl focus:bg-white focus:border-orange-200 focus:ring-4 focus:ring-orange-500/10 transition-all h-10 sm:h-11"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery('')}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <X className="w-4 h-4" />
                  </button>
                )}
              </div>

              <div className="flex items-center gap-2 justify-end">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={expandAll}
                  className="rounded-xl border-gray-200 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200 text-xs sm:text-sm px-3"
                >
                  Expand All
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={collapseAll}
                  className="rounded-xl border-gray-200 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200 text-xs sm:text-sm px-3"
                >
                  Collapse All
                </Button>
              </div>
            </div>

            {/* Search results count */}
            {searchQuery && (
              <p className="text-sm text-gray-500 mt-3">
                Found <span className="font-semibold text-orange-600">{totalStudents}</span> student{totalStudents !== 1 ? 's' : ''} matching "{searchQuery}"
              </p>
            )}
          </CardContent>
        </Card>

        {/* Status Filter Tabs */}
        <div className="flex items-center gap-2 flex-wrap">
          <Filter className="w-4 h-4 text-gray-400" />
          {([
            { value: 'active' as const, label: 'Active', icon: Users, color: 'orange' },
            { value: 'graduated' as const, label: 'Pass Out', icon: Award, color: 'green' },
            { value: 'left' as const, label: 'Left School', icon: LogOut, color: 'red' },
            { value: 'all' as const, label: 'All', icon: Users, color: 'gray' },
          ]).map(({ value, label, icon: Icon, color }) => {
            const isActive = statusFilter === value;
            return (
              <button
                key={value}
                onClick={() => setStatusFilter(value)}
                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all ${
                  isActive
                    ? color === 'orange' ? 'bg-orange-100 text-orange-700 ring-1 ring-orange-300'
                    : color === 'green' ? 'bg-green-100 text-green-700 ring-1 ring-green-300'
                    : color === 'red' ? 'bg-red-100 text-red-700 ring-1 ring-red-300'
                    : 'bg-gray-100 text-gray-700 ring-1 ring-gray-300'
                    : 'bg-gray-50 text-gray-500 hover:bg-gray-100'
                }`}
              >
                <Icon className="w-3.5 h-3.5" />
                {label}
              </button>
            );
          })}
        </div>

        {/* Class-wise Student List */}
        {loading ? (
          <Card className="border-0 shadow-sm rounded-2xl bg-white">
            <CardContent className="p-8 sm:p-12 flex flex-col items-center justify-center">
              <Loader2 className="w-8 h-8 sm:w-10 sm:h-10 animate-spin text-orange-500 mb-4" />
              <p className="text-gray-500 font-medium">Loading students...</p>
            </CardContent>
          </Card>
        ) : classGroups.length === 0 ? (
          <Card className="border-0 shadow-sm rounded-2xl bg-white">
            <CardContent className="p-8 sm:p-12 flex flex-col items-center justify-center">
              <Users className="w-12 h-12 sm:w-16 sm:h-16 text-gray-200 mb-4" />
              <p className="text-base sm:text-lg font-semibold text-gray-700">No students found</p>
              <p className="text-gray-500 mt-1 text-sm sm:text-base text-center">
                {searchQuery ? "Try adjusting your search query." : "Add new students to get started."}
              </p>
              {!searchQuery && (
                <Button asChild className="mt-4 bg-orange-600 hover:bg-orange-700 rounded-xl">
                  <Link to="/dashboard/students/add">
                    <UserPlus className="w-4 h-4 mr-2" /> Add Student
                  </Link>
                </Button>
              )}
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-3 sm:space-y-4">
            {classGroups.map((group, index) => {
              const colors = getColorForIndex(index);

              return (
                <Card
                  key={group.id}
                  ref={(el) => {
                    if (el) classRefs.current.set(group.id, el);
                    else classRefs.current.delete(group.id);
                  }}
                  className="border-0 shadow-sm rounded-2xl bg-white overflow-hidden hover:shadow-md transition-shadow"
                >
                  {/* Class Header - No expand/collapse */}
                  <div className="px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between bg-gray-50/50 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <div className="flex items-center gap-3 sm:gap-4">
                      <div className={`w-10 h-10 sm:w-12 sm:h-12 rounded-xl ${colors.bg} flex items-center justify-center shadow-sm`}>
                        <School className={`w-5 h-5 sm:w-6 sm:h-6 ${colors.text}`} />
                      </div>
                      <div className="text-left">
                        <h3 className="text-base sm:text-lg font-bold text-gray-800">{group.name}</h3>
                        <p className="text-xs sm:text-sm text-gray-500 font-medium">{group.students.length} student{group.students.length !== 1 ? 's' : ''}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2 sm:gap-3">
                      <Button
                        variant="outline"
                        size="sm"
                        className="rounded-lg border-orange-200 text-orange-600 hover:bg-orange-50 text-xs sm:text-sm"
                        onClick={() => {
                          navigate(`/dashboard/students/class/${group.id}`);
                        }}
                      >
                        View Class
                      </Button>
                      <span className={`px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs sm:text-sm font-bold ${colors.bg} ${colors.text}`}>
                        {group.students.length}
                      </span>
                    </div>
                  </div>
                </Card>
              );
            })}
          </div>
        )}

        {/* Footer Summary */}
        {!loading && totalStudents > 0 && (
          <div className="text-center text-xs sm:text-sm text-gray-500 font-medium py-3 sm:py-4">
            Showing {totalStudents} student{totalStudents !== 1 ? 's' : ''} across {classGroups.filter(g => g.id !== 'unassigned').length} class{classGroups.filter(g => g.id !== 'unassigned').length !== 1 ? 'es' : ''}
          </div>
        )}

      {/* Student Detail Modal */}
      {(selectedStudent || detailLoading) && (
        <div
          className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
          onClick={() => !detailLoading && setSelectedStudent(null)}
        >
          <div
            className="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[90vh] overflow-y-auto animate-in slide-in-from-bottom-4 duration-300"
            onClick={(e) => e.stopPropagation()}
          >
            {detailLoading ? (
              <div className="p-8 sm:p-12 flex flex-col items-center justify-center">
                <Loader2 className="w-8 h-8 animate-spin text-orange-500 mb-4" />
                <p className="text-gray-500 font-medium">Loading student details...</p>
              </div>
            ) : selectedStudent && (
              <>
                {/* Modal Header */}
                <div className="relative bg-gradient-to-r from-orange-600 to-orange-700 p-6 sm:p-8 text-white">
                  <button
                    onClick={() => setSelectedStudent(null)}
                    className="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors"
                  >
                    <X className="w-4 h-4" />
                  </button>

                  <div className="flex items-center gap-4">
                    <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 flex items-center justify-center text-xl sm:text-2xl font-bold shadow-lg">
                      {getInitials(selectedStudent.firstName, selectedStudent.lastName)}
                    </div>
                    <div>
                      <h2 className="text-xl sm:text-2xl font-bold">
                        {selectedStudent.firstName} {selectedStudent.lastName}
                      </h2>
                      <div className="flex items-center gap-2 mt-1">
                        <p className="text-orange-200">{selectedStudent.className}</p>
                        {selectedStudent.status && selectedStudent.status !== 'active' && (
                          <span className={`text-xs font-bold px-2 py-0.5 rounded-full ${
                            selectedStudent.status === 'graduated'
                              ? 'bg-green-500/30 text-green-100'
                              : 'bg-red-500/30 text-red-100'
                          }`}>
                            {selectedStudent.status === 'graduated' ? 'Passed Out' : 'Left'}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Modal Content */}
                <div className="p-4 sm:p-6 space-y-4 sm:space-y-6">
                  {/* Contact Info */}
                  <div className="space-y-3">
                    <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Contact Information</h3>
                    <div className="space-y-2">
                      <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        <Mail className="w-5 h-5 text-orange-500" />
                        <span className="text-gray-700 text-sm sm:text-base truncate">{selectedStudent.email || 'Not available'}</span>
                      </div>
                    </div>
                  </div>

                  {/* Academic Info */}
                  <div className="space-y-3">
                    <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Academic Details</h3>
                    <div className="grid grid-cols-1 gap-3">
                      <div className="p-3 bg-orange-50 rounded-xl">
                        <p className="text-xs text-gray-500 mb-1">Class</p>
                        <p className="font-semibold text-orange-700">{selectedStudent.className}</p>
                      </div>
                      <div className="p-3 bg-green-50 rounded-xl">
                        <p className="text-xs text-gray-500 mb-1">Enrollment Date</p>
                        <p className="font-semibold text-green-700">
                          {new Date(selectedStudent.enrollmentDate).toLocaleDateString(undefined, {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                          })}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Attendance Stats */}
                  <div className="space-y-3">
                    <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Attendance Overview</h3>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                      <div className="p-3 bg-gray-50 rounded-xl text-center">
                        <p className="text-2xl font-bold text-gray-800">{selectedStudent.attendance.totalDays}</p>
                        <p className="text-xs text-gray-500 mt-1">Total Days</p>
                      </div>
                      <div className="p-3 bg-green-50 rounded-xl text-center">
                        <div className="flex items-center justify-center gap-1 mb-1">
                          <CheckCircle2 className="w-4 h-4 text-green-500" />
                        </div>
                        <p className="text-2xl font-bold text-green-600">{selectedStudent.attendance.presentDays}</p>
                        <p className="text-xs text-gray-500 mt-1">Present</p>
                      </div>
                      <div className="p-3 bg-red-50 rounded-xl text-center">
                        <div className="flex items-center justify-center gap-1 mb-1">
                          <XCircle className="w-4 h-4 text-red-500" />
                        </div>
                        <p className="text-2xl font-bold text-red-600">{selectedStudent.attendance.absentDays}</p>
                        <p className="text-xs text-gray-500 mt-1">Absent</p>
                      </div>
                      <div className="p-3 bg-amber-50 rounded-xl text-center">
                        <div className="flex items-center justify-center gap-1 mb-1">
                          <Clock className="w-4 h-4 text-amber-500" />
                        </div>
                        <p className="text-2xl font-bold text-amber-600">{selectedStudent.attendance.lateDays}</p>
                        <p className="text-xs text-gray-500 mt-1">Late</p>
                      </div>
                    </div>

                    {/* Attendance Progress */}
                    <div className="p-4 bg-gradient-to-r from-orange-50 to-green-50 rounded-xl">
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-sm font-medium text-gray-600">Attendance Rate</span>
                        <span className={`text-lg font-bold ${
                          selectedStudent.attendance.attendancePercentage >= 80 ? 'text-green-600' :
                          selectedStudent.attendance.attendancePercentage >= 60 ? 'text-amber-600' : 'text-red-600'
                        }`}>
                          {selectedStudent.attendance.attendancePercentage}%
                        </span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div
                          className={`h-full rounded-full transition-all duration-500 ${
                            selectedStudent.attendance.attendancePercentage >= 80 ? 'bg-green-500' :
                            selectedStudent.attendance.attendancePercentage >= 60 ? 'bg-amber-500' : 'bg-red-500'
                          }`}
                          style={{ width: `${selectedStudent.attendance.attendancePercentage}%` }}
                        />
                      </div>
                    </div>
                  </div>

                  {/* Actions */}
                  {selectedStudent.status === 'active' && (() => {
                    const currentOrder = getClassSortOrder(selectedStudent.className);
                    const maxOrder = classes.length > 0 ? Math.max(...classes.map(c => getClassSortOrder(c.name))) : 15;
                    const isGraduatingClass = currentOrder >= maxOrder && currentOrder !== 99;

                    return (
                      <div className="flex gap-2 pt-4 border-t border-gray-100">
                        {isGraduatingClass && (
                          <Button
                            variant="outline"
                            size="sm"
                            className="flex-1 rounded-xl border-green-200 text-green-700 hover:bg-green-50"
                            onClick={() => handleStatusUpdate(selectedStudent!.id, 'graduated')}
                            disabled={statusUpdating}
                          >
                            {statusUpdating ? <Loader2 className="w-4 h-4 mr-1 animate-spin" /> : <Award className="w-4 h-4 mr-1" />}
                            Pass Out
                          </Button>
                        )}
                        <Button
                          variant="outline"
                          size="sm"
                          className="flex-1 rounded-xl border-red-200 text-red-700 hover:bg-red-50"
                          onClick={() => handleStatusUpdate(selectedStudent!.id, 'left')}
                          disabled={statusUpdating}
                        >
                          {statusUpdating ? <Loader2 className="w-4 h-4 mr-1 animate-spin" /> : <LogOut className="w-4 h-4 mr-1" />}
                          Mark as Left
                        </Button>
                      </div>
                    );
                  })()}

                  {selectedStudent.status && selectedStudent.status !== 'active' && (
                    <div className="pt-4 border-t border-gray-100">
                      <div className={`p-3 sm:p-4 rounded-xl flex items-center gap-3 ${
                        selectedStudent.status === 'graduated' ? 'bg-green-50 text-green-700 border border-green-200' :
                        'bg-red-50 text-red-700 border border-red-200'
                      }`}>
                        {selectedStudent.status === 'graduated' ? <Award className="w-5 h-5 sm:w-6 sm:h-6" /> : <LogOut className="w-5 h-5 sm:w-6 sm:h-6" />}
                        <div>
                          <h4 className="font-semibold text-sm sm:text-base">
                            Student has {selectedStudent.status === 'graduated' ? 'Passed Out' : 'Left School'}
                          </h4>
                          <p className="text-xs sm:text-sm opacity-80">This student is no longer active in the current academic session.</p>
                        </div>
                      </div>
                    </div>
                  )}

                  <div className="flex gap-3 pt-3 border-t border-gray-100">
                    <Button
                      variant="outline"
                      className="flex-1 rounded-xl border-gray-200 hover:bg-gray-50"
                      onClick={() => setSelectedStudent(null)}
                    >
                      Close
                    </Button>
                    <Button
                      variant="outline"
                      className="flex-1 rounded-xl border-orange-200 hover:bg-orange-50 hover:text-orange-600"
                      onClick={() => {
                        if (selectedStudent) {
                          generateStudentPDF(selectedStudent, "");
                          toast.success("PDF downloaded successfully");
                          toast.info("Tip: Original credentials were provided at enrollment time");
                        }
                      }}
                    >
                      <Download className="w-4 h-4 mr-2" /> Download PDF
                    </Button>
                    <Button
                      variant="destructive"
                      className="flex-1 rounded-xl"
                      onClick={() => {
                        handleDelete(selectedStudent!.id);
                      }}
                    >
                      <Trash2 className="w-4 h-4 mr-2" /> Remove
                    </Button>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
