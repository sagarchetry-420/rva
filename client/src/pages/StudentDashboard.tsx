import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { useAuth } from "@/hooks/useAuth";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { useToast } from "@/hooks/use-toast";
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
} from "lucide-react";

interface StudentProfile {
  firstName: string;
  lastName: string;
  email: string;
  className: string;
  classId: string;
  rollNumber?: string;
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
      const data = await api.get<Notice[]>("/api/notices");
      // Filter notices for students (All or Students audience)
      const studentNotices = data?.filter(
        (n) => n.target_audience === "All" || n.target_audience === "Students"
      ) || [];
      setNotices(studentNotices);
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

  if (authLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="animate-spin w-8 h-8 text-blue-600" />
      </div>
    );
  }

  if (!user) return null;

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="border-b border-border bg-card px-6 py-4 flex items-center justify-between shadow-sm sticky top-0 z-10">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center shadow-inner">
            <GraduationCap className="w-6 h-6 text-white" />
          </div>
          <div>
            <h1 className="font-bold text-lg text-card-foreground leading-tight">
              Rose Valley Academy
            </h1>
            <p className="text-xs text-muted-foreground font-medium">
              Student Portal
            </p>
          </div>
        </div>
        <div className="flex items-center gap-4">
          <div className="hidden sm:flex flex-col items-end">
            <span className="text-sm font-medium text-foreground">
              {profileLoading ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                `${profile?.firstName || ""} ${profile?.lastName || ""}`.trim() || user.email
              )}
            </span>
            <span className="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full uppercase font-bold tracking-wider">
              Student
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
              <LayoutDashboard className="w-5 h-5 text-blue-600" />
              <span className="text-sm font-semibold uppercase tracking-wider text-blue-600">
                Dashboard
              </span>
            </div>
            <h2 className="text-3xl font-bold text-foreground tracking-tight">
              Welcome, {profile?.firstName || "Student"}!
            </h2>
            <p className="text-muted-foreground flex items-center gap-2 mt-1">
              <CalendarIcon className="w-4 h-4" /> {formattedDate}
            </p>
          </div>
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
            Refresh
          </Button>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <Card className="border-l-4 border-l-blue-500">
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

          <Card className="border-l-4 border-l-emerald-500">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Present Days
              </CardTitle>
              <CheckCircle className="w-5 h-5 text-emerald-500" />
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-bold text-card-foreground">
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

          <Card className="border-l-4 border-l-red-500">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Absent Days
              </CardTitle>
              <XCircle className="w-5 h-5 text-red-500" />
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-bold text-card-foreground">
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

          <Card className="border-l-4 border-l-amber-500">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Attendance Rate
              </CardTitle>
              <TrendingUp className="w-5 h-5 text-amber-500" />
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-bold text-card-foreground">
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

        {/* Tabbed Content */}
        <Tabs defaultValue="profile" className="space-y-6">
          <TabsList className="bg-muted/50 p-1">
            <TabsTrigger
              value="profile"
              className="gap-2 data-[state=active]:bg-blue-600 data-[state=active]:text-white"
            >
              <User className="w-4 h-4" />
              Profile
            </TabsTrigger>
            <TabsTrigger
              value="attendance"
              className="gap-2 data-[state=active]:bg-blue-600 data-[state=active]:text-white"
            >
              <ClipboardCheck className="w-4 h-4" />
              Attendance
            </TabsTrigger>
            <TabsTrigger
              value="notices"
              className="gap-2 data-[state=active]:bg-blue-600 data-[state=active]:text-white"
            >
              <Bell className="w-4 h-4" />
              Notices
            </TabsTrigger>
          </TabsList>

          {/* ============ PROFILE TAB ============ */}
          <TabsContent value="profile" className="space-y-6">
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
                    <User className="w-12 h-12 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground font-medium">
                      Profile information not available
                    </p>
                  </div>
                ) : (
                  <div className="grid md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                      <div className="flex items-center gap-4 p-4 rounded-lg bg-muted/30">
                        <div className="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                          <User className="w-8 h-8 text-blue-600" />
                        </div>
                        <div>
                          <h3 className="text-xl font-semibold text-foreground">
                            {profile.firstName} {profile.lastName}
                          </h3>
                          <p className="text-sm text-muted-foreground">
                            {profile.email}
                          </p>
                        </div>
                      </div>
                    </div>
                    <div className="space-y-3">
                      <div className="flex justify-between items-center py-3 border-b">
                        <span className="text-muted-foreground">Class</span>
                        <span className="font-medium">{profile.className}</span>
                      </div>
                      {profile.rollNumber && (
                        <div className="flex justify-between items-center py-3 border-b">
                          <span className="text-muted-foreground">Roll Number</span>
                          <span className="font-medium">{profile.rollNumber}</span>
                        </div>
                      )}
                      {profile.admissionDate && (
                        <div className="flex justify-between items-center py-3 border-b">
                          <span className="text-muted-foreground">Admission Date</span>
                          <span className="font-medium">
                            {new Date(profile.admissionDate).toLocaleDateString("en-US", {
                              month: "short",
                              day: "numeric",
                              year: "numeric",
                            })}
                          </span>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* ============ ATTENDANCE TAB ============ */}
          <TabsContent value="attendance" className="space-y-6">
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
                  Refresh
                </Button>
              </CardHeader>
              <CardContent className="pt-6">
                {attendanceLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="animate-spin w-8 h-8 text-blue-600" />
                  </div>
                ) : attendanceRecords.length === 0 ? (
                  <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                    <ClipboardCheck className="w-12 h-12 text-muted-foreground/40 mx-auto mb-4" />
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

                    {/* Attendance Table */}
                    <div className="rounded-lg border overflow-hidden">
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
                    {attendanceRecords.length > 30 && (
                      <p className="text-sm text-muted-foreground text-center mt-4">
                        Showing latest 30 records of {attendanceRecords.length} total
                      </p>
                    )}
                  </>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* ============ NOTICES TAB ============ */}
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
          </TabsContent>
        </Tabs>
      </main>
    </div>
  );
}
