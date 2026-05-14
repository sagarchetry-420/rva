import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { useAuth } from "@/hooks/useAuth";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useToast } from "@/hooks/use-toast";
import { Printer } from "lucide-react";
import {
  Loader2,
  CheckCircle,
  XCircle,
  Clock,
  Calendar as CalendarIcon,
  LogOut,
  GraduationCap,
  User,
  BookOpen,
  ClipboardCheck,
  LayoutDashboard,
  Bell,
  RefreshCw,
  TrendingUp,
  Menu,
  X,
  ChevronRight,
  FileText,
  Trophy,
  Eye,
  Lock,
} from "lucide-react";

interface StudentProfile {
  firstName: string;
  lastName: string;
  email: string;
  className: string;
  classId: string;
  admissionDate?: string;
}

interface AttendanceStats {
  totalDays: number;
  presentDays: number;
  absentDays: number;
  lateDays: number;
  attendancePercentage: number;
}

interface AttendanceRecord {
  id: string;
  date: string;
  status: string;
  marked_by?: string;
}

interface Notice {
  id: string;
  title: string;
  content: string;
  target_audience: string;
  publish_date: string;
  created_at: string;
}

interface UpcomingExam {
  id: string;
  exam_date: string;
  start_time: string;
  end_time: string;
  total_marks: number;
  passing_marks: number;
  subjects: { id: string; name: string; code: string } | null;
  exams: { id: string; name: string; class_id: string; classes: { id: string; name: string } | null } | null;
}

export default function StudentDashboard() {
  const { user, loading: authLoading, signOut } = useAuth();
  const navigate = useNavigate();
  const { toast } = useToast();

  // Profile state
  const [profile, setProfile] = useState<StudentProfile | null>(null);
  const [profileLoading, setProfileLoading] = useState(true);

  // Attendance state
  const [attendanceStats, setAttendanceStats] = useState<AttendanceStats | null>(null);
  const [attendanceRecords, setAttendanceRecords] = useState<AttendanceRecord[]>([]);
  const [attendanceLoading, setAttendanceLoading] = useState(true);

  // Notices state
  const [notices, setNotices] = useState<Notice[]>([]);
  const [noticesLoading, setNoticesLoading] = useState(false);

  // Exams state
  const [scheduledExams, setScheduledExams] = useState<UpcomingExam[]>([]);
  const [examsLoading, setExamsLoading] = useState(false);

  // Mobile sidebar state
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // Active tab state for navigation
  const [activeTab, setActiveTab] = useState("dashboard");

  // Results lookup state
  const [resultExams, setResultExams] = useState<any[]>([]);
  const [selectedResultExam, setSelectedResultExam] = useState("");
  const [resultPassword, setResultPassword] = useState("");
  const [lookupResult, setLookupResult] = useState<any>(null);
  const [lookupLoading, setLookupLoading] = useState(false);
  const [lookupError, setLookupError] = useState("");

  // Sidebar navigation items
  const sidebarLinks = [
    { icon: LayoutDashboard, label: "Dashboard", id: "dashboard" },
    { icon: User, label: "My Profile", id: "profile" },
    { icon: ClipboardCheck, label: "Attendance", id: "attendance" },
    { icon: FileText, label: "Exams", id: "exams" },
    { icon: Trophy, label: "Results", id: "results" },
    { icon: Bell, label: "Notices", id: "notices" },
  ];

  const formattedDate = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  useEffect(() => {
    if (user && !authLoading) {
      fetchStudentData();
    }
  }, [user, authLoading]);

  const fetchStudentData = async () => {
    await Promise.all([
      fetchProfile(),
      fetchAttendance(),
      fetchNotices(),
      fetchScheduledExams(),
    ]);
  };

  const fetchProfile = async () => {
    try {
      setProfileLoading(true);
      const data = await api.get<StudentProfile>("/api/students/me");
      setProfile(data);
    } catch (error: any) {
      console.error("Profile fetch error:", error);
      toast({
        title: "Error",
        description: "Failed to load profile information.",
        variant: "destructive",
      });
    } finally {
      setProfileLoading(false);
    }
  };

  const fetchAttendance = async () => {
    try {
      setAttendanceLoading(true);
      const [stats, records] = await Promise.all([
        api.get<AttendanceStats>("/api/students/me/attendance/stats"),
        api.get<AttendanceRecord[]>("/api/students/me/attendance"),
      ]);
      setAttendanceStats(stats);
      setAttendanceRecords(records || []);
    } catch (error: any) {
      console.error("Attendance fetch error:", error);
    } finally {
      setAttendanceLoading(false);
    }
  };

  const fetchNotices = async () => {
    setNoticesLoading(true);
    try {
      const data = await api.get<Notice[]>("/api/notices/student");
      setNotices(data || []);
    } catch (error: any) {
      console.error("Notices fetch error:", error);
    } finally {
      setNoticesLoading(false);
    }
  };

  const fetchScheduledExams = async () => {
    setExamsLoading(true);
    try {
      const classId = profile?.classId;
      const url = classId ? `/api/exams/schedule?class_id=${classId}` : '/api/exams/schedule';
      const data = await api.get<UpcomingExam[]>(url);
      setScheduledExams(data || []);
    } catch (error: any) {
      console.error("Exams fetch error:", error);
    } finally {
      setExamsLoading(false);
    }
  };

  // Re-fetch exams when profile loads (to get class-specific exams)
  useEffect(() => {
    if (profile?.classId) {
      fetchScheduledExams();
      fetchResultExams();
    }
  }, [profile?.classId]);

  const fetchResultExams = async () => {
    try {
      const classId = profile?.classId;
      const url = classId ? `/api/exams?class_id=${classId}` : "/api/exams";
      const data = await api.get<any[]>(url);
      setResultExams(data || []);
    } catch (err) {
      console.error("Result exams fetch error:", err);
    }
  };

  const handleResultLookup = async () => {
    if (!selectedResultExam || !resultPassword) {
      setLookupError("Please fill in all fields (Exam and Password).");
      return;
    }

    setLookupLoading(true);
    setLookupError("");

    try {
      const data = await api.post<any>("/api/results/student-lookup", {
        examId: selectedResultExam,
        password: resultPassword,
      });
      setLookupResult(data);
    } catch (err: any) {
      setLookupError(err.message || "Failed to look up results");
    } finally {
      setLookupLoading(false);
    }
  };

  const handleSignOut = async () => {
    await signOut();
    navigate("/");
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "Present":
        return (
          <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">
            <CheckCircle className="w-3 h-3 mr-1" />
            Present
          </Badge>
        );
      case "Absent":
        return (
          <Badge className="bg-red-100 text-red-700 hover:bg-red-100">
            <XCircle className="w-3 h-3 mr-1" />
            Absent
          </Badge>
        );
      case "Late":
        return (
          <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100">
            <Clock className="w-3 h-3 mr-1" />
            Late
          </Badge>
        );
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const handleNavClick = (id: string) => {
    setActiveTab(id);
    setIsMobileMenuOpen(false);
  };

  if (authLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="animate-spin w-8 h-8 text-blue-600" />
      </div>
    );
  }

  if (!user) return null;

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
        className={`fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-blue-600 via-blue-700 to-indigo-900 transform transition-all duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-2xl lg:shadow-none ${
          isMobileMenuOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        {/* Logo */}
        <div className="flex items-center justify-between gap-3 px-5 py-5 border-b border-white/10">
          <div className="flex items-center gap-3">
            <img src="/logo/logo.png" alt="Rose Valley Academy" className="w-10 h-10 object-contain" />
            <div>
              <h1 className="font-bold text-lg text-white tracking-tight whitespace-nowrap">Rose Valley Academy</h1>
              <p className="text-[10px] text-blue-200 uppercase tracking-wider font-medium">Student Portal</p>
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
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
              <span className="text-sm font-bold text-white">
                {profile?.firstName?.charAt(0) || 'S'}
              </span>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-white truncate">
                {profileLoading ? "Loading..." : `${profile?.firstName || ""} ${profile?.lastName || ""}`.trim() || 'Student'}
              </p>
              <p className="text-xs text-blue-200 truncate">{profile?.className || "Student"}</p>
            </div>
          </div>
        </div>

        {/* Nav Links */}
        <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          {sidebarLinks.map((link) => (
            <button
              key={link.id}
              onClick={() => handleNavClick(link.id)}
              className={`w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 group ${
                activeTab === link.id
                  ? "bg-white text-blue-700 shadow-lg scale-[1.02]"
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
                  activeTab === link.id ? "text-blue-400" : "text-white/40 group-hover:text-white/70"
                }`}
              />
            </button>
          ))}
        </nav>

        {/* Sign Out Button */}
        <div className="px-3 py-4 border-t border-white/10">
          <Button
            variant="ghost"
            onClick={handleSignOut}
            className="w-full justify-start gap-3 text-white/80 hover:text-white hover:bg-red-500/20 px-4 py-3 h-auto"
          >
            <LogOut className="w-5 h-5" />
            Sign Out
          </Button>
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
              className="lg:hidden text-muted-foreground hover:bg-blue-50 hover:text-blue-600"
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
            <Button
              onClick={fetchStudentData}
              variant="outline"
              size="sm"
              className="gap-2 hover:bg-blue-50 border-blue-200 text-blue-700"
              disabled={profileLoading || attendanceLoading}
            >
              <RefreshCw
                className={`w-4 h-4 ${profileLoading || attendanceLoading ? "animate-spin" : ""}`}
              />
              <span className="hidden sm:inline">Refresh</span>
            </Button>

            {/* Mobile user avatar */}
            <div className="lg:hidden w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center border border-blue-200">
              <span className="text-sm font-bold text-blue-700">
                {profile?.firstName?.charAt(0) || user.email?.charAt(0).toUpperCase()}
              </span>
            </div>
          </div>
        </header>

        <main className="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden">
          {/* Dashboard View */}
          {activeTab === "dashboard" && (
            <div className="space-y-6">
              {/* Welcome Section */}
              <div className="mb-2">
                <h3 className="text-2xl sm:text-3xl font-bold text-foreground">
                  Welcome, {profile?.firstName || "Student"}!
                </h3>
                <p className="text-muted-foreground mt-1">
                  Here's your academic overview at Rose Valley Academy.
                </p>
              </div>

              {/* Stats Cards */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card className="border-l-4 border-l-blue-500 hover:shadow-md transition-shadow">
                  <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      My Class
                    </CardTitle>
                    <BookOpen className="w-5 h-5 text-blue-500" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-2xl font-bold text-card-foreground truncate">
                      {profileLoading ? (
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                      ) : (
                        profile?.className || "N/A"
                      )}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      Current class
                    </p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-emerald-500 hover:shadow-md transition-shadow">
                  <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      Present Days
                    </CardTitle>
                    <CheckCircle className="w-5 h-5 text-emerald-500" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-2xl sm:text-3xl font-bold text-card-foreground">
                      {attendanceLoading ? (
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                      ) : (
                        attendanceStats?.presentDays ?? 0
                      )}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      Out of {attendanceStats?.totalDays ?? 0} days
                    </p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-red-500 hover:shadow-md transition-shadow">
                  <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      Absent Days
                    </CardTitle>
                    <XCircle className="w-5 h-5 text-red-500" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-2xl sm:text-3xl font-bold text-card-foreground">
                      {attendanceLoading ? (
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                      ) : (
                        attendanceStats?.absentDays ?? 0
                      )}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      This term
                    </p>
                  </CardContent>
                </Card>

                <Card className="border-l-4 border-l-amber-500 hover:shadow-md transition-shadow">
                  <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                      Attendance Rate
                    </CardTitle>
                    <TrendingUp className="w-5 h-5 text-amber-500" />
                  </CardHeader>
                  <CardContent>
                    <p className="text-2xl sm:text-3xl font-bold text-card-foreground">
                      {attendanceLoading ? (
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                      ) : (
                        `${attendanceStats?.attendancePercentage ?? 0}%`
                      )}
                    </p>
                    <p className="text-xs text-muted-foreground mt-1">
                      Overall percentage
                    </p>
                  </CardContent>
                </Card>
              </div>

              {/* Quick Actions */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2 text-lg">
                    <LayoutDashboard className="w-5 h-5 text-blue-600" />
                    Quick Actions
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Button
                      variant="outline"
                      className="h-auto py-4 flex flex-col gap-2 hover:bg-blue-50 hover:border-blue-200"
                      onClick={() => setActiveTab("profile")}
                    >
                      <User className="w-6 h-6 text-blue-600" />
                      <span className="text-xs font-medium">My Profile</span>
                    </Button>
                    <Button
                      variant="outline"
                      className="h-auto py-4 flex flex-col gap-2 hover:bg-emerald-50 hover:border-emerald-200"
                      onClick={() => setActiveTab("attendance")}
                    >
                      <ClipboardCheck className="w-6 h-6 text-emerald-500" />
                      <span className="text-xs font-medium">Attendance</span>
                    </Button>
                    <Button
                      variant="outline"
                      className="h-auto py-4 flex flex-col gap-2 hover:bg-rose-50 hover:border-rose-200"
                      onClick={() => setActiveTab("notices")}
                    >
                      <Bell className="w-6 h-6 text-rose-500" />
                      <span className="text-xs font-medium">Notices</span>
                    </Button>
                    <Button
                      variant="outline"
                      className="h-auto py-4 flex flex-col gap-2 hover:bg-orange-50 hover:border-orange-200"
                      onClick={() => setActiveTab("exams")}
                    >
                      <FileText className="w-6 h-6 text-orange-500" />
                      <span className="text-xs font-medium">Exams</span>
                    </Button>
                  </div>
                </CardContent>
              </Card>

              {/* Upcoming Exams Preview */}
              {(() => {
                const upcomingOnlyExams = scheduledExams.filter(e => new Date(e.exam_date) >= new Date(new Date().setHours(0,0,0,0)));
                if (upcomingOnlyExams.length === 0) return null;
                return (
                  <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                      <CardTitle className="flex items-center gap-2 text-lg">
                        <FileText className="w-5 h-5 text-orange-500" />
                        Upcoming Exams
                      </CardTitle>
                      <Button variant="ghost" size="sm" onClick={() => setActiveTab("exams")} className="text-blue-600 hover:text-blue-700">View All</Button>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-3">
                        {upcomingOnlyExams.slice(0, 5).map((exam) => (
                          <div key={exam.id} className="p-3 rounded-lg border bg-muted/20 hover:bg-muted/40 transition-colors">
                            <div className="flex items-start justify-between gap-2">
                              <div className="flex-1 min-w-0">
                                <h4 className="font-medium text-sm text-card-foreground truncate">
                                  {exam.subjects?.name || 'Unknown'}
                                </h4>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                  {exam.exams?.name} · {new Date(exam.exam_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                                </p>
                              </div>
                              <div className="flex gap-1.5 shrink-0">
                                <Badge className="bg-blue-50 text-blue-700 text-[10px]">{exam.total_marks} marks</Badge>
                                <Badge className="bg-green-50 text-green-700 text-[10px]">Pass: {exam.passing_marks}</Badge>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </CardContent>
                  </Card>
                );
              })()}

              {/* Recent Notices Preview */}
              {notices.length > 0 && (
                <Card>
                  <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle className="flex items-center gap-2 text-lg">
                      <Bell className="w-5 h-5 text-rose-500" />
                      Recent Notices
                    </CardTitle>
                    <Button variant="ghost" size="sm" onClick={() => setActiveTab("notices")} className="text-blue-600 hover:text-blue-700">View All</Button>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {notices.slice(0, 3).map((notice) => (
                        <div key={notice.id} className="p-3 rounded-lg border bg-muted/20 hover:bg-muted/40 transition-colors">
                          <div className="flex items-start justify-between gap-2">
                            <h4 className="font-medium text-sm text-card-foreground truncate">{notice.title}</h4>
                            <Badge variant="secondary" className={`text-[10px] shrink-0 ${notice.target_audience === "All" ? "bg-blue-100 text-blue-700" : "bg-amber-100 text-amber-700"}`}>{notice.target_audience}</Badge>
                          </div>
                          <p className="text-xs text-muted-foreground mt-1 line-clamp-1">{notice.content}</p>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              )}
            </div>
          )}

          {/* Profile View */}
          {activeTab === "profile" && (
            <Card>
              <CardHeader className="border-b bg-muted/20">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <User className="w-5 h-5 text-blue-600" />
                  My Profile
                </CardTitle>
              </CardHeader>
              <CardContent className="pt-6">
                {profileLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="animate-spin w-8 h-8 text-blue-600" />
                  </div>
                ) : !profile ? (
                  <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                    <User className="w-10 h-10 sm:w-12 sm:h-12 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">
                      Profile information not available
                    </p>
                  </div>
                ) : (
                  <div className="grid md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                      <div className="flex items-center gap-4 p-4 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100">
                        <div className="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center border-2 border-blue-200">
                          <User className="w-8 h-8 text-blue-600" />
                        </div>
                        <div>
                          <h3 className="text-xl font-semibold text-foreground">
                            {profile.firstName} {profile.lastName}
                          </h3>
                          <p className="text-sm text-muted-foreground">
                            Roll No: {profile.rollNumber}
                          </p>
                          <Badge className="mt-2 bg-blue-100 text-blue-700 hover:bg-blue-100">
                            Student
                          </Badge>
                        </div>
                      </div>
                    </div>
                    <div className="space-y-3">
                      <div className="flex justify-between items-center py-3 px-4 border rounded-lg bg-muted/10">
                        <span className="text-muted-foreground">Class</span>
                        <span className="font-semibold">{profile.className}</span>
                      </div>
                      {profile.admissionDate && (
                        <div className="flex justify-between items-center py-3 px-4 border rounded-lg bg-muted/10">
                          <span className="text-muted-foreground">Admission Date</span>
                          <span className="font-semibold">
                            {new Date(profile.admissionDate).toLocaleDateString("en-US", {
                              month: "short",
                              day: "numeric",
                              year: "numeric",
                            })}
                          </span>
                        </div>
                      )}
                      <div className="flex justify-between items-center py-3 px-4 border rounded-lg bg-muted/10">
                        <span className="text-muted-foreground">Attendance</span>
                        <span className="font-semibold text-emerald-600">
                          {attendanceStats?.attendancePercentage ?? 0}%
                        </span>
                      </div>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {/* Attendance View */}
          {activeTab === "attendance" && (
            <Card>
              <CardHeader className="border-b bg-muted/20 flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <ClipboardCheck className="w-5 h-5 text-blue-600" />
                  My Attendance Records
                </CardTitle>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={fetchAttendance}
                  disabled={attendanceLoading}
                  className="gap-1"
                >
                  <RefreshCw
                    className={`w-3.5 h-3.5 ${attendanceLoading ? "animate-spin" : ""}`}
                  />
                  <span className="hidden sm:inline">Refresh</span>
                </Button>
              </CardHeader>
              <CardContent className="pt-6">
                {attendanceLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="animate-spin w-8 h-8 text-blue-600" />
                  </div>
                ) : attendanceRecords.length === 0 ? (
                  <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                    <ClipboardCheck className="w-10 h-10 sm:w-12 sm:h-12 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">
                      No attendance records found
                    </p>
                    <p className="text-sm text-muted-foreground mt-1">
                      Your attendance history will appear here.
                    </p>
                  </div>
                ) : (
                  <>
                    {/* Summary Bar */}
                    <div className="flex flex-wrap items-center gap-4 mb-4 p-3 rounded-lg bg-muted/30">
                      <span className="text-sm font-medium text-muted-foreground">
                        Summary:
                      </span>
                      <span className="flex items-center gap-1 text-sm">
                        <span className="w-2 h-2 rounded-full bg-emerald-500" />
                        Present: {attendanceStats?.presentDays ?? 0}
                      </span>
                      <span className="flex items-center gap-1 text-sm">
                        <span className="w-2 h-2 rounded-full bg-red-500" />
                        Absent: {attendanceStats?.absentDays ?? 0}
                      </span>
                      <span className="flex items-center gap-1 text-sm">
                        <span className="w-2 h-2 rounded-full bg-amber-500" />
                        Late: {attendanceStats?.lateDays ?? 0}
                      </span>
                    </div>

                    {/* Desktop Table */}
                    <div className="rounded-lg border overflow-hidden hidden sm:block">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/30">
                            <TableHead className="w-12 text-center">#</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead className="text-center">Status</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {attendanceRecords.slice(0, 30).map((record, index) => (
                            <TableRow key={record.id} className="hover:bg-muted/20">
                              <TableCell className="text-center text-muted-foreground text-sm">
                                {index + 1}
                              </TableCell>
                              <TableCell className="font-medium">
                                {new Date(record.date).toLocaleDateString("en-US", {
                                  weekday: "short",
                                  month: "short",
                                  day: "numeric",
                                  year: "numeric",
                                })}
                              </TableCell>
                              <TableCell className="text-center">
                                {getStatusBadge(record.status)}
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>

                    {/* Mobile Cards */}
                    <div className="sm:hidden space-y-2">
                      {attendanceRecords.slice(0, 30).map((record, index) => (
                        <div
                          key={record.id}
                          className="flex items-center justify-between p-3 rounded-lg border bg-card"
                        >
                          <div className="flex items-center gap-3">
                            <span className="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-xs font-medium">
                              {index + 1}
                            </span>
                            <span className="text-sm font-medium">
                              {new Date(record.date).toLocaleDateString("en-US", {
                                weekday: "short",
                                month: "short",
                                day: "numeric",
                              })}
                            </span>
                          </div>
                          {getStatusBadge(record.status)}
                        </div>
                      ))}
                    </div>

                    {attendanceRecords.length > 30 && (
                      <p className="text-sm text-muted-foreground text-center mt-4">
                        Showing latest 30 records of {attendanceRecords.length} total
                      </p>
                    )}
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
                  <RefreshCw
                    className={`w-3.5 h-3.5 ${noticesLoading ? "animate-spin" : ""}`}
                  />
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
                    <p className="text-muted-foreground font-medium">
                      No notices posted yet
                    </p>
                    <p className="text-sm text-muted-foreground mt-1">
                      Notices from the school will appear here.
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
                            <h4 className="font-semibold text-card-foreground truncate">
                              {notice.title}
                            </h4>
                            <p className="text-sm text-muted-foreground mt-1 line-clamp-3">
                              {notice.content}
                            </p>
                          </div>
                          <Badge
                            variant="secondary"
                            className={
                              notice.target_audience === "All"
                                ? "bg-blue-100 text-blue-700 shrink-0"
                                : "bg-amber-100 text-amber-700 shrink-0"
                            }
                          >
                            {notice.target_audience}
                          </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-3 flex items-center gap-1">
                          <CalendarIcon className="w-3 h-3" />
                          {new Date(
                            notice.publish_date || notice.created_at
                          ).toLocaleDateString("en-US", {
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

          {/* Exams View */}
          {activeTab === "exams" && (
            <Card>
              <CardHeader className="border-b bg-muted/20 flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <FileText className="w-5 h-5 text-orange-500" />
                  Exam Schedule
                </CardTitle>
                <Button variant="ghost" size="sm" onClick={fetchScheduledExams} disabled={examsLoading} className="gap-1">
                  <RefreshCw className={`w-3.5 h-3.5 ${examsLoading ? "animate-spin" : ""}`} />
                  <span className="hidden sm:inline">Refresh</span>
                </Button>
              </CardHeader>
              <CardContent className="pt-6">
                {examsLoading ? (
                  <div className="flex justify-center py-12"><Loader2 className="animate-spin w-8 h-8 text-orange-500" /></div>
                ) : scheduledExams.length === 0 ? (
                  <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                    <FileText className="w-10 h-10 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">No scheduled exams</p>
                    <p className="text-sm text-muted-foreground mt-1">Your exam schedule will appear here.</p>
                  </div>
                ) : (
                  <div className="space-y-8">
                    {Object.entries(
                      scheduledExams.reduce((acc, exam) => {
                        const examName = exam.exams?.name || 'Other';
                        if (!acc[examName]) acc[examName] = [];
                        acc[examName].push(exam);
                        return acc;
                      }, {} as Record<string, typeof scheduledExams>)
                    ).filter(([_, exams]) => {
                      // Only show routine if at least one subject is today or in the future
                      return exams.some(e => new Date(e.exam_date) >= new Date(new Date().setHours(0,0,0,0)));
                    }).map(([examName, exams]) => (
                      <div key={examName} className="space-y-3">
                        <div className="flex items-center justify-between border-b pb-2">
                          <h3 className="font-semibold text-lg text-slate-800">{examName}</h3>
                          <Button 
                            variant="outline" 
                            size="sm" 
                            className="gap-2"
                            onClick={() => {
                              const printWindow = window.open('', '_blank');
                              if (!printWindow) return;
                              const tableHtml = `
                                <html>
                                  <head>
                                    <title>${examName} - Routine</title>
                                    <style>
                                      body { font-family: system-ui, sans-serif; padding: 2rem; color: #333; max-width: 1000px; margin: 0 auto; }
                                      h1 { text-align: center; color: #111; border-bottom: 2px solid #eee; padding-bottom: 1rem; margin-bottom: 2rem; }
                                      table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
                                      th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                                      th { background-color: #f8f9fa; font-weight: 600; color: #444; }
                                      td { font-size: 0.95rem; }
                                      .footer { margin-top: 3rem; text-align: center; font-size: 0.875rem; color: #666; }
                                    </style>
                                  </head>
                                  <body>
                                    <h1>${examName} - Exam Routine</h1>
                                    <table>
                                      <thead>
                                        <tr>
                                          <th>Date</th>
                                          <th>Day</th>
                                          <th>Time</th>
                                          <th>Subject</th>
                                          <th>Code</th>
                                          <th>Marks (Total/Pass)</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        ${exams.map(exam => {
                                          const dateObj = new Date(exam.exam_date);
                                          const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                          const dayStr = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
                                          const formatTime = (time24: string) => {
                                            const [h, m] = time24.split(':');
                                            const hr = parseInt(h);
                                            return (hr % 12 || 12) + ':' + m + ' ' + (hr >= 12 ? 'PM' : 'AM');
                                          };
                                          return `
                                            <tr>
                                              <td>${dateStr}</td>
                                              <td>${dayStr}</td>
                                              <td>${formatTime(exam.start_time)} - ${formatTime(exam.end_time)}</td>
                                              <td><strong>${exam.subjects?.name || 'N/A'}</strong></td>
                                              <td>${exam.subjects?.code || 'N/A'}</td>
                                              <td>${exam.total_marks} / ${exam.passing_marks}</td>
                                            </tr>
                                          `;
                                        }).join('')}
                                      </tbody>
                                    </table>
                                    <div class="footer">Generated by RVA Examination System</div>
                                    <script>window.onload = () => { window.print(); window.close(); }</script>
                                  </body>
                                </html>
                              `;
                              printWindow.document.write(tableHtml);
                              printWindow.document.close();
                            }}
                          >
                            <Printer className="w-4 h-4" />
                            <span className="hidden sm:inline">Download Routine</span>
                          </Button>
                        </div>
                        <div className="border rounded-md overflow-hidden">
                          <Table>
                            <TableHeader className="bg-muted/50">
                              <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Time</TableHead>
                                <TableHead>Subject</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead className="text-right">Marks</TableHead>
                              </TableRow>
                            </TableHeader>
                            <TableBody>
                              {exams.map((exam) => {
                                const isPast = new Date(exam.exam_date) < new Date(new Date().setHours(0,0,0,0));
                                const formatTime = (time24: string) => {
                                  if (!time24) return '';
                                  const [h, m] = time24.split(':');
                                  const hr = parseInt(h);
                                  return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
                                };
                                return (
                                  <TableRow key={exam.id} className={isPast ? 'opacity-60 bg-muted/20' : ''}>
                                    <TableCell>
                                      <div className="font-medium">{new Date(exam.exam_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div>
                                      <div className="text-xs text-muted-foreground">{new Date(exam.exam_date).toLocaleDateString('en-US', { weekday: 'short' })}</div>
                                    </TableCell>
                                    <TableCell>
                                      <div className="text-sm">{formatTime(exam.start_time)} - {formatTime(exam.end_time)}</div>
                                    </TableCell>
                                    <TableCell>
                                      <div className="font-medium flex items-center gap-2">
                                        {exam.subjects?.name}
                                        {isPast && <Badge variant="secondary" className="text-[10px] h-4 px-1">Completed</Badge>}
                                      </div>
                                    </TableCell>
                                    <TableCell>{exam.subjects?.code}</TableCell>
                                    <TableCell className="text-right">
                                      <div className="text-sm font-medium">{exam.total_marks}</div>
                                      <div className="text-xs text-muted-foreground">Pass: {exam.passing_marks}</div>
                                    </TableCell>
                                  </TableRow>
                                );
                              })}
                            </TableBody>
                          </Table>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          )}

          {/* Results Lookup View */}
          {activeTab === "results" && (
            <Card>
              <CardHeader className="border-b bg-muted/20">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Trophy className="w-5 h-5 text-purple-500" />
                  Result Lookup
                </CardTitle>
              </CardHeader>
              <CardContent className="pt-6">
                {!lookupResult ? (
                  <div className="max-w-md mx-auto space-y-6">
                    <div className="text-center mb-6">
                      <Lock className="w-12 h-12 text-purple-300 mx-auto mb-3" />
                      <p className="text-muted-foreground text-sm">Enter your details to view your exam results securely.</p>
                    </div>

                    <div className="space-y-4">
                      <div className="space-y-2">
                        <label className="text-sm font-semibold">Select Exam *</label>
                        <select
                          value={selectedResultExam}
                          onChange={e => setSelectedResultExam(e.target.value)}
                          className="w-full border rounded-xl px-3 py-2 text-sm bg-background"
                        >
                          <option value="">Choose an exam...</option>
                          {resultExams.map((exam: any) => (
                            <option key={exam.id} value={exam.id}>
                              {exam.name} {exam.is_final ? '(FINAL)' : ''} — {exam.classes?.name}
                            </option>
                          ))}
                        </select>
                      </div>

                      <div className="space-y-2">
                        <label className="text-sm font-semibold">Password *</label>
                        <input
                          type="password"
                          value={resultPassword}
                          onChange={e => setResultPassword(e.target.value)}
                          placeholder="Enter your login password"
                          className="w-full border rounded-xl px-3 py-2 text-sm bg-background"
                        />
                      </div>

                      {lookupError && (
                        <div className="text-red-600 text-sm bg-red-50 p-3 rounded-xl">
                          {lookupError}
                        </div>
                      )}

                      <Button
                        onClick={handleResultLookup}
                        disabled={lookupLoading || !selectedResultExam || !resultRollNumber || !resultPassword}
                        className="w-full gap-2 bg-purple-600 hover:bg-purple-700 rounded-xl"
                      >
                        {lookupLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Eye className="w-4 h-4" />}
                        View Results
                      </Button>
                    </div>
                  </div>
                ) : (
                  <div className="space-y-6">
                    <div className="flex items-center justify-between">
                      <div>
                        <h3 className="font-bold text-lg">{lookupResult.exam.name}</h3>
                        <p className="text-sm text-muted-foreground">
                          {lookupResult.exam.className} · {lookupResult.student.name} · Roll: {lookupResult.student.rollNumber}
                        </p>
                      </div>
                      <Button variant="outline" size="sm" onClick={() => { setLookupResult(null); setLookupError(''); }}>
                        ← Back
                      </Button>
                    </div>

                    <div className="border rounded-lg overflow-hidden">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/30">
                            <TableHead>Subject</TableHead>
                            <TableHead className="text-center">Total</TableHead>
                            <TableHead className="text-center">Pass Marks</TableHead>
                            <TableHead className="text-center">Obtained</TableHead>
                            <TableHead className="text-center">Result</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {lookupResult.subjects.map((sub: any, idx: number) => (
                            <TableRow key={idx}>
                              <TableCell className="font-medium">{sub.subjectName} ({sub.subjectCode})</TableCell>
                              <TableCell className="text-center">{sub.totalMarks}</TableCell>
                              <TableCell className="text-center">{sub.passingMarks}</TableCell>
                              <TableCell className="text-center">
                                {!sub.hasResult ? (
                                  <span className="text-muted-foreground">—</span>
                                ) : sub.isAbsent ? (
                                  <span className="text-purple-600 font-medium">AB</span>
                                ) : (
                                  <span className={sub.passed ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold'}>
                                    {sub.marksObtained}
                                  </span>
                                )}
                              </TableCell>
                              <TableCell className="text-center">
                                {!sub.hasResult ? (
                                  <Badge variant="outline" className="text-amber-600 text-[10px]">Pending</Badge>
                                ) : sub.isAbsent ? (
                                  <Badge className="bg-purple-100 text-purple-700 text-[10px]">Absent</Badge>
                                ) : sub.passed ? (
                                  <Badge className="bg-green-100 text-green-700 text-[10px]">Pass</Badge>
                                ) : (
                                  <Badge className="bg-red-100 text-red-700 text-[10px]">Fail</Badge>
                                )}
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>

                    {/* Summary */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                      <Card className="bg-blue-50/80 border-0">
                        <CardContent className="p-3 text-center">
                          <p className="text-[10px] font-semibold text-gray-500 uppercase">Total</p>
                          <p className="text-lg font-bold text-blue-600">{lookupResult.totalObtained}/{lookupResult.totalMarks}</p>
                        </CardContent>
                      </Card>
                      <Card className="bg-amber-50/80 border-0">
                        <CardContent className="p-3 text-center">
                          <p className="text-[10px] font-semibold text-gray-500 uppercase">Percentage</p>
                          <p className="text-lg font-bold text-amber-600">{lookupResult.percentage}%</p>
                        </CardContent>
                      </Card>
                      <Card className={`border-0 ${lookupResult.overallPassed ? 'bg-green-50/80' : 'bg-red-50/80'}`}>
                        <CardContent className="p-3 text-center">
                          <p className="text-[10px] font-semibold text-gray-500 uppercase">Result</p>
                          <p className={`text-lg font-bold ${lookupResult.overallPassed ? 'text-green-600' : 'text-red-600'}`}>
                            {lookupResult.allResultsEntered ? (lookupResult.overallPassed ? 'PASS' : 'FAIL') : 'Pending'}
                          </p>
                        </CardContent>
                      </Card>
                      <Card className="bg-purple-50/80 border-0">
                        <CardContent className="p-3 text-center">
                          <p className="text-[10px] font-semibold text-gray-500 uppercase">Exam</p>
                          <p className="text-sm font-bold text-purple-600 truncate">
                            {lookupResult.exam.isFinal ? 'Final' : 'Regular'}
                          </p>
                        </CardContent>
                      </Card>
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
