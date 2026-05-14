import { useState, useEffect } from "react";
import { compressFile } from "@/lib/fileCompression";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { useAuth } from "@/hooks/useAuth";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import {
  Loader2,
  CheckCircle,
  XCircle,
  Clock,
  Calendar as CalendarIcon,
  LogOut,
  GraduationCap,
  Users,
  BookOpen,
  ClipboardCheck,
  LayoutDashboard,
  Bell,
  RefreshCw,
  Menu,
  X,
  ChevronRight,
  Briefcase,
  Upload,
  FileText
} from "lucide-react";
import { supabase } from "@/integrations/supabase/client";

interface TeacherStats {
  department: string;
  hireDate: string;
  totalClasses: number;
  totalSubjects: number;
  totalStudents: number;
  attendanceMarkedToday: number;
  status?: string;
  noticeStartDate?: string;
  lastWorkingDate?: string;
  resignationDocumentUrl?: string;
}

interface Notice {
  id: string;
  title: string;
  content: string;
  target_audience: string;
  publish_date: string;
  created_at: string;
}

export default function TeacherDashboard() {
  const { user, loading: authLoading, signOut } = useAuth();
  const navigate = useNavigate();
  const { toast } = useToast();

  // Dashboard state
  const [stats, setStats] = useState<TeacherStats | null>(null);
  const [statsLoading, setStatsLoading] = useState(true);

  // Attendance state
  const [attendanceLoading, setAttendanceLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [assignedClasses, setAssignedClasses] = useState<any[]>([]);
  const [selectedClass, setSelectedClass] = useState<string>("");
  const [students, setStudents] = useState<any[]>([]);
  const [attendance, setAttendance] = useState<Record<string, string>>({});

  // Notices state
  const [notices, setNotices] = useState<Notice[]>([]);
  const [noticesLoading, setNoticesLoading] = useState(false);

  // Attendance validation state
  const [attendanceAlreadyMarked, setAttendanceAlreadyMarked] = useState(false);
  const [markedByOtherTeacher, setMarkedByOtherTeacher] = useState(false);

  // Mobile sidebar state
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // Active tab state for navigation
  const [activeTab, setActiveTab] = useState("dashboard");

  // Employment state
  const [noticeStartDate, setNoticeStartDate] = useState("");
  const [lastWorkingDate, setLastWorkingDate] = useState("");
  const [resignationFile, setResignationFile] = useState<File | null>(null);
  const [submittingResignation, setSubmittingResignation] = useState(false);

  // Sidebar navigation items
  const sidebarLinks = [
    { icon: LayoutDashboard, label: "Dashboard", id: "dashboard" },
    { icon: ClipboardCheck, label: "Attendance", id: "attendance" },
    { icon: Bell, label: "Notices", id: "notices" },
    { icon: BookOpen, label: "My Classes", id: "classes" },
    { icon: Briefcase, label: "Employment", id: "employment" },
  ];

  const today = new Date().toISOString().split("T")[0];
  const formattedDate = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  useEffect(() => {
    if (user && !authLoading) {
      fetchTeacherStats();
      fetchTeacherClasses();
      fetchNotices();
    }
  }, [user, authLoading]);

  const fetchTeacherStats = async () => {
    try {
      setStatsLoading(true);
      const data = await api.get<TeacherStats>("/api/attendance/teacher-stats");
      setStats(data);
    } catch (error: any) {
      console.error("Stats fetch error:", error);
    } finally {
      setStatsLoading(false);
    }
  };

  const fetchTeacherClasses = async () => {
    try {
      const data = await api.get<any[]>("/api/attendance/teacher-classes");
      setAssignedClasses(data);
    } catch (error: any) {
      console.error("Classes fetch error:", error);
      toast({
        title: "Error",
        description: "Failed to load assigned classes.",
        variant: "destructive",
      });
    }
  };

  const fetchStudents = async (classId: string) => {
    setSelectedClass(classId);
    setAttendanceLoading(true);
    setAttendanceAlreadyMarked(false);
    setMarkedByOtherTeacher(false);
    try {
      // Check if attendance already marked for this class today
      const checkResult = await api.get<{
        markedToday: boolean;
        isMarkedByCurrentTeacher: boolean;
      }>(`/api/attendance/check-class?class_id=${classId}`);

      if (checkResult.markedToday) {
        setAttendanceAlreadyMarked(true);
        setMarkedByOtherTeacher(true);
        setAttendanceLoading(false);
        toast({
          title: "Attendance Already Marked",
          description:
            "Attendance for this class has already been marked today and cannot be changed. Attendance is locked once marked.",
          variant: "destructive",
        });
        return;
      }

      const data = await api.get<any[]>(
        `/api/attendance/students?class_id=${classId}`
      );
      setStudents(data || []);
      const initialAttendance: Record<string, string> = {};
      data?.forEach((s) => (initialAttendance[s.id] = "Present"));
      setAttendance(initialAttendance);
    } catch (error: any) {
      toast({
        title: "Error",
        description: "Failed to load students.",
        variant: "destructive",
      });
    } finally {
      setAttendanceLoading(false);
    }
  };

  const saveAttendance = async () => {
    setSaving(true);
    try {
      const records = Object.entries(attendance).map(([studentId, status]) => ({
        studentId,
        status,
      }));
      await api.post("/api/attendance", { records, date: today });
      toast({
        title: "Attendance Saved",
        description: `Attendance recorded for ${students.length} students on ${today}.`,
      });
      fetchTeacherStats();
      setAttendance({});
      setSelectedClass("");
      setStudents([]);
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message,
        variant: "destructive",
      });
    } finally {
      setSaving(false);
    }
  };

  const fetchNotices = async () => {
    setNoticesLoading(true);
    try {
      const data = await api.get<Notice[]>("/api/notices/teacher");
      setNotices(data || []);
    } catch (error: any) {
      console.error("Notices fetch error:", error);
    } finally {
      setNoticesLoading(false);
    }
  };

  const handleSignOut = async () => {
    await signOut();
    navigate("/");
  };

  const handleResignationSubmit = async () => {
    if (!noticeStartDate || !lastWorkingDate) {
      toast({ title: "Validation Error", description: "Notice dates are required.", variant: "destructive" });
      return;
    }
    setSubmittingResignation(true);
    try {
      let documentUrl = stats?.resignationDocumentUrl || null;
      if (resignationFile) {
        const fileToUpload = await compressFile(resignationFile);
        const fileExt = resignationFile.name.split('.').pop()?.toLowerCase();
        
        const fileName = `${user?.id}-${Date.now()}.${fileExt}`;
        const { error: uploadError } = await supabase.storage.from('teacher_documents').upload(fileName, fileToUpload);
        if (uploadError) throw new Error("Upload failed: " + uploadError.message);
        const { data: { publicUrl } } = supabase.storage.from('teacher_documents').getPublicUrl(fileName);
        documentUrl = publicUrl;
      }
      
      await api.post("/api/teachers/resign", {
        noticeStartDate,
        lastWorkingDate,
        resignationDocumentUrl: documentUrl
      });
      
      toast({ title: "Success", description: "Resignation submitted successfully." });
      setResignationFile(null);
      fetchTeacherStats();
    } catch (err: any) {
      toast({ title: "Error", description: err.message || "Failed to submit resignation", variant: "destructive" });
    } finally {
      setSubmittingResignation(false);
    }
  };

  const setAllStatus = (status: string) => {
    const updated: Record<string, string> = {};
    students.forEach((s) => (updated[s.id] = status));
    setAttendance(updated);
  };

  if (authLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="animate-spin w-8 h-8 text-emerald-600" />
      </div>
    );
  }

  if (!user) return null;

  const attendanceSummary = {
    present: Object.values(attendance).filter((s) => s === "Present").length,
    absent: Object.values(attendance).filter((s) => s === "Absent").length,
    late: Object.values(attendance).filter((s) => s === "Late").length,
  };

  const handleNavClick = (id: string) => {
    setActiveTab(id);
    setIsMobileMenuOpen(false);
  };

  return (
    <div className="min-h-screen bg-background flex">
      {/* Mobile Sidebar Overlay */}
      {isMobileMenuOpen && (
        <div
          className="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 lg:hidden transition-opacity"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-emerald-600 via-emerald-700 to-teal-900 transform transition-all duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-2xl lg:shadow-none ${
          isMobileMenuOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        {/* Logo */}
        <div className="flex items-center justify-between gap-3 px-5 py-5 border-b border-white/10">
          <div className="flex items-center gap-3">
            <img src="/logo/logo.png" alt="Rose Valley Academy" className="w-10 h-10 object-contain" />
            <div>
              <h1 className="font-bold text-lg text-white tracking-tight whitespace-nowrap">Rose Valley Academy</h1>
              <p className="text-[10px] text-emerald-200 uppercase tracking-wider font-medium">Teacher Portal</p>
            </div>
          </div>
          {/* Mobile Close Button */}
          <Button
            variant="ghost"
            size="icon"
            className="lg:hidden text-white/70 hover:text-white hover:bg-white/10"
            onClick={() => setIsMobileMenuOpen(false)}
          >
            <X className="w-5 h-5" />
          </Button>
        </div>

        {/* User Info */}
        <div className="px-5 py-4 border-b border-white/10">
        </div>

        {/* Nav Links */}
        <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          {sidebarLinks.map((link) => (
            <button
              key={link.id}
              onClick={() => handleNavClick(link.id)}
              className={`w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 group ${
                activeTab === link.id
                  ? "bg-white text-emerald-700 shadow-lg scale-[1.02]"
                  : "text-white/80 hover:bg-white/10 hover:text-white"
              }`}
            >
              <div className="flex items-center gap-3">
                <link.icon
                  className={`w-5 h-5 transition-transform duration-200 ${
                    activeTab === link.id ? "scale-110" : "group-hover:scale-110"
                  }`}
                />
                {link.label}
              </div>
              <ChevronRight
                className={`w-4 h-4 transition-transform ${
                  activeTab === link.id ? "text-emerald-400" : "text-white/40 group-hover:text-white/70"
                }`}
              />
            </button>
          ))}
        </nav>

        {/* Sign Out Button */}
        <div className="px-3 py-4 border-t border-white/10">
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col min-h-screen lg:ml-64 transition-all duration-300">
        {/* Top Header */}
        <header className="bg-card/80 backdrop-blur-md border-b border-border px-4 sm:px-6 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
          <div className="flex items-center gap-3">
            {/* Mobile Menu Toggle */}
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden text-muted-foreground hover:bg-emerald-50 hover:text-emerald-600"
              onClick={() => setIsMobileMenuOpen(true)}
            >
              <Menu className="w-6 h-6" />
            </Button>

            <div>
              <h2 className="text-lg sm:text-xl font-bold text-foreground">
                {sidebarLinks.find(l => l.id === activeTab)?.label || "Dashboard"}
              </h2>
              <p className="text-xs text-muted-foreground flex items-center gap-1">
                <CalendarIcon className="w-3 h-3" /> {formattedDate}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2 sm:gap-3">
            <div className="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center border border-emerald-200">
              <span className="text-sm font-bold text-emerald-700">
                {user.email?.charAt(0).toUpperCase()}
              </span>
            </div>
            <Button
              variant="ghost"
              onClick={handleSignOut}
              className="gap-2 text-muted-foreground hover:text-red-600 hover:bg-red-50"
              size="sm"
            >
              <LogOut className="w-4 h-4" />
              <span className="hidden sm:inline text-sm">Sign Out</span>
            </Button>
          </div>
        </header>

        <main className="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden">
          {/* Dashboard View */}
          {activeTab === "dashboard" && (
            <div className="space-y-6">
              {/* Stats Cards */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card className="border-l-4 border-l-emerald-500">
                  <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      My Classes
                    </CardTitle>
                    <BookOpen className="w-5 h-5 text-emerald-500" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-2xl sm:text-3xl font-bold text-card-foreground">
                      {statsLoading ? (
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                      ) : (
                        stats?.totalClasses ?? 0
                      )}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      Assigned classes
                    </p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-blue-500">
                  <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      My Students
                    </CardTitle>
                    <Users className="w-5 h-5 text-blue-500" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-2xl sm:text-3xl font-bold text-card-foreground">
                      {statsLoading ? (
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                      ) : (
                        stats?.totalStudents ?? 0
                      )}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      Total students
                    </p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-amber-500">
                  <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      Subjects
                    </CardTitle>
                    <GraduationCap className="w-5 h-5 text-amber-500" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-2xl sm:text-3xl font-bold text-card-foreground">
                      {statsLoading ? (
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                      ) : (
                        stats?.totalSubjects ?? 0
                      )}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      Teaching subjects
                    </p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-rose-500">
                  <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      Attendance Today
                    </CardTitle>
                    <ClipboardCheck className="w-5 h-5 text-rose-500" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-2xl sm:text-3xl font-bold text-card-foreground">
                      {statsLoading ? (
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                      ) : (
                        stats?.attendanceMarkedToday ?? 0
                      )}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      Records marked
                    </p>
                  </CardContent>
                </Card>
              </div>

              {/* Quick Actions */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2 text-lg">
                    <LayoutDashboard className="w-5 h-5 text-emerald-600" />
                    Quick Actions
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <Button
                      variant="outline"
                      className="h-auto py-4 flex flex-col gap-2 hover:bg-emerald-50 hover:border-emerald-200"
                      onClick={() => setActiveTab("attendance")}
                    >
                      <ClipboardCheck className="w-6 h-6 text-emerald-600" />
                      <span className="text-xs font-medium">Mark Attendance</span>
                    </Button>
                    <Button
                      variant="outline"
                      className="h-auto py-4 flex flex-col gap-2 hover:bg-rose-50 hover:border-rose-200"
                      onClick={() => setActiveTab("notices")}
                    >
                      <Bell className="w-6 h-6 text-rose-500" />
                      <span className="text-xs font-medium">View Notices</span>
                    </Button>
                    <Button
                      variant="outline"
                      className="h-auto py-4 flex flex-col gap-2 hover:bg-amber-50 hover:border-amber-200"
                      onClick={() => setActiveTab("classes")}
                    >
                      <BookOpen className="w-6 h-6 text-amber-500" />
                      <span className="text-xs font-medium">My Classes</span>
                    </Button>
                  </div>
                </CardContent>
              </Card>

              {/* Recent Notices Preview */}
              {notices.length > 0 && (
                <Card>
                  <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle className="flex items-center gap-2 text-lg">
                      <Bell className="w-5 h-5 text-rose-500" />
                      Recent Notices
                    </CardTitle>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => setActiveTab("notices")}
                      className="text-emerald-600 hover:text-emerald-700"
                    >
                      View All
                    </Button>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {notices.slice(0, 3).map((notice) => (
                        <div
                          key={notice.id}
                          className="p-3 rounded-lg border bg-muted/20 hover:bg-muted/40 transition-colors"
                        >
                          <div className="flex items-start justify-between gap-2">
                            <h4 className="font-medium text-sm text-card-foreground truncate">
                              {notice.title}
                            </h4>
                            <Badge
                              variant="secondary"
                              className={`text-[10px] shrink-0 ${
                                notice.target_audience === "All"
                                  ? "bg-blue-100 text-blue-700"
                                  : notice.target_audience === "Staff"
                                    ? "bg-orange-100 text-orange-700"
                                    : "bg-amber-100 text-amber-700"
                              }`}
                            >
                              {notice.target_audience}
                            </Badge>
                          </div>
                          <p className="text-xs text-muted-foreground mt-1 line-clamp-1">
                            {notice.content}
                          </p>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              )}
            </div>
          )}

          {/* Attendance View */}
          {activeTab === "attendance" && (
            <Card>
              <CardHeader className="border-b bg-gradient-to-r from-emerald-50 to-emerald-100">
                <div className="flex flex-col gap-4">
                  <CardTitle className="flex items-center gap-2 text-xl">
                    <ClipboardCheck className="w-6 h-6 text-emerald-600" />
                    Mark Attendance
                  </CardTitle>
                  <div className="w-full">
                    <Select onValueChange={fetchStudents} value={selectedClass}>
                      <SelectTrigger className="bg-white">
                        <SelectValue placeholder="Select a class" />
                      </SelectTrigger>
                      <SelectContent>
                        {assignedClasses.map((c) => (
                          <SelectItem key={c.id} value={c.id}>
                            {c.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </CardHeader>

              <CardContent className="pt-6">
                {!selectedClass ? (
                  <div className="text-center py-16">
                    <ClipboardCheck className="w-16 h-16 text-emerald-200 mx-auto mb-4" />
                    <p className="text-foreground font-medium text-lg">Select a class to begin</p>
                  </div>
                ) : attendanceLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="animate-spin w-8 h-8 text-emerald-600" />
                  </div>
                ) : students.length === 0 ? (
                  <div className="text-center py-12">
                    <Users className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
                    <p className="text-muted-foreground">No students in this class</p>
                  </div>
                ) : markedByOtherTeacher ? (
                  <div className="text-center py-12">
                    <XCircle className="w-12 h-12 text-red-500/30 mx-auto mb-4" />
                    <p className="text-red-700 font-medium">Attendance Marked by Another Teacher</p>
                    <p className="text-sm text-muted-foreground mt-2">
                      This class's attendance has already been marked by another teacher today.
                    </p>
                    <p className="text-sm text-muted-foreground">
                      Only the teacher who marked the attendance can update it.
                    </p>
                  </div>
                ) : (
                  <>
                    {/* Quick actions bar */}
                    <div className="flex flex-col sm:flex-row gap-4 mb-6 p-3 bg-muted/50 rounded-lg">
                      <div className="flex gap-2 flex-1">
                        <Button
                          size="sm"
                          className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white"
                          onClick={() => setAllStatus("Present")}
                        >
                          <CheckCircle className="w-4 h-4 mr-2" />
                          All Present
                        </Button>
                        <Button
                          size="sm"
                          variant="outline"
                          className="flex-1 border-red-200 text-red-700 hover:bg-red-50"
                          onClick={() => setAllStatus("Absent")}
                        >
                          <XCircle className="w-4 h-4 mr-2" />
                          All Absent
                        </Button>
                      </div>
                      <div className="flex items-center gap-4 text-sm font-medium">
                        <div className="flex items-center gap-2">
                          <div className="w-3 h-3 rounded-full bg-emerald-500" />
                          <span>{attendanceSummary.present}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <div className="w-3 h-3 rounded-full bg-red-500" />
                          <span>{attendanceSummary.absent}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <div className="w-3 h-3 rounded-full bg-amber-500" />
                          <span>{attendanceSummary.late}</span>
                        </div>
                      </div>
                    </div>

                    {/* Students list - Mobile friendly */}
                    <div className="rounded-lg border divide-y max-h-[500px] overflow-y-auto">
                      {/* Desktop Table */}
                      <div className="hidden sm:block">
                        <Table>
                          <TableHeader>
                            <TableRow className="bg-muted/30">
                              <TableHead className="w-12 text-center">#</TableHead>
                              <TableHead>Name</TableHead>
                              <TableHead className="w-16 text-center">Roll</TableHead>
                              <TableHead className="text-center w-80">Status</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {students.map((student, index) => (
                              <TableRow key={student.id} className="hover:bg-muted/20">
                                <TableCell className="text-center text-xs text-muted-foreground">
                                  {index + 1}
                                </TableCell>
                                <TableCell className="font-medium">
                                  {(() => {
                                    const p = Array.isArray(student.profiles)
                                      ? student.profiles[0]
                                      : student.profiles;
                                    return `${p?.first_name || ""} ${p?.last_name || ""}`.trim() || "Unknown";
                                  })()}
                                </TableCell>
                                <TableCell className="text-center text-sm text-muted-foreground">
                                  {student.roll_number || "—"}
                                </TableCell>
                                <TableCell>
                                  <div className="flex justify-center gap-1">
                                    <Button
                                      size="sm"
                                      variant={attendance[student.id] === "Present" ? "default" : "outline"}
                                      className={`w-20 text-xs ${
                                        attendance[student.id] === "Present"
                                          ? "bg-emerald-600 hover:bg-emerald-700 text-white"
                                          : ""
                                      }`}
                                      onClick={() => setAttendance({ ...attendance, [student.id]: "Present" })}
                                    >
                                      <CheckCircle className="w-3 h-3 mr-1" />
                                      P
                                    </Button>
                                    <Button
                                      size="sm"
                                      variant={attendance[student.id] === "Absent" ? "destructive" : "outline"}
                                      className="w-20 text-xs"
                                      onClick={() => setAttendance({ ...attendance, [student.id]: "Absent" })}
                                    >
                                      <XCircle className="w-3 h-3 mr-1" />
                                      A
                                    </Button>
                                    <Button
                                      size="sm"
                                      variant={attendance[student.id] === "Late" ? "default" : "outline"}
                                      className={`w-20 text-xs ${
                                        attendance[student.id] === "Late"
                                          ? "bg-amber-500 hover:bg-amber-600 text-white"
                                          : ""
                                      }`}
                                      onClick={() => setAttendance({ ...attendance, [student.id]: "Late" })}
                                    >
                                      <Clock className="w-3 h-3 mr-1" />
                                      L
                                    </Button>
                                  </div>
                                </TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </div>

                      {/* Mobile Cards */}
                      <div className="sm:hidden">
                        {students.map((student, index) => {
                          const p = Array.isArray(student.profiles) ? student.profiles[0] : student.profiles;
                          const name = `${p?.first_name || ""} ${p?.last_name || ""}`.trim() || "Unknown";
                          return (
                            <div key={student.id} className="p-3 space-y-2">
                              <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2 flex-1 min-w-0">
                                  <span className="text-xs font-bold text-muted-foreground bg-muted rounded px-2 py-1 shrink-0">
                                    {index + 1}
                                  </span>
                                  <div className="min-w-0">
                                    <p className="font-medium text-sm truncate">{name}</p>
                                    <p className="text-xs text-muted-foreground">Roll: {student.roll_number || "—"}</p>
                                  </div>
                                </div>
                                <Badge
                                  className={`text-xs shrink-0 ml-2 ${
                                    attendance[student.id] === "Present"
                                      ? "bg-emerald-100 text-emerald-700"
                                      : attendance[student.id] === "Absent"
                                        ? "bg-red-100 text-red-700"
                                        : "bg-amber-100 text-amber-700"
                                  }`}
                                >
                                  {attendance[student.id]?.[0]}
                                </Badge>
                              </div>
                              <div className="flex gap-1">
                                <Button
                                  size="sm"
                                  variant={attendance[student.id] === "Present" ? "default" : "outline"}
                                  className={`flex-1 h-9 text-xs ${
                                    attendance[student.id] === "Present"
                                      ? "bg-emerald-600 hover:bg-emerald-700 text-white"
                                      : ""
                                  }`}
                                  onClick={() => setAttendance({ ...attendance, [student.id]: "Present" })}
                                >
                                  <CheckCircle className="w-3.5 h-3.5" />
                                </Button>
                                <Button
                                  size="sm"
                                  variant={attendance[student.id] === "Absent" ? "destructive" : "outline"}
                                  className="flex-1 h-9 text-xs"
                                  onClick={() => setAttendance({ ...attendance, [student.id]: "Absent" })}
                                >
                                  <XCircle className="w-3.5 h-3.5" />
                                </Button>
                                <Button
                                  size="sm"
                                  variant={attendance[student.id] === "Late" ? "default" : "outline"}
                                  className={`flex-1 h-9 text-xs ${
                                    attendance[student.id] === "Late"
                                      ? "bg-amber-500 hover:bg-amber-600 text-white"
                                      : ""
                                  }`}
                                  onClick={() => setAttendance({ ...attendance, [student.id]: "Late" })}
                                >
                                  <Clock className="w-3.5 h-3.5" />
                                </Button>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </div>

                    {/* Submit bar */}
                    <div className="flex flex-col sm:flex-row items-center gap-3 mt-6 pt-4 border-t">
                      <span className="text-sm text-muted-foreground">
                        <span className="font-semibold text-foreground">{students.length}</span> students
                      </span>
                      <Button
                        onClick={saveAttendance}
                        disabled={saving}
                        className="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 gap-2"
                      >
                        {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <CheckCircle className="h-4 w-4" />}
                        Submit
                      </Button>
                    </div>
                  </>
                )}
              </CardContent>
            </Card>
          )}

          {/* Notices View */}
          {activeTab === "notices" && (
            <Card>
              <CardHeader className="border-b bg-muted/20 flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Bell className="w-5 h-5 text-rose-500" />
                  Notice Board
                </CardTitle>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={fetchNotices}
                  disabled={noticesLoading}
                  className="gap-1"
                >
                  <RefreshCw className={`w-3.5 h-3.5 ${noticesLoading ? "animate-spin" : ""}`} />
                  <span className="hidden sm:inline">Refresh</span>
                </Button>
              </CardHeader>
              <CardContent className="pt-6">
                {noticesLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="animate-spin w-8 h-8 text-muted-foreground" />
                  </div>
                ) : notices.length === 0 ? (
                  <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                    <Bell className="w-10 h-10 sm:w-12 sm:h-12 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">No notices posted yet</p>
                    <p className="text-sm text-muted-foreground mt-1">
                      Notices posted by the admin will appear here.
                    </p>
                  </div>
                ) : (
                  <div className="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                    {notices.map((notice) => (
                      <div
                        key={notice.id}
                        className="p-4 rounded-lg border bg-card hover:shadow-sm transition-shadow"
                      >
                        <div className="flex items-start justify-between gap-3">
                          <div className="flex-1 min-w-0">
                            <h4 className="font-semibold text-card-foreground">{notice.title}</h4>
                            <p className="text-sm text-muted-foreground mt-1 line-clamp-3">{notice.content}</p>
                          </div>
                          <Badge
                            variant="secondary"
                            className={`shrink-0 ${
                              notice.target_audience === "All"
                                ? "bg-blue-100 text-blue-700"
                                : notice.target_audience === "Staff"
                                  ? "bg-orange-100 text-orange-700"
                                  : "bg-amber-100 text-amber-700"
                            }`}
                          >
                            {notice.target_audience}
                          </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-3 flex items-center gap-1">
                          <CalendarIcon className="w-3 h-3" />
                          {new Date(notice.publish_date || notice.created_at).toLocaleDateString("en-US", {
                            month: "short",
                            day: "numeric",
                            year: "numeric",
                          })}
                        </p>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {/* Students View */}
          {activeTab === "students" && (
            <Card>
              <CardHeader className="border-b bg-muted/20">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Users className="w-5 h-5 text-blue-500" />
                  My Students
                </CardTitle>
              </CardHeader>
              <CardContent className="pt-6">
                <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                  <Users className="w-10 h-10 sm:w-12 sm:h-12 text-muted-foreground/40 mx-auto mb-4" />
                  <p className="text-muted-foreground font-medium">Student List</p>
                  <p className="text-sm text-muted-foreground mt-1">
                    You have {stats?.totalStudents ?? 0} students across your classes.
                  </p>
                  <Button
                    variant="outline"
                    className="mt-4 gap-2"
                    onClick={() => setActiveTab("attendance")}
                  >
                    <ClipboardCheck className="w-4 h-4" />
                    View in Attendance
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}

          {/* Classes View */}
          {activeTab === "classes" && (
            <Card>
              <CardHeader className="border-b bg-muted/20">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <BookOpen className="w-5 h-5 text-amber-500" />
                  My Classes
                </CardTitle>
              </CardHeader>
              <CardContent className="pt-6">
                {assignedClasses.length === 0 ? (
                  <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                    <BookOpen className="w-10 h-10 sm:w-12 sm:h-12 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">No classes assigned</p>
                    <p className="text-sm text-muted-foreground mt-1">
                      Please contact your administrator to get classes assigned.
                    </p>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {assignedClasses.map((c) => (
                      <div
                        key={c.id}
                        className="p-4 rounded-lg border bg-card hover:shadow-md transition-shadow cursor-pointer"
                        onClick={() => {
                          setActiveTab("attendance");
                          fetchStudents(c.id);
                        }}
                      >
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                            <BookOpen className="w-5 h-5 text-amber-600" />
                          </div>
                          <div>
                            <h4 className="font-semibold text-card-foreground">{c.name}</h4>
                            <p className="text-xs text-muted-foreground">Click to mark attendance</p>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {/* Employment View */}
          {activeTab === "employment" && (
            <Card>
              <CardHeader className="border-b bg-muted/20">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Briefcase className="w-5 h-5 text-indigo-500" />
                  Employment Status
                </CardTitle>
              </CardHeader>
              <CardContent className="pt-6 space-y-8">
                <div>
                  <h3 className="text-lg font-semibold mb-4">Current Status</h3>
                  <div className="flex items-center gap-4 p-4 bg-muted rounded-lg">
                    <span className="text-muted-foreground">Status:</span>
                    <Badge variant={stats?.status === 'active' ? 'default' : stats?.status === 'on_notice' ? 'outline' : 'destructive'} 
                           className={stats?.status === 'on_notice' ? 'text-amber-600 border-amber-200 bg-amber-50' : ''}>
                      {stats?.status === 'active' ? 'Active' : stats?.status === 'on_notice' ? 'On Notice' : stats?.status === 'left' ? 'Left School' : 'Unknown'}
                    </Badge>
                  </div>
                  {(stats?.status === 'on_notice' || stats?.noticeStartDate) && (
                    <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="p-4 bg-amber-50 rounded-lg border border-amber-100">
                        <p className="text-sm text-amber-700">Notice Start Date {stats?.status === 'active' ? '(Proposed)' : ''}</p>
                        <p className="text-lg font-medium text-amber-900">
                          {stats.noticeStartDate ? new Date(stats.noticeStartDate).toLocaleDateString() : 'N/A'}
                        </p>
                      </div>
                      <div className="p-4 bg-amber-50 rounded-lg border border-amber-100">
                        <p className="text-sm text-amber-700">Last Working Date {stats?.status === 'active' ? '(Proposed)' : ''}</p>
                        <p className="text-lg font-medium text-amber-900">
                          {stats.lastWorkingDate ? new Date(stats.lastWorkingDate).toLocaleDateString() : 'N/A'}
                        </p>
                      </div>
                    </div>
                  )}
                </div>

                {stats?.status === 'active' && !stats?.resignationDocumentUrl && (
                  <div className="pt-6 border-t">
                    <h3 className="text-lg font-semibold mb-4">Submit Resignation Application</h3>
                  <div className="space-y-4 max-w-2xl">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Notice Start Date</label>
                        <input 
                          type="date" 
                          className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                          value={noticeStartDate} 
                          min={new Date().toISOString().split('T')[0]}
                          onChange={(e) => {
                            setNoticeStartDate(e.target.value);
                            // Auto-set last working date to 30 days after
                            if (e.target.value) {
                              const start = new Date(e.target.value);
                              start.setDate(start.getDate() + 30);
                              setLastWorkingDate(start.toISOString().split('T')[0]);
                            } else {
                              setLastWorkingDate('');
                            }
                          }} 
                        />
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Last Working Date</label>
                        <input 
                          type="date" 
                          className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                          value={lastWorkingDate} 
                          min={noticeStartDate || new Date().toISOString().split('T')[0]}
                          disabled={!noticeStartDate}
                          onChange={(e) => setLastWorkingDate(e.target.value)} 
                        />
                        {!noticeStartDate && (
                          <p className="text-xs text-muted-foreground">Select notice start date first</p>
                        )}
                      </div>
                    </div>
                    
                    <div className="space-y-2">
                      <label className="text-sm font-medium">Application Document (PDF/Image)</label>
                      <input 
                        type="file" 
                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer"
                        onChange={(e) => setResignationFile(e.target.files?.[0] || null)}
                      />
                      {stats?.resignationDocumentUrl && !resignationFile && (
                        <p className="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                          <FileText className="w-3 h-3" /> A document is already uploaded. Uploading a new one will replace it.
                        </p>
                      )}
                    </div>

                    <div className="pt-2">
                      <Button onClick={handleResignationSubmit} disabled={submittingResignation} className="gap-2">
                        {submittingResignation ? <Loader2 className="w-4 h-4 animate-spin" /> : <Upload className="w-4 h-4" />}
                        Submit Application
                      </Button>
                    </div>
                  </div>
                </div>
                )}

                {stats?.resignationDocumentUrl && (
                  <div className="pt-6 border-t mt-6">
                    <h3 className="text-lg font-semibold mb-4">Submitted Application</h3>
                    <div className="p-4 bg-indigo-50 rounded-lg border border-indigo-100 flex items-center justify-between">
                      <div>
                        <p className="text-sm text-indigo-700 font-medium">Resignation Document</p>
                        <p className="text-xs text-indigo-600/70">View the official resignation document</p>
                      </div>
                      <Button variant="outline" size="sm" onClick={() => window.open(stats.resignationDocumentUrl, '_blank')} className="gap-2 text-indigo-700 hover:text-indigo-800 hover:bg-indigo-100 border-indigo-200">
                        <FileText className="w-4 h-4" /> View File
                      </Button>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          )}
        </main>
      </div>
    </div>
  );
}
