import { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { generateBatchPDF, generateClassPDF, generateStudentPDF } from "@/lib/pdfGenerator";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
  ArrowLeft,
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
  Download,
  Award,
  LogOut,
} from "lucide-react";
import { toast } from "sonner";

interface Student {
  id: string;
  user_id: string;
  enrollment_date: string;
  roll_number?: string;
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
  status?: 'active' | 'graduated' | 'left';
  attendance: {
    totalDays: number;
    presentDays: number;
    absentDays: number;
    lateDays: number;
    attendancePercentage: number;
  };
}

interface BatchGroup {
  name: string;
  year: number;
  students: Student[];
}

function groupStudentsByBatch(students: Student[]): BatchGroup[] {
  if (!students || students.length === 0) return [];

  // Group students by year from enrollment_date
  const batchMap: Map<number, Student[]> = new Map();

  students.forEach((student) => {
    const enrollmentDate = new Date(student.enrollment_date);
    const year = enrollmentDate.getFullYear();

    if (!batchMap.has(year)) {
      batchMap.set(year, []);
    }
    batchMap.get(year)!.push(student);
  });

  // Convert to array and sort by year (descending)
  return Array.from(batchMap.entries())
    .map(([year, students]) => ({
      name: `${year} Batch`,
      year,
      students: students.sort((a, b) => {
        const aName = `${a.profiles?.first_name} ${a.profiles?.last_name}`;
        const bName = `${b.profiles?.first_name} ${b.profiles?.last_name}`;
        return aName.localeCompare(bName);
      }),
    }))
    .sort((a, b) => b.year - a.year);
}

export default function ClassDetail() {
  const { classId } = useParams<{ classId: string }>();
  const navigate = useNavigate();
  const [students, setStudents] = useState<Student[]>([]);
  const [classInfo, setClassInfo] = useState<any>(null);
  const [classes, setClasses] = useState<any[]>([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [loading, setLoading] = useState(true);
  const [expandedBatches, setExpandedBatches] = useState<Set<string>>(new Set());
  const [selectedStudent, setSelectedStudent] = useState<StudentDetail | null>(
    null
  );
  const [detailLoading, setDetailLoading] = useState(false);
  const [statusUpdating, setStatusUpdating] = useState(false);

  useEffect(() => {
    fetchClassData();
  }, [classId]);

  // Expand all batches by default when data loads
  useEffect(() => {
    if (students.length > 0 && expandedBatches.size === 0) {
      const batches = groupStudentsByBatch(students);
      setExpandedBatches(new Set(batches.map((b) => b.name)));
    }
  }, [students]);

  async function fetchClassData() {
    if (!classId) return;

    setLoading(true);
    try {
      const isUnassigned = classId === 'unassigned';
      const [classData, studentsData, classesData] = await Promise.all([
        isUnassigned
          ? Promise.resolve({ id: 'unassigned', name: 'Unassigned Students', description: 'Students who have left school or passed out.' })
          : api.get<any>(`/api/classes/${classId}`),
        api.get<Student[]>(`/api/students?class_id=${classId}&status=all`),
        api.get<any[]>('/api/classes/simple'),
      ]);
      setClassInfo(classData);
      setStudents(studentsData);
      setClasses(classesData);
    } catch (error: any) {
      console.error("Fetch Error:", error);
      toast.error("Failed to load class data");
    } finally {
      setLoading(false);
    }
  }

  const handleDelete = async (studentId: string) => {
    if (!confirm("Are you sure? This will remove the student record.")) return;

    try {
      await api.delete(`/api/students/${studentId}`);
      toast.success("Student removed");
      setStudents(students.filter((s) => s.id !== studentId));
      if (selectedStudent?.id === studentId) {
        setSelectedStudent(null);
      }
    } catch (error: any) {
      toast.error("Delete failed: " + error.message);
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
      await fetchClassData();
    } catch (error: any) {
      toast.error(error.message || 'Failed to update student status');
    } finally {
      setStatusUpdating(false);
    }
  };

  const toggleBatch = (batchName: string) => {
    const newExpanded = new Set(expandedBatches);
    if (newExpanded.has(batchName)) {
      newExpanded.delete(batchName);
    } else {
      newExpanded.add(batchName);
    }
    setExpandedBatches(newExpanded);
  };

  const expandAll = () => {
    const batches = groupStudentsByBatch(filteredStudents);
    setExpandedBatches(new Set(batches.map((b) => b.name)));
  };

  const collapseAll = () => {
    setExpandedBatches(new Set());
  };

  // Filter students based on search query
  const filteredStudents = students.filter((s) => {
    if (!searchQuery.trim()) return true;
    const query = searchQuery.toLowerCase().trim();
    const firstName = (s.profiles?.first_name || "").toLowerCase();
    const lastName = (s.profiles?.last_name || "").toLowerCase();
    const fullName = `${firstName} ${lastName}`;

    return (
      firstName.includes(query) ||
      lastName.includes(query) ||
      fullName.includes(query)
    );
  });

  const batchGroups = groupStudentsByBatch(filteredStudents);
  const totalStudents = filteredStudents.length;

  const getInitials = (firstName?: string, lastName?: string) => {
    return `${firstName?.charAt(0) || ""}${lastName?.charAt(0) || ""}`.toUpperCase() || "?";
  };

  const getAvatarColor = (index: number) => {
    const colors = ["bg-orange-500", "bg-amber-500", "bg-green-500"];
    return colors[index % 3];
  };

  const handleDownloadClass = async () => {
    try {
      if (!students || students.length === 0) {
        toast.error("No students to download");
        return;
      }

      console.log('[handleDownloadClass] Downloading for', students.length, 'students');
      // Generate PDF with all students
      generateClassPDF(classInfo?.name || "Class", students);
      toast.success("Class PDF downloaded successfully");
    } catch (error: any) {
      console.error('[handleDownloadClass] Error:', error);
      toast.error("Download failed: " + (error?.message || "Unknown error"));
    }
  };

  const handleDownloadBatch = (batchName: string) => {
    try {
      const batch = batchGroups.find((b) => b.name === batchName);
      if (!batch || batch.students.length === 0) {
        toast.error("No students in batch");
        return;
      }

      // Generate PDF for batch with all details
      generateBatchPDF(batchName, classInfo?.name || "Class", batch.students);
      toast.success(`${batchName} PDF downloaded successfully`);
    } catch (error: any) {
      toast.error("Download failed: " + error.message);
    }
  };

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header Section */}
      <div className="flex flex-col gap-4">
        <div className="flex items-center gap-3 sm:gap-4">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => navigate("/dashboard/students")}
            className="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl"
          >
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
              {loading ? (
                <Loader2 className="w-8 h-8 animate-spin text-orange-500" />
              ) : (
                classInfo?.name || "Class"
              )}
            </h1>
            <p className="text-gray-500 mt-1 text-sm sm:text-base">
              Manage students by batch
            </p>
          </div>
        </div>

        {!loading && (
          <div className="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <Button
              onClick={handleDownloadClass}
              className="gap-2 bg-green-600 hover:bg-green-700 shadow-sm rounded-xl h-10 sm:h-11"
            >
              <Download className="w-4 h-4" /> Download Full Class
            </Button>
          </div>
        )}
      </div>

      {/* Stats Card */}
      <Card className="bg-orange-50/80 border border-black/5 hover:shadow-md transition-all duration-300 overflow-hidden relative">
        <div className="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 rounded-full bg-orange-100 mix-blend-multiply opacity-50 blur-2xl -translate-y-1/2 translate-x-1/3"></div>
        <CardContent className="p-4 sm:p-5 md:p-6 flex items-center justify-between relative z-10">
          <div>
            <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">
              Students in {batchGroups.length > 0 && expandedBatches.size > 0 ? "This Batch" : "This Class"}
            </p>
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

      {/* Search and Controls */}
      <Card className="border-0 shadow-sm rounded-2xl bg-white">
        <CardContent className="p-3 sm:p-4">
          <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 items-stretch sm:items-center justify-between">
            <div className="relative w-full sm:w-80 md:w-96 group">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-orange-500 transition-colors" />
              <Input
                placeholder="Search students..."
                className="pl-10 bg-gray-50/80 hover:bg-gray-100/80 border-transparent rounded-xl focus:bg-white focus:border-orange-200 focus:ring-4 focus:ring-orange-500/10 transition-all h-10 sm:h-11"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
              {searchQuery && (
                <button
                  onClick={() => setSearchQuery("")}
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

          {searchQuery && (
            <p className="text-sm text-gray-500 mt-3">
              Found <span className="font-semibold text-orange-600">{totalStudents}</span> student
              {totalStudents !== 1 ? "s" : ""} matching "{searchQuery}"
            </p>
          )}
        </CardContent>
      </Card>

      {/* Batch-wise Student List */}
      {loading ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 sm:p-12 flex flex-col items-center justify-center">
            <Loader2 className="w-8 h-8 sm:w-10 sm:h-10 animate-spin text-orange-500 mb-4" />
            <p className="text-gray-500 font-medium">Loading students...</p>
          </CardContent>
        </Card>
      ) : batchGroups.length === 0 ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 sm:p-12 flex flex-col items-center justify-center">
            <Users className="w-12 h-12 sm:w-16 sm:h-16 text-gray-200 mb-4" />
            <p className="text-base sm:text-lg font-semibold text-gray-700">
              No students found
            </p>
            <p className="text-gray-500 mt-1 text-sm sm:text-base text-center">
              {searchQuery ? "Try adjusting your search query." : "No students in this class."}
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-3 sm:space-y-4">
          {batchGroups.map((batch, batchIndex) => {
            const isExpanded = expandedBatches.has(batch.name);
            const batchColors = [
              {
                bg: "bg-orange-100",
                text: "text-orange-600",
                border: "border-orange-200",
              },
              {
                bg: "bg-amber-100",
                text: "text-amber-600",
                border: "border-amber-200",
              },
              {
                bg: "bg-green-100",
                text: "text-green-600",
                border: "border-green-200",
              },
            ];
            const colors = batchColors[batchIndex % 3];

            return (
              <Card
                key={batch.name}
                className="border-0 shadow-sm rounded-2xl bg-white overflow-hidden hover:shadow-md transition-shadow"
              >
                  {/* Batch Header */}
                  <div className="px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between bg-gray-50/50 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <button
                      onClick={() => toggleBatch(batch.name)}
                      className="flex-1 flex items-center gap-3 sm:gap-4 text-left"
                    >
                      <div
                        className={`w-10 h-10 sm:w-12 sm:h-12 rounded-xl ${colors.bg} flex items-center justify-center shadow-sm`}
                      >
                        <School className={`w-5 h-5 sm:w-6 sm:h-6 ${colors.text}`} />
                      </div>
                      <div>
                        <h3 className="text-base sm:text-lg font-bold text-gray-800">
                          {batch.name}
                        </h3>
                        <p className="text-xs sm:text-sm text-gray-500 font-medium">
                          {batch.students.length} student
                          {batch.students.length !== 1 ? "s" : ""}
                        </p>
                      </div>
                    </button>

                    <div className="flex items-center gap-2 sm:gap-3">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleDownloadBatch(batch.name)}
                        className="text-green-600 border-green-200 hover:bg-green-50 rounded-lg text-xs sm:text-sm"
                        title="Download batch details as PDF"
                      >
                        <Download className="w-4 h-4 mr-1" /> Download
                      </Button>
                      <span
                        className={`px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-xs sm:text-sm font-bold ${colors.bg} ${colors.text}`}
                      >
                        {batch.students.length}
                      </span>
                      {isExpanded ? (
                        <ChevronDown className="w-4 h-4 sm:w-5 sm:h-5 text-gray-400" />
                      ) : (
                        <ChevronRight className="w-4 h-4 sm:w-5 sm:h-5 text-gray-400" />
                      )}
                    </div>
                  </div>

                {/* Student List */}
                {isExpanded && (
                  <CardContent className="p-0">
                    <div className="divide-y divide-gray-50">
                      {batch.students.map((student, sIndex) => (
                        <div
                          key={student.id}
                          onClick={() => handleStudentClick(student.id)}
                          className="px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between hover:bg-orange-50/30 transition-colors group cursor-pointer"
                        >
                          <div className="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                            <div
                              className={`w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold text-white shadow-sm shrink-0 ${getAvatarColor(sIndex)}`}
                            >
                              {getInitials(
                                student.profiles?.first_name,
                                student.profiles?.last_name
                              )}
                            </div>
                            <div className="min-w-0 flex-1">
                              <p className="font-semibold text-gray-800 text-sm sm:text-base truncate">
                                {student.profiles?.first_name}{" "}
                                {student.profiles?.last_name}
                              </p>
                              <div className="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                <Calendar className="w-3 h-3 shrink-0" />
                                <span className="truncate">
                                  {new Date(
                                    student.enrollment_date
                                  ).toLocaleDateString(undefined, {
                                    year: "numeric",
                                    month: "short",
                                    day: "numeric",
                                  })}
                                </span>
                              </div>
                            </div>
                          </div>
                          <div className="flex items-center gap-2">
                            <span className="hidden sm:inline-flex text-xs text-orange-600 font-medium bg-orange-50 px-2 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                              View Details
                            </span>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all rounded-xl w-8 h-8 sm:w-9 sm:h-9"
                              onClick={(e) => {
                                e.stopPropagation();
                                handleDelete(student.id);
                              }}
                            >
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                )}
              </Card>
            );
          })}
        </div>
      )}

      {/* Student Detail Modal */}
      {(selectedStudent || detailLoading) && (
        <div
          className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
          onClick={() => !detailLoading && setSelectedStudent(null)}
        >
          <div
            className="bg-white w-full sm:max-w-2xl sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[90vh] overflow-y-auto animate-in slide-in-from-bottom-4 duration-300"
            onClick={(e) => e.stopPropagation()}
          >
            {detailLoading ? (
              <div className="p-8 sm:p-12 flex flex-col items-center justify-center">
                <Loader2 className="w-8 h-8 animate-spin text-orange-500 mb-4" />
                <p className="text-gray-500 font-medium">
                  Loading student details...
                </p>
              </div>
            ) : selectedStudent ? (
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
                      {getInitials(
                        selectedStudent.firstName,
                        selectedStudent.lastName
                      )}
                    </div>
                    <div>
                      <h2 className="text-xl sm:text-2xl font-bold">
                        {selectedStudent.firstName} {selectedStudent.lastName}
                      </h2>
                      <p className="text-orange-200 mt-1">
                        {selectedStudent.className}
                      </p>
                      {selectedStudent.status && selectedStudent.status !== 'active' && (
                        <span className={`inline-block text-xs font-bold px-2 py-0.5 rounded-full mt-1 ${
                          selectedStudent.status === 'graduated'
                            ? 'bg-green-500/30 text-green-100'
                            : 'bg-red-500/30 text-red-100'
                        }`}>
                          {selectedStudent.status === 'graduated' ? 'Passed Out' : 'Left'}
                        </span>
                      )}
                      {selectedStudent.rollNumber && (
                        <p className="text-orange-100 text-sm mt-1">
                          Roll Number: {selectedStudent.rollNumber}
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                {/* Modal Content */}
                <div className="p-4 sm:p-6 space-y-4 sm:space-y-6">
                  {/* Contact Information */}
                  <div className="space-y-3">
                    <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Contact Information</h3>
                    <div className="space-y-2">
                      <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        <Mail className="w-5 h-5 text-orange-500 shrink-0" />
                        <div>
                          <p className="text-xs text-gray-500">Email</p>
                          <span className="text-gray-700 text-sm font-medium block truncate">
                            {selectedStudent.email || "Not available"}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Academic Information */}
                  <div className="space-y-3">
                    <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Academic Details</h3>
                    <div className="grid grid-cols-2 gap-3">
                      <div className="p-3 bg-orange-50 rounded-xl">
                        <p className="text-xs text-gray-500 mb-1">Class</p>
                        <p className="font-semibold text-orange-700 text-sm">{selectedStudent.className}</p>
                      </div>
                      <div className="p-3 bg-amber-50 rounded-xl">
                        <p className="text-xs text-gray-500 mb-1">Roll Number</p>
                        <p className="font-semibold text-amber-700 text-sm">{selectedStudent.rollNumber || 'N/A'}</p>
                      </div>
                      <div className="p-3 bg-green-50 rounded-xl">
                        <p className="text-xs text-gray-500 mb-1">Enrollment Date</p>
                        <p className="font-semibold text-green-700 text-sm">
                          {new Date(selectedStudent.enrollmentDate).toLocaleDateString(undefined, {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                          })}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Personal Information */}
                  {selectedStudent.dob && (
                    <div className="space-y-3">
                      <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Personal Information</h3>
                      <div className="p-3 bg-gray-50 rounded-xl">
                        <p className="text-xs text-gray-500 mb-1">Date of Birth</p>
                        <p className="font-semibold text-gray-700 text-sm">
                          {new Date(selectedStudent.dob).toLocaleDateString(undefined, {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                          })}
                        </p>
                      </div>
                    </div>
                  )}

                  {/* Attendance Information */}
                  {selectedStudent.attendance.totalDays > 0 && (
                    <div className="space-y-3">
                      <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Attendance Overview</h3>
                      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div className="p-3 bg-gray-50 rounded-xl text-center">
                          <p className="text-2xl font-bold text-gray-800">{selectedStudent.attendance.totalDays}</p>
                          <p className="text-xs text-gray-500 mt-1">Total Days</p>
                        </div>
                        <div className="p-3 bg-green-50 rounded-xl text-center">
                          <p className="text-2xl font-bold text-green-600">{selectedStudent.attendance.presentDays}</p>
                          <p className="text-xs text-gray-500 mt-1">Present</p>
                        </div>
                        <div className="p-3 bg-red-50 rounded-xl text-center">
                          <p className="text-2xl font-bold text-red-600">{selectedStudent.attendance.absentDays}</p>
                          <p className="text-xs text-gray-500 mt-1">Absent</p>
                        </div>
                        <div className="p-3 bg-amber-50 rounded-xl text-center">
                          <p className="text-2xl font-bold text-amber-600">{selectedStudent.attendance.lateDays}</p>
                          <p className="text-xs text-gray-500 mt-1">Late</p>
                        </div>
                      </div>

                      {/* Attendance Progress Bar */}
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
                  )}

                  {/* Graduate / Mark as Left */}
                  {(!selectedStudent.status || selectedStudent.status === 'active') && (() => {
                    const CLASS_ORDER: Record<string, number> = {
                      'nursery': 1, 'kg': 2, 'kindergarten': 2, 'lkg': 2, 'ukg': 3,
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
                    const getOrder = (name: string) => {
                      const lower = name.toLowerCase().trim();
                      if (CLASS_ORDER[lower] !== undefined) return CLASS_ORDER[lower];
                      const numMatch = lower.match(/(\d+)/);
                      if (numMatch) {
                        const num = parseInt(numMatch[1]);
                        if (num >= 1 && num <= 12) return num + 3;
                      }
                      return 50;
                    };
                    
                    const currentOrder = getOrder(selectedStudent.className);
                    const maxOrder = classes.length > 0 ? Math.max(...classes.map(c => getOrder(c.name))) : 15;
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

                  {/* Action Buttons */}
                  <div className="flex flex-col sm:flex-row gap-3 pt-3 border-t border-gray-100">
                    <Button
                      variant="outline"
                      className="flex-1 rounded-xl border-gray-200 hover:bg-gray-50"
                      onClick={() => setSelectedStudent(null)}
                    >
                      Close
                    </Button>
                    <Button
                      className="flex-1 rounded-xl bg-green-600 hover:bg-green-700 text-white"
                      onClick={() => {
                        if (selectedStudent) {
                          generateStudentPDF(selectedStudent);
                          toast.success("PDF downloaded successfully");
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
                        setSelectedStudent(null);
                      }}
                    >
                      <Trash2 className="w-4 h-4 mr-2" /> Remove
                    </Button>
                  </div>
                </div>
              </>
            ) : null}
          </div>
        </div>
      )}
    </div>
  );
}
