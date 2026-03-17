import { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
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
} from "lucide-react";

interface TeacherStats {
  department: string;
  hireDate: string;
  totalClasses: number;
  totalSubjects: number;
  totalStudents: number;
  attendanceMarkedToday: number;
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
  const { user, role, loading: authLoading, signOut } = useAuth();
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
    try {
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
      const data = await api.get<Notice[]>("/api/notices");
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

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="border-b border-border bg-card px-6 py-4 flex items-center justify-between shadow-sm sticky top-0 z-10">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center shadow-inner">
            <GraduationCap className="w-6 h-6 text-white" />
          </div>
          <div>
            <h1 className="font-bold text-lg text-card-foreground leading-tight">
              Rose Valley Academy
            </h1>
            <p className="text-xs text-muted-foreground font-medium">
              Teacher Portal
            </p>
          </div>
        </div>
        <div className="flex items-center gap-4">
          <div className="hidden sm:flex flex-col items-end">
            <span className="text-sm font-medium text-foreground">
              {user.email}
            </span>
            <span className="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full uppercase font-bold tracking-wider">
              Teacher
            </span>
          </div>
          <Button
            variant="outline"
            size="sm"
            onClick={handleSignOut}
            className="gap-2 border-destructive/20 text-destructive hover:bg-destructive hover:text-white transition-all shadow-sm"
          >
            <LogOut className="w-4 h-4" />
            <span className="hidden sm:inline">Sign Out</span>
          </Button>
        </div>
      </header>

      <main className="container mx-auto px-4 py-8 max-w-7xl">
        {/* Welcome Section */}
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
          <div>
            <div className="flex items-center gap-2 mb-1">
              <LayoutDashboard className="w-5 h-5 text-emerald-600" />
              <span className="text-sm font-semibold uppercase tracking-wider text-emerald-600">
                Dashboard
              </span>
            </div>
            <h2 className="text-3xl font-bold text-foreground tracking-tight">
              Welcome, Teacher!
            </h2>
            <p className="text-muted-foreground flex items-center gap-2 mt-1">
              <CalendarIcon className="w-4 h-4" /> {formattedDate}
            </p>
          </div>
          <Button
            onClick={() => {
              fetchTeacherStats();
              fetchNotices();
            }}
            variant="outline"
            size="sm"
            className="gap-2 hover:bg-emerald-50 border-emerald-200 text-emerald-700"
            disabled={statsLoading}
          >
            <RefreshCw
              className={`w-4 h-4 ${statsLoading ? "animate-spin" : ""}`}
            />
            Refresh
          </Button>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <Card className="border-l-4 border-l-emerald-500">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                My Classes
              </CardTitle>
              <BookOpen className="w-5 h-5 text-emerald-500" />
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-bold text-card-foreground">
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
              <p className="text-3xl font-bold text-card-foreground">
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
              <p className="text-3xl font-bold text-card-foreground">
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
              <p className="text-3xl font-bold text-card-foreground">
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

        {/* Tabbed Content */}
        <Tabs defaultValue="attendance" className="space-y-6">
          <TabsList className="bg-muted/50 p-1">
            <TabsTrigger
              value="attendance"
              className="gap-2 data-[state=active]:bg-emerald-600 data-[state=active]:text-white"
            >
              <ClipboardCheck className="w-4 h-4" />
              Attendance
            </TabsTrigger>
            <TabsTrigger
              value="notices"
              className="gap-2 data-[state=active]:bg-emerald-600 data-[state=active]:text-white"
            >
              <Bell className="w-4 h-4" />
              Notices
            </TabsTrigger>
          </TabsList>

          {/* ============ ATTENDANCE TAB ============ */}
          <TabsContent value="attendance" className="space-y-6">
            <Card>
              <CardHeader className="border-b bg-muted/20">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div>
                    <CardTitle className="flex items-center gap-2 text-lg">
                      <ClipboardCheck className="w-5 h-5 text-emerald-600" />
                      Mark Attendance
                    </CardTitle>
                    <p className="text-sm text-muted-foreground mt-1">
                      Select a class to mark today's attendance
                    </p>
                  </div>
                  <div className="w-full sm:w-64">
                    <Select onValueChange={fetchStudents} value={selectedClass}>
                      <SelectTrigger>
                        <SelectValue placeholder="Choose a class" />
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
                  <div className="text-center py-16 border-2 border-dashed rounded-lg bg-muted/5">
                    <ClipboardCheck className="w-12 h-12 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">
                      Select a class above to begin
                    </p>
                    <p className="text-sm text-muted-foreground mt-1">
                      {assignedClasses.length === 0
                        ? "No classes assigned yet. Please contact your administrator."
                        : `You have ${assignedClasses.length} class${assignedClasses.length !== 1 ? "es" : ""} assigned.`}
                    </p>
                  </div>
                ) : attendanceLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="animate-spin w-8 h-8 text-emerald-600" />
                  </div>
                ) : students.length === 0 ? (
                  <div className="text-center py-16 border-2 border-dashed rounded-lg bg-muted/5">
                    <Users className="w-12 h-12 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">
                      No students found in this class
                    </p>
                  </div>
                ) : (
                  <>
                    {/* Quick actions bar */}
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                      <div className="flex items-center gap-3">
                        <span className="text-sm font-medium text-muted-foreground">
                          Quick set all:
                        </span>
                        <div className="flex gap-2">
                          <Button
                            size="sm"
                            variant="outline"
                            className="text-xs h-7 border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                            onClick={() => setAllStatus("Present")}
                          >
                            <CheckCircle className="w-3 h-3 mr-1" /> All
                            Present
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            className="text-xs h-7 border-red-200 text-red-700 hover:bg-red-50"
                            onClick={() => setAllStatus("Absent")}
                          >
                            <XCircle className="w-3 h-3 mr-1" /> All Absent
                          </Button>
                        </div>
                      </div>
                      <div className="flex items-center gap-3 text-xs font-medium">
                        <span className="flex items-center gap-1">
                          <span className="w-2 h-2 rounded-full bg-emerald-500" />
                          Present: {attendanceSummary.present}
                        </span>
                        <span className="flex items-center gap-1">
                          <span className="w-2 h-2 rounded-full bg-red-500" />
                          Absent: {attendanceSummary.absent}
                        </span>
                        <span className="flex items-center gap-1">
                          <span className="w-2 h-2 rounded-full bg-amber-500" />
                          Late: {attendanceSummary.late}
                        </span>
                      </div>
                    </div>

                    {/* Students table */}
                    <div className="rounded-lg border overflow-hidden">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/30">
                            <TableHead className="w-12 text-center">
                              #
                            </TableHead>
                            <TableHead>Student Name</TableHead>
                            <TableHead className="text-center">
                              Status
                            </TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {students.map((student, index) => (
                            <TableRow
                              key={student.id}
                              className="hover:bg-muted/20"
                            >
                              <TableCell className="text-center text-muted-foreground text-sm">
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
                              <TableCell>
                                <div className="flex justify-center gap-2">
                                  <Button
                                    size="sm"
                                    variant={
                                      attendance[student.id] === "Present"
                                        ? "default"
                                        : "outline"
                                    }
                                    className={
                                      attendance[student.id] === "Present"
                                        ? "bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                                        : "hover:border-emerald-300 hover:text-emerald-700"
                                    }
                                    onClick={() =>
                                      setAttendance({
                                        ...attendance,
                                        [student.id]: "Present",
                                      })
                                    }
                                  >
                                    <CheckCircle className="w-3.5 h-3.5 mr-1" />
                                    Present
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant={
                                      attendance[student.id] === "Absent"
                                        ? "destructive"
                                        : "outline"
                                    }
                                    className={
                                      attendance[student.id] !== "Absent"
                                        ? "hover:border-red-300 hover:text-red-700"
                                        : "shadow-sm"
                                    }
                                    onClick={() =>
                                      setAttendance({
                                        ...attendance,
                                        [student.id]: "Absent",
                                      })
                                    }
                                  >
                                    <XCircle className="w-3.5 h-3.5 mr-1" />
                                    Absent
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant={
                                      attendance[student.id] === "Late"
                                        ? "default"
                                        : "outline"
                                    }
                                    className={
                                      attendance[student.id] === "Late"
                                        ? "bg-amber-500 hover:bg-amber-600 text-white shadow-sm"
                                        : "hover:border-amber-300 hover:text-amber-700"
                                    }
                                    onClick={() =>
                                      setAttendance({
                                        ...attendance,
                                        [student.id]: "Late",
                                      })
                                    }
                                  >
                                    <Clock className="w-3.5 h-3.5 mr-1" />
                                    Late
                                  </Button>
                                </div>
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>

                    {/* Submit bar */}
                    <div className="flex items-center justify-between mt-6 pt-4 border-t">
                      <p className="text-sm text-muted-foreground">
                        <span className="font-medium text-foreground">
                          {students.length}
                        </span>{" "}
                        students in class
                      </p>
                      <Button
                        onClick={saveAttendance}
                        disabled={saving}
                        className="bg-emerald-600 hover:bg-emerald-700 gap-2 px-6"
                      >
                        {saving ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                          <CheckCircle className="h-4 w-4" />
                        )}
                        Submit Attendance
                      </Button>
                    </div>
                  </>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* ============ NOTICES TAB (View Only) ============ */}
          <TabsContent value="notices" className="space-y-6">
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
                  Refresh
                </Button>
              </CardHeader>
              <CardContent className="pt-6">
                {noticesLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="animate-spin w-8 h-8 text-muted-foreground" />
                  </div>
                ) : notices.length === 0 ? (
                  <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                    <Bell className="w-12 h-12 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">
                      No notices posted yet
                    </p>
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
                                : notice.target_audience === "Staff"
                                  ? "bg-purple-100 text-purple-700 shrink-0"
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
          </TabsContent>
        </Tabs>
      </main>
    </div>
  );
}
