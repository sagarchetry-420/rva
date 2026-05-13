import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  GraduationCap,
  Users,
  BookOpen,
  Bell,
  Loader2,
  PlusCircle,
  School,
  ChevronRight,
  TrendingUp,
  Megaphone,
  UserPlus,
  Calendar,
  Clock,
  Award,
  CheckSquare,
  MoreHorizontal,
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface DashboardStats {
  totalStudents: number;
  totalTeachers: number;
  totalClasses: number;
  totalNotices: number;
  totalSubjects: number;
  totalAttendanceRecords: number;
}

interface TopStudent {
  id: string;
  name: string;
  avatarUrl: string | null;
  className: string;
  enrollmentDate: string;
  attendancePercent: number;
  totalDays: number;
  presentDays: number;
}

interface RecentNotice {
  id: string;
  title: string;
  content: string;
  publish_date: string;
  target_audience: string;
}

interface ClassStat {
  id: string;
  name: string;
  schoolLevel: string;
  studentCount: number;
}

interface Subject {
  id: string;
  name: string;
  code: string;
}

export default function Dashboard() {
  const { toast } = useToast();

  const [stats, setStats] = useState<DashboardStats>({
    totalStudents: 0,
    totalTeachers: 0,
    totalClasses: 0,
    totalNotices: 0,
    totalSubjects: 0,
    totalAttendanceRecords: 0,
  });
  const [statsLoading, setStatsLoading] = useState(true);
  const [topStudents, setTopStudents] = useState<TopStudent[]>([]);
  const [recentNotices, setRecentNotices] = useState<RecentNotice[]>([]);
  const [classStats, setClassStats] = useState<ClassStat[]>([]);
  const [subjects, setSubjects] = useState<Subject[]>([]);

  useEffect(() => {
    fetchAllDashboardData();
  }, []);

  const fetchAllDashboardData = async () => {
    try {
      setStatsLoading(true);
      const [statsData, studentsData, noticesData, classData, subjectsData] = await Promise.all([
        api.get<DashboardStats>("/api/dashboard/stats"),
        api.get<TopStudent[]>("/api/dashboard/top-students").catch(() => []),
        api.get<RecentNotice[]>("/api/dashboard/recent-notices").catch(() => []),
        api.get<ClassStat[]>("/api/dashboard/class-stats").catch(() => []),
        api.get<Subject[]>("/api/dashboard/subjects").catch(() => []),
      ]);
      setStats(statsData);
      setTopStudents(studentsData);
      setRecentNotices(noticesData);
      setClassStats(classData);
      setSubjects(subjectsData);
    } catch (error: any) {
      console.error("Error fetching dashboard data:", error);
      toast({
        title: "Error",
        description: "Failed to load dashboard data.",
        variant: "destructive",
      });
    } finally {
      setStatsLoading(false);
    }
  };

  const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
  };

  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
  };

  const formatTime = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
  };

  const subjectIcons: Record<string, string> = {
    'Literature': '📖',
    'Mathematics': '📐',
    'Math': '📐',
    'English': '📝',
    'Science': '🔬',
    'Physics': '⚡',
    'Chemistry': '🧪',
    'Biology': '🧬',
    'History': '📜',
    'Geography': '🌍',
    'Computer': '💻',
    'Art': '🎨',
  };

  const getSubjectIcon = (name: string) => {
    for (const [key, icon] of Object.entries(subjectIcons)) {
      if (name.toLowerCase().includes(key.toLowerCase())) return icon;
    }
    return '📚';
  };

  // Brand colors: Gray tones instead of vibrant colors
  const subjectColors = ['bg-gray-200', 'bg-gray-200', 'bg-gray-200', 'bg-gray-200', 'bg-gray-200', 'bg-gray-200'];

  // Brand stat cards - using gray tones to match navbar
  const statCards = [
    {
      icon: Users,
      label: "Students",
      value: stats.totalStudents > 1000 ? `${(stats.totalStudents / 1000).toFixed(1)}K` : stats.totalStudents.toString(),
      rawValue: stats.totalStudents,
      color: "bg-gray-100/80",
      iconBg: "bg-gray-200",
      iconColor: "text-gray-700",
      path: "/dashboard/students",
    },
    {
      icon: GraduationCap,
      label: "Teachers",
      value: stats.totalTeachers.toString(),
      rawValue: stats.totalTeachers,
      color: "bg-gray-100/80",
      iconBg: "bg-gray-200",
      iconColor: "text-gray-700",
      path: "/dashboard/teachers",
    },
    {
      icon: Award,
      label: "Classes",
      value: stats.totalClasses.toString(),
      rawValue: stats.totalClasses,
      color: "bg-gray-100/80",
      iconBg: "bg-gray-200",
      iconColor: "text-gray-700",
      path: "/dashboard/classes",
    },
  ];

  const totalStudentsInClasses = classStats.reduce((acc, c) => acc + c.studentCount, 0);
  // Gray chart colors instead of brand colors
  const brandChartColors = ['#6B7280', '#9CA3AF', '#D1D5DB'];
  const classDistribution = classStats.slice(0, 4).map((c, i) => ({
    name: c.name,
    value: c.studentCount,
    percent: totalStudentsInClasses > 0 ? Math.round((c.studentCount / totalStudentsInClasses) * 100) : 0,
    color: brandChartColors[i % 3],
  }));

  return (
    <>
      {/* Welcome Section */}
      <div className="mb-6 md:mb-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <h2 className="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">Welcome back! 👋</h2>
        <p className="text-gray-500 mt-1">Navigate the future of education with Rose Valley Academy.</p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
        {statCards.map((stat, i) => (
          <Link key={i} to={stat.path} className="block group">
            <Card className={`${stat.color} border border-black/5 hover:shadow-md hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden relative`}>
              <div className={`absolute top-0 right-0 w-32 h-32 rounded-full ${stat.iconBg} mix-blend-multiply opacity-50 blur-2xl -translate-y-1/2 translate-x-1/3 group-hover:scale-110 transition-transform`}></div>
              <CardContent className="p-5 md:p-6 flex items-center justify-between relative z-10">
                <div>
                  <p className="text-sm font-semibold text-gray-600 mb-1">{stat.label}</p>
                  <p className="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">
                    {statsLoading ? (
                      <Loader2 className="w-7 h-7 animate-spin text-gray-400" />
                    ) : (
                      stat.value
                    )}
                  </p>
                </div>
                <div className={`w-14 h-14 md:w-16 md:h-16 rounded-2xl ${stat.iconBg} flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform`}>
                  <stat.icon className={`w-7 h-7 md:w-8 md:h-8 ${stat.iconColor}`} />
                </div>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>

      {/* Main Grid */}
      <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
        {/* Left Column */}
        <div className="md:col-span-12 xl:col-span-5 space-y-6">
          {/* Class Statistics Card */}
          <Card className="border-0 shadow-sm rounded-2xl overflow-hidden bg-white hover:shadow-md transition-shadow">
            <CardHeader className="pb-4 bg-gray-50/50 border-b border-gray-50">
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg font-bold text-gray-800">Class Overview</CardTitle>
                <Link to="/dashboard/classes" className="text-sm text-gray-600 font-semibold hover:text-gray-800 flex items-center gap-1 group">
                  View All <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                </Link>
              </div>
            </CardHeader>
            <CardContent className="pt-6">
              {statsLoading ? (
                <div className="flex justify-center py-8">
                  <Loader2 className="w-8 h-8 animate-spin text-orange-400" />
                </div>
              ) : classStats.length === 0 ? (
                <div className="text-center py-10 text-gray-500">
                  <School className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                  <p className="font-medium text-gray-600">No classes found</p>
                  <Button size="sm" className="mt-4 bg-gray-700 hover:bg-gray-800 rounded-xl" asChild>
                    <Link to="/dashboard/classes">Add Class</Link>
                  </Button>
                </div>
              ) : (
                <div className="grid grid-cols-2 gap-4">
                  {classStats.slice(0, 4).map((cls, i) => (
                    <div key={cls.id} className="border border-gray-200 rounded-2xl p-4 md:p-5 hover:border-gray-300 hover:bg-gray-50 transition-colors group">
                      <div className="flex items-center gap-2 mb-3">
                        <div className={`w-10 h-10 rounded-xl ${ ['bg-gray-200', 'bg-gray-200', 'bg-gray-200', 'bg-gray-200'][i % 3]} flex items-center justify-center group-hover:scale-110 transition-transform`}>
                          <School className={`w-5 h-5 ${ ['text-gray-700', 'text-gray-700', 'text-gray-700', 'text-gray-700'][i % 3]}`} />
                        </div>
                      </div>
                      <p className="font-bold text-gray-800">{cls.name}</p>
                      <p className="text-sm text-gray-500 font-medium mt-1">{cls.studentCount} students</p>
                      <p className="text-xs text-gray-400 mt-0.5">{cls.schoolLevel}</p>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Star Students */}
          <Card className="border-0 shadow-sm rounded-2xl overflow-hidden bg-white hover:shadow-md transition-shadow">
            <CardHeader className="pb-4 bg-gray-50/50 border-b border-gray-50">
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg font-bold text-gray-800">Top Students</CardTitle>
                <Link to="/dashboard/students" className="text-sm text-orange-600 font-semibold hover:text-orange-700 flex items-center gap-1 group">
                  View All <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                </Link>
              </div>
            </CardHeader>
            <CardContent className="pt-0 px-0">
              {statsLoading ? (
                <div className="flex justify-center py-8">
                  <Loader2 className="w-8 h-8 animate-spin text-orange-400" />
                </div>
              ) : topStudents.length === 0 ? (
                <div className="text-center py-10 text-gray-500 px-6">
                  <Users className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                  <p className="font-medium text-gray-600">No students found</p>
                  <Button size="sm" className="mt-4 bg-gray-700 hover:bg-gray-800 rounded-xl" asChild>
                    <Link to="/dashboard/students/add">Add Student</Link>
                  </Button>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead className="bg-white">
                      <tr className="text-left text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                        <th className="py-4 px-6 font-semibold">Name</th>
                        <th className="py-4 px-6 font-semibold">Class</th>
                        <th className="py-4 px-6 font-semibold">Attendance</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-50">
                      {topStudents.map((student, i) => (
                        <tr key={student.id} className="hover:bg-gray-50/50 transition-colors">
                          <td className="py-4 px-6 whitespace-nowrap">
                            <div className="flex items-center gap-3">
                              <div className={`w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-sm ${['bg-gray-700', 'bg-gray-700', 'bg-gray-700'][i % 3]}`}>
                                {getInitials(student.name)}
                              </div>
                              <span className="font-semibold text-gray-800">{student.name}</span>
                            </div>
                          </td>
                          <td className="py-4 px-6 whitespace-nowrap text-gray-600 font-medium">{student.className}</td>
                          <td className="py-4 px-6 whitespace-nowrap">
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${student.attendancePercent >= 80 ? 'bg-gray-100 text-gray-700' : student.attendancePercent >= 60 ? 'bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-700'}`}>
                              {student.attendancePercent}%
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Notifications */}
          <Card className="border-0 shadow-sm rounded-2xl overflow-hidden bg-white hover:shadow-md transition-shadow">
            <CardHeader className="pb-4 bg-gray-50/50 border-b border-gray-50">
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg font-bold text-gray-800">Recent Notices</CardTitle>
                <Link to="/dashboard/notices" className="text-sm text-orange-600 font-semibold hover:text-orange-700 flex items-center gap-1 group">
                  View All <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                </Link>
              </div>
            </CardHeader>
            <CardContent className="pt-6 space-y-5">
              {statsLoading ? (
                <div className="flex justify-center py-8">
                  <Loader2 className="w-8 h-8 animate-spin text-orange-400" />
                </div>
              ) : recentNotices.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <Bell className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                  <p className="font-medium">No notices yet</p>
                  <Button size="sm" className="mt-4 bg-gray-700 hover:bg-gray-800 rounded-xl" asChild>
                    <Link to="/dashboard/notices/create">Create Notice</Link>
                  </Button>
                </div>
              ) : (
                recentNotices.map((notice, i) => (
                  <div key={notice.id} className="flex items-start gap-4 group">
                    <div className={`shrink-0 w-12 h-12 rounded-2xl ${['bg-gray-200 text-gray-700', 'bg-gray-200 text-gray-700', 'bg-gray-200 text-gray-700'][i % 3]} flex items-center justify-center group-hover:scale-110 transition-transform`}>
                      <Bell className="w-5 h-5" />
                    </div>
                    <div className="flex-1 min-w-0 pt-0.5">
                      <p className="font-bold text-gray-800 truncate pr-2">{notice.title}</p>
                      <div className="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-gray-500 font-medium">
                        <span className="flex items-center gap-1 bg-gray-50 px-2 py-1 rounded-md">
                          <Clock className="w-3.5 h-3.5" /> {formatTime(notice.publish_date)}
                        </span>
                        <span className="flex items-center gap-1 bg-gray-50 px-2 py-1 rounded-md">
                          <Calendar className="w-3.5 h-3.5" /> {formatDate(notice.publish_date)}
                        </span>
                      </div>
                    </div>
                    <span className={`shrink-0 text-xs font-bold px-2.5 py-1 rounded-full ${
                      notice.target_audience === 'All' ? 'bg-gray-100 text-gray-700 border border-gray-200' :
                      notice.target_audience === 'Students' ? 'bg-gray-100 text-gray-700 border border-gray-200' :
                      'bg-gray-100 text-gray-700 border border-gray-200'
                    }`}>
                      {notice.target_audience}
                    </span>
                  </div>
                ))
              )}
            </CardContent>
          </Card>
        </div>

        {/* Middle Column */}
        <div className="md:col-span-12 xl:col-span-4 space-y-6">
          {/* Subjects / Library */}
          <Card className="border-0 shadow-sm rounded-2xl bg-white hover:shadow-md transition-shadow">
            <CardHeader className="bg-gray-900 text-white rounded-t-2xl">
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg font-bold text-white">Total Notices</CardTitle>
              </div>
            </CardHeader>
            <CardContent className="bg-gray-800 text-white rounded-b-2xl p-6 relative">
              <div className="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full blur-2xl -translate-y-1/2 translate-x-1/3"></div>
              <div className="flex items-center justify-between mb-4">
                <div className="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-sm">
                  <Megaphone className="w-5 h-5 text-white" />
                </div>
                {stats.totalNotices > 0 && (
                  <span className="text-xs font-bold bg-white/20 backdrop-blur-md px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                    <TrendingUp className="w-3.5 h-3.5" /> Active
                  </span>
                )}
              </div>
              <p className="text-5xl font-extrabold mb-2 tracking-tight">
                {statsLoading ? <Loader2 className="w-8 h-8 animate-spin text-white/70" /> : stats.totalNotices}
              </p>
              <p className="text-sm text-white/80 font-medium mb-4">
                Total announcements active in the system.
              </p>
              <Link to="/dashboard/notices" className="text-sm font-bold text-white flex items-center gap-1 group w-max">
                Manage notices <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
              </Link>
            </CardContent>
            <CardContent className="space-y-2">
              {statsLoading ? (
                <div className="flex justify-center py-8">
                  <Loader2 className="w-8 h-8 animate-spin text-orange-400" />
                </div>
              ) : subjects.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <BookOpen className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                  <p className="font-medium">No subjects found</p>
                  <Button size="sm" className="mt-4 rounded-xl" asChild>
                    <Link to="/dashboard/classes">Add Subject</Link>
                  </Button>
                </div>
              ) : (
                subjects.slice(0, 5).map((subject, i) => (
                  <div key={subject.id} className="flex items-center justify-between p-3.5 hover:bg-gray-50 rounded-2xl transition-all border border-transparent hover:border-gray-100 group">
                    <div className="flex items-center gap-4">
                      <div className={`w-12 h-12 rounded-xl ${subjectColors[i % subjectColors.length]} flex items-center justify-center text-xl shadow-sm group-hover:scale-105 transition-transform`}>
                        {getSubjectIcon(subject.name)}
                      </div>
                      <div>
                        <p className="font-bold text-gray-800">{subject.name}</p>
                        <p className="text-xs text-gray-500 font-medium mt-0.5">Code: {subject.code}</p>
                      </div>
                    </div>
                    <ChevronRight className="w-4 h-4 text-gray-300 group-hover:text-orange-500 transition-colors" />
                  </div>
                ))
              )}
            </CardContent>
          </Card>

          {/* Total Stats Summary */}
          <Card className="border-0 shadow-sm rounded-2xl bg-gray-900 text-white overflow-hidden relative">
            <div className="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full blur-2xl -translate-y-1/2 translate-x-1/3"></div>
            <CardContent className="p-6 relative z-10">
              <div className="flex items-center justify-between mb-4">
                <div className="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-sm">
                  <Megaphone className="w-5 h-5 text-white" />
                </div>
                {stats.totalNotices > 0 && (
                  <span className="text-xs font-bold bg-white/20 backdrop-blur-md px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                    <TrendingUp className="w-3.5 h-3.5" /> Active
                  </span>
                )}
              </div>
              <p className="text-5xl font-extrabold mb-2 tracking-tight">
                {statsLoading ? <Loader2 className="w-8 h-8 animate-spin text-white/70" /> : stats.totalNotices}
              </p>
              <p className="text-sm text-white/80 font-medium mb-4">
                Total announcements active in the system across all classes.
              </p>
              <Link to="/dashboard/notices" className="text-sm font-bold text-white flex items-center gap-1 group w-max">
                Manage notices <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
              </Link>
            </CardContent>
          </Card>

          {/* Quick Actions Card */}
          <Card className="border-0 shadow-sm rounded-2xl overflow-hidden bg-gray-800 text-white">
            <CardContent className="p-6 relative">
              <div className="absolute top-0 right-0 w-24 h-24 bg-white/5 rounded-full blur-xl -translate-y-1/2 translate-x-1/2"></div>
              <span className="text-xs font-bold bg-white/10 text-gray-300 px-2.5 py-1 rounded-full mb-4 inline-block">Quick Actions</span>
              <h3 className="text-xl font-bold mb-5">Manage your school</h3>
              <div className="space-y-3 relative z-10">
                <Button size="sm" className="bg-gray-700 hover:bg-gray-600 w-full justify-start rounded-xl h-11" asChild>
                  <Link to="/dashboard/students/add">
                    <UserPlus className="w-4 h-4 mr-3" /> Add Student
                  </Link>
                </Button>
                <Button size="sm" variant="outline" className="w-full justify-start border-white/10 text-white bg-white/5 hover:bg-white/10 rounded-xl h-11" asChild>
                  <Link to="/dashboard/teachers/add">
                    <UserPlus className="w-4 h-4 mr-3" /> Add Teacher
                  </Link>
                </Button>
                <Button size="sm" variant="outline" className="w-full justify-start border-white/10 text-white bg-white/5 hover:bg-white/10 rounded-xl h-11" asChild>
                  <Link to="/dashboard/notices/create">
                    <PlusCircle className="w-4 h-4 mr-3" /> Create Notice
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Right Column */}
        <div className="md:col-span-12 xl:col-span-3 space-y-6">
          {/* Student Distribution */}
          <Card className="border-0 shadow-sm rounded-2xl bg-white hover:shadow-md transition-shadow">
            <CardHeader className="pb-4">
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg font-bold text-gray-800">Distribution</CardTitle>
                <Button variant="ghost" size="icon" className="w-8 h-8 rounded-full">
                  <MoreHorizontal className="w-4 h-4 text-gray-400" />
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {statsLoading ? (
                <div className="flex justify-center py-8">
                  <Loader2 className="w-8 h-8 animate-spin text-orange-400" />
                </div>
              ) : classDistribution.length === 0 ? (
                <div className="text-center py-8 text-gray-500 font-medium">
                  <p className="text-sm">No data available</p>
                </div>
              ) : (
                <>
                  {/* Donut Chart */}
                  <div className="relative w-44 h-44 mx-auto mb-6">
                    <svg viewBox="0 0 100 100" className="w-full h-full -rotate-90 drop-shadow-sm">
                      <circle cx="50" cy="50" r="35" fill="none" stroke="#F1F5F9" strokeWidth="12" />
                      {classDistribution.map((item, i) => {
                        const prevPercent = classDistribution.slice(0, i).reduce((acc, c) => acc + c.percent, 0);
                        const dashArray = (item.percent / 100) * 220;
                        const dashOffset = -(prevPercent / 100) * 220;
                        return (
                          <circle
                            key={item.name}
                            cx="50" cy="50" r="35" fill="none"
                            stroke={item.color} strokeWidth="12"
                            strokeDasharray={`${dashArray} 220`}
                            strokeDashoffset={dashOffset}
                            strokeLinecap="round"
                            className="transition-all duration-1000 ease-out"
                          />
                        );
                      })}
                    </svg>
                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                      <p className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Total</p>
                      <p className="text-3xl font-extrabold text-gray-900 mt-0.5">{totalStudentsInClasses}</p>
                    </div>
                  </div>
                  {/* Legend */}
                  <div className="flex flex-col gap-3">
                    {classDistribution.map((item) => (
                      <div key={item.name} className="flex items-center justify-between text-sm">
                        <div className="flex items-center gap-2.5">
                          <div className="w-3 h-3 rounded-full shadow-sm" style={{ backgroundColor: item.color }}></div>
                          <span className="font-semibold text-gray-600">{item.name}</span>
                        </div>
                        <span className="font-bold text-gray-900">{item.value}</span>
                      </div>
                    ))}
                  </div>
                </>
              )}
            </CardContent>
          </Card>

          {/* Class Performance */}
          <Card className="border-0 shadow-sm rounded-2xl bg-white hover:shadow-md transition-shadow">
            <CardHeader className="pb-4">
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg font-bold text-gray-800">Capacity</CardTitle>
                <select className="text-xs border-gray-200 rounded-lg px-2.5 py-1.5 bg-gray-50 font-medium text-gray-600 focus:ring-orange-500 focus:border-orange-500 outline-none">
                  <option>All Classes</option>
                </select>
              </div>
            </CardHeader>
            <CardContent className="space-y-5">
              {statsLoading ? (
                <div className="flex justify-center py-8">
                  <Loader2 className="w-8 h-8 animate-spin text-orange-400" />
                </div>
              ) : classStats.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <p className="text-sm font-medium">No classes found</p>
                </div>
              ) : (
                classStats.slice(0, 5).map((cls, i) => {
                  const maxStudents = Math.max(...classStats.map(c => c.studentCount), 1);
                  const percent = Math.round((cls.studentCount / maxStudents) * 100);
                  return (
                    <div key={cls.id} className="space-y-2 group">
                      <div className="flex items-center justify-between text-sm">
                        <span className="font-bold text-gray-700">{cls.name}</span>
                        <span className="font-bold text-gray-900">{cls.studentCount}</span>
                      </div>
                      <div className="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                        <div
                          className={`h-full rounded-full ${ ['bg-gray-700', 'bg-gray-700', 'bg-gray-700'][i % 3]} group-hover:opacity-80 transition-all duration-500 ease-out`}
                          style={{ width: `${percent}%` }}
                        ></div>
                      </div>
                    </div>
                  );
                })
              )}
            </CardContent>
          </Card>

          {/* Attendance Summary */}
          <Card className="border-0 shadow-sm rounded-2xl bg-white hover:shadow-md transition-shadow">
            <CardHeader className="pb-4">
              <CardTitle className="text-lg font-bold text-gray-800">Quick Stats</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-center justify-between p-3.5 bg-gray-100 rounded-xl border border-gray-200">
                <div className="flex items-center gap-2 text-gray-700">
                  <BookOpen className="w-4 h-4" />
                  <span className="text-sm font-bold">Subjects</span>
                </div>
                <span className="font-extrabold text-gray-700 text-base">{stats.totalSubjects}</span>
              </div>
              <div className="flex items-center justify-between p-3.5 bg-gray-100 rounded-xl border border-gray-200">
                <div className="flex items-center gap-2 text-gray-700">
                  <CheckSquare className="w-4 h-4" />
                  <span className="text-sm font-bold">Records</span>
                </div>
                <span className="font-extrabold text-gray-700 text-base">{stats.totalAttendanceRecords}</span>
              </div>
              <div className="flex items-center justify-between p-3.5 bg-gray-100 rounded-xl border border-gray-200">
                <div className="flex items-center gap-2 text-gray-700">
                  <School className="w-4 h-4" />
                  <span className="text-sm font-bold">Rooms</span>
                </div>
                <span className="font-extrabold text-gray-700 text-base">{stats.totalClasses}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </>
  );
}
