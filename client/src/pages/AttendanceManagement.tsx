import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import {
  ClipboardCheck,
  Loader2,
  Users,
  CheckCircle2,
  XCircle,
  Clock,
  ChevronDown,
  ChevronRight,
  FileText,
  Upload,
  Eye,
  AlertCircle
} from "lucide-react";
import { toast } from "sonner";

interface ClassItem {
  id: string;
  name: string;
}

interface StudentAttendance {
  id: string;
  user_id: string;
  profiles: {
    first_name: string;
    last_name: string;
  };
  attendance: {
    status: 'Present' | 'Absent' | 'Late' | 'Half Leave' | 'Full Leave';
    leave_application_url: string | null;
  } | null;
}

export default function AttendanceManagement() {
  const [classes, setClasses] = useState<ClassItem[]>([]);
  const [selectedClassId, setSelectedClassId] = useState<string>("");
  const [students, setStudents] = useState<StudentAttendance[]>([]);
  const [selectedDate, setSelectedDate] = useState<string>(new Date().toISOString().split('T')[0]);
  const [loading, setLoading] = useState(true);
  const [studentsLoading, setStudentsLoading] = useState(false);
  const [showStudents, setShowStudents] = useState(true);

  // Leave Dialog State
  const [isLeaveDialogOpen, setIsLeaveDialogOpen] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState<StudentAttendance | null>(null);
  const [leaveType, setLeaveType] = useState<"Half Leave" | "Full Leave">("Full Leave");
  const [leaveFile, setLeaveFile] = useState<File | null>(null);
  const [isSubmittingLeave, setIsSubmittingLeave] = useState(false);

  useEffect(() => {
    fetchClasses();
  }, []);

  useEffect(() => {
    if (selectedClassId && selectedDate) {
      fetchClassAttendance(selectedClassId, selectedDate);
    }
  }, [selectedClassId, selectedDate]);

  const fetchClasses = async () => {
    try {
      const data = await api.get<ClassItem[]>('/api/classes/simple');
      setClasses(data);
      if (data.length > 0) {
        setSelectedClassId(data[0].id);
      }
    } catch (error: any) {
      toast.error("Failed to load classes");
    } finally {
      setLoading(false);
    }
  };

  const fetchClassAttendance = async (classId: string, date: string) => {
    setStudentsLoading(true);
    try {
      const data = await api.get<StudentAttendance[]>(`/api/attendance/admin/class-date?class_id=${classId}&date=${date}`);
      setStudents(data);
    } catch (error: any) {
      toast.error("Failed to load attendance");
    } finally {
      setStudentsLoading(false);
    }
  };

  const handleOpenLeaveDialog = (student: StudentAttendance) => {
    setSelectedStudent(student);
    setLeaveType("Full Leave");
    setLeaveFile(null);
    setIsLeaveDialogOpen(true);
  };

  const submitLeave = async () => {
    if (!selectedStudent) return;

    setIsSubmittingLeave(true);
    try {
      let leaveApplicationUrl: string | null = null;

      if (leaveFile) {
        const fileExt = leaveFile.name.split('.').pop();
        const fileName = `${selectedStudent.id}/${Date.now()}_leave.${fileExt}`;

        const { data: uploadData, error: uploadError } = await supabase.storage
          .from('leave-applications')
          .upload(fileName, leaveFile);

        if (uploadError) throw new Error("Failed to upload application document.");
        leaveApplicationUrl = uploadData.path;
      }

      await api.post('/api/attendance/admin-leave', {
        studentId: selectedStudent.id,
        date: selectedDate,
        status: leaveType,
        leaveApplicationUrl
      });

      toast.success("Leave marked successfully");
      setIsLeaveDialogOpen(false);
      // Refresh the class attendance
      fetchClassAttendance(selectedClassId, selectedDate);
    } catch (error: any) {
      toast.error(error.message || "Failed to mark leave");
    } finally {
      setIsSubmittingLeave(false);
    }
  };

  const handleViewApplication = async (path: string) => {
    try {
      // Get signed URL valid for 60 seconds to view the private file
      const { data, error } = await supabase.storage
        .from('leave-applications')
        .createSignedUrl(path, 60);

      if (error) throw error;
      
      // Open in new tab
      window.open(data.signedUrl, '_blank');
    } catch (error) {
      toast.error("Failed to load application document");
    }
  };

  const getStatusBadge = (status?: string | null) => {
    if (!status) return <Badge variant="outline" className="text-gray-400 border-gray-200">Unmarked</Badge>;

    switch (status) {
      case 'Present': return <Badge variant="secondary" className="bg-green-100 text-green-700">Present</Badge>;
      case 'Absent': return <Badge variant="secondary" className="bg-red-100 text-red-700">Absent</Badge>;
      case 'Late': return <Badge variant="secondary" className="bg-amber-100 text-amber-700">Late</Badge>;
      case 'Half Leave': return <Badge variant="secondary" className="bg-purple-100 text-purple-700">Half Leave</Badge>;
      case 'Full Leave': return <Badge variant="secondary" className="bg-indigo-100 text-indigo-700">Full Leave</Badge>;
      default: return <Badge variant="outline">{status}</Badge>;
    }
  };

  const getStatusCounts = () => {
    const counts = { Present: 0, Absent: 0, Late: 0, Leave: 0, Unmarked: 0 };
    students.forEach(s => {
      if (!s.attendance) counts.Unmarked++;
      else if (s.attendance.status === 'Half Leave' || s.attendance.status === 'Full Leave') counts.Leave++;
      else if (s.attendance.status in counts) counts[s.attendance.status as keyof typeof counts]++;
    });
    return counts;
  };

  const counts = getStatusCounts();
  const selectedClass = classes.find(c => c.id === selectedClassId);

  const getInitials = (firstName: string, lastName: string) => {
    return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase() || '?';
  };

  const getAvatarColor = (index: number) => {
    const colors = ['bg-orange-500', 'bg-amber-500', 'bg-green-500', 'bg-blue-500', 'bg-pink-500'];
    return colors[index % 5];
  };

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header Section */}
      <div className="flex flex-col gap-4">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <div className="p-2.5 bg-orange-100 rounded-xl shadow-sm">
              <ClipboardCheck className="w-6 h-6 text-orange-600" />
            </div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
              Attendance & Leave
            </h1>
          </div>
          <p className="text-gray-500 text-sm sm:text-base ml-[3.25rem]">
            View daily attendance and manage student leaves.
          </p>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <Card className="bg-green-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 flex items-center justify-between">
            <div>
              <p className="text-xs font-semibold text-gray-600 mb-1">Present</p>
              <p className="text-xl sm:text-2xl font-extrabold text-green-600">{counts.Present}</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
              <CheckCircle2 className="w-5 h-5 text-green-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-red-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 flex items-center justify-between">
            <div>
              <p className="text-xs font-semibold text-gray-600 mb-1">Absent</p>
              <p className="text-xl sm:text-2xl font-extrabold text-red-600">{counts.Absent}</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
              <XCircle className="w-5 h-5 text-red-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-indigo-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 flex items-center justify-between">
            <div>
              <p className="text-xs font-semibold text-gray-600 mb-1">On Leave</p>
              <p className="text-xl sm:text-2xl font-extrabold text-indigo-600">{counts.Leave}</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
              <FileText className="w-5 h-5 text-indigo-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 flex items-center justify-between">
            <div>
              <p className="text-xs font-semibold text-gray-600 mb-1">Unmarked</p>
              <p className="text-xl sm:text-2xl font-extrabold text-gray-600">{counts.Unmarked}</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-gray-200 flex items-center justify-center">
              <AlertCircle className="w-5 h-5 text-gray-500" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Controls Card */}
      <Card className="border-0 shadow-sm rounded-2xl bg-white">
        <CardContent className="p-4 sm:p-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
            {/* Class Select */}
            <div className="space-y-2">
              <Label className="text-sm font-medium">Select Class</Label>
              <Select value={selectedClassId} onValueChange={setSelectedClassId} disabled={loading}>
                <SelectTrigger className="bg-gray-50 rounded-xl h-11">
                  <SelectValue placeholder="Select a class" />
                </SelectTrigger>
                <SelectContent>
                  {classes.map((cls) => (
                    <SelectItem key={cls.id} value={cls.id}>{cls.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Date Select */}
            <div className="space-y-2">
              <Label className="text-sm font-medium">Date</Label>
              <div className="relative">
                <input
                  type="date"
                  value={selectedDate}
                  onChange={(e) => setSelectedDate(e.target.value)}
                  className="w-full h-11 px-3 bg-gray-50 border border-input rounded-xl text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Students List */}
      <Card className="border-0 shadow-sm rounded-2xl bg-white overflow-hidden">
        <button
          onClick={() => setShowStudents(!showStudents)}
          className="w-full px-4 sm:px-6 py-4 flex items-center justify-between bg-gray-50/50 border-b border-gray-100 hover:bg-gray-50 transition-colors"
        >
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-orange-100 flex items-center justify-center">
              <Users className="w-5 h-5 sm:w-6 sm:h-6 text-orange-600" />
            </div>
            <div className="text-left">
              <h3 className="text-base sm:text-lg font-bold text-gray-800">
                {selectedClass?.name || 'Select a class'}
              </h3>
              <p className="text-xs sm:text-sm text-gray-500">
                {students.length} student{students.length !== 1 ? 's' : ''}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="secondary" className="bg-orange-100 text-orange-700">
              {selectedDate}
            </Badge>
            {showStudents ? (
              <ChevronDown className="w-5 h-5 text-gray-400" />
            ) : (
              <ChevronRight className="w-5 h-5 text-gray-400" />
            )}
          </div>
        </button>

        {showStudents && (
          <CardContent className="p-0">
            {loading || studentsLoading ? (
              <div className="p-8 flex flex-col items-center justify-center">
                <Loader2 className="w-8 h-8 animate-spin text-orange-500 mb-4" />
                <p className="text-gray-500">Loading attendance data...</p>
              </div>
            ) : students.length === 0 ? (
              <div className="p-8 flex flex-col items-center justify-center">
                <Users className="w-12 h-12 text-gray-200 mb-4" />
                <p className="text-gray-500">No students found in this class</p>
              </div>
            ) : (
              <div className="divide-y divide-gray-50">
                {students.map((student, index) => {
                  return (
                    <div
                      key={student.id}
                      className="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50/50 transition-colors"
                    >
                      <div className="flex items-center gap-3 min-w-0">
                        <div className={`w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold text-white shadow-sm shrink-0 ${getAvatarColor(index)}`}>
                          {getInitials(student.profiles.first_name, student.profiles.last_name)}
                        </div>
                        <div className="min-w-0">
                          <p className="font-semibold text-gray-800 text-sm sm:text-base truncate">
                            {student.profiles.first_name} {student.profiles.last_name}
                          </p>
                          <div className="mt-1 flex items-center gap-2">
                            {getStatusBadge(student.attendance?.status)}
                            {student.attendance?.leave_application_url && (
                              <button
                                onClick={() => handleViewApplication(student.attendance!.leave_application_url!)}
                                className="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1"
                              >
                                <Eye className="w-3 h-3" /> View App
                              </button>
                            )}
                          </div>
                        </div>
                      </div>

                      <div>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleOpenLeaveDialog(student)}
                          className="rounded-lg text-indigo-600 border-indigo-200 hover:bg-indigo-50 w-full sm:w-auto"
                        >
                          <FileText className="w-4 h-4 mr-1.5" />
                          Mark Leave
                        </Button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        )}
      </Card>

      {/* Mark Leave Dialog */}
      <Dialog open={isLeaveDialogOpen} onOpenChange={setIsLeaveDialogOpen}>
        <DialogContent className="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Mark Student Leave</DialogTitle>
            <DialogDescription>
              Submit a leave application for {selectedStudent?.profiles.first_name} {selectedStudent?.profiles.last_name} on {selectedDate}.
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="leave-type">Leave Type</Label>
              <Select value={leaveType} onValueChange={(val: any) => setLeaveType(val)}>
                <SelectTrigger id="leave-type">
                  <SelectValue placeholder="Select leave type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Full Leave">Full Leave</SelectItem>
                  <SelectItem value="Half Leave">Half Leave</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="leave-file">Application Document (Optional)</Label>
              <Input
                id="leave-file"
                type="file"
                accept=".pdf,image/*"
                onChange={(e) => setLeaveFile(e.target.files?.[0] || null)}
                className="cursor-pointer"
              />
              <p className="text-xs text-gray-500">
                Upload a scanned copy or photo of the leave application (PDF, JPG, PNG).
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsLeaveDialogOpen(false)}>
              Cancel
            </Button>
            <Button 
              onClick={submitLeave} 
              disabled={isSubmittingLeave}
              className="bg-indigo-600 hover:bg-indigo-700 text-white"
            >
              {isSubmittingLeave ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  Saving...
                </>
              ) : (
                'Save Leave'
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
