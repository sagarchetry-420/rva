import { useAuth } from "@/hooks/useAuth";
import { useNavigate, Link, Outlet, useLocation } from "react-router-dom";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  GraduationCap,
  Users,
  BookOpen,
  Bell,
  LogOut,
  Loader2,
  PlusCircle,
  School,
  RefreshCw,
  LayoutDashboard,
  ChevronRight,
  TrendingUp,
  Megaphone,
  UserPlus,
  ClipboardList,
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface DashboardStats {
  totalStudents: number;
  totalTeachers: number;
  totalClasses: number;
  totalNotices: number;
}

export default function Dashboard() {
  const { user, role, loading: authLoading, signOut } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const { toast } = useToast();

  const [stats, setStats] = useState<DashboardStats>({
    totalStudents: 0,
    totalTeachers: 0,
    totalClasses: 0,
    totalNotices: 0,
  });
  const [statsLoading, setStatsLoading] = useState(true);

  useEffect(() => {
    if (authLoading) return;
    if (!user) {
      navigate("/login");
      return;
    }
    if (role === "admin") {
      fetchDashboardStats();
    } else {
      setStatsLoading(false);
    }
  }, [authLoading, user, role, navigate]);

  const fetchDashboardStats = async () => {
    try {
      setStatsLoading(true);
      const data = await api.get<DashboardStats>("/api/dashboard/stats");
      setStats(data);
    } catch (error: any) {
      console.error("Error fetching stats:", error);
      toast({
        title: "Error",
        description: "Failed to load dashboard statistics.",
        variant: "destructive",
      });
    } finally {
      setStatsLoading(false);
    }
  };

  if (authLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="animate-spin w-8 h-8 text-primary" />
      </div>
    );
  }

  if (!user) return null;

  const handleSignOut = async () => {
    await signOut();
    navigate("/");
  };

  const sidebarLinks = [
    { icon: LayoutDashboard, label: "Overview", path: "/dashboard" },
    { icon: Users, label: "Students", path: "/dashboard/students" },
    { icon: GraduationCap, label: "Teachers", path: "/dashboard/teachers" },
    { icon: School, label: "Classes & Subjects", path: "/dashboard/classes" },
    { icon: Bell, label: "Notices", path: "/dashboard/notices" },
  ];

  const isActive = (path: string) => location.pathname === path;

  const statCards = [
    {
      icon: Users,
      label: "Total Students",
      value: stats.totalStudents,
      color: "bg-blue-500",
      lightColor: "bg-blue-50 text-blue-600",
      path: "/dashboard/students",
    },
    {
      icon: GraduationCap,
      label: "Total Teachers",
      value: stats.totalTeachers,
      color: "bg-emerald-500",
      lightColor: "bg-emerald-50 text-emerald-600",
      path: "/dashboard/teachers",
    },
    {
      icon: BookOpen,
      label: "Total Classes",
      value: stats.totalClasses,
      color: "bg-amber-500",
      lightColor: "bg-amber-50 text-amber-600",
      path: "/dashboard/classes",
    },
    {
      icon: Megaphone,
      label: "Total Notices",
      value: stats.totalNotices,
      color: "bg-rose-500",
      lightColor: "bg-rose-50 text-rose-600",
      path: "/dashboard/notices",
    },
  ];

  const quickActions = [
    {
      icon: UserPlus,
      label: "Add Student",
      description: "Enroll a new student",
      path: "/dashboard/students/add",
      color: "text-blue-600",
    },
    {
      icon: UserPlus,
      label: "Add Teacher",
      description: "Register a new teacher",
      path: "/dashboard/teachers/add",
      color: "text-emerald-600",
    },
    {
      icon: PlusCircle,
      label: "Create Notice",
      description: "Post an announcement",
      path: "/dashboard/notices/create",
      color: "text-rose-600",
    },
    {
      icon: School,
      label: "Manage Classes",
      description: "Add classes & subjects",
      path: "/dashboard/classes",
      color: "text-amber-600",
    },
  ];

  return (
    <div className="min-h-screen bg-slate-50 flex">
      {/* Sidebar */}
      <aside className="hidden lg:flex flex-col w-64 bg-white border-r border-slate-200 fixed h-full z-20">
        {/* Logo */}
        <div className="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
          <div className="w-10 h-10 rounded-xl bg-primary flex items-center justify-center shadow-sm">
            <GraduationCap className="w-6 h-6 text-primary-foreground" />
          </div>
          <div>
            <h1 className="font-bold text-base text-slate-900 leading-tight">
              Rose Valley
            </h1>
            <p className="text-[11px] text-slate-500 font-medium">
              Academy Admin
            </p>
          </div>
        </div>

        {/* Nav Links */}
        <nav className="flex-1 px-3 py-4 space-y-1">
          {sidebarLinks.map((link) => (
            <Link
              key={link.path}
              to={link.path}
              className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all ${
                isActive(link.path)
                  ? "bg-primary/10 text-primary shadow-sm"
                  : "text-slate-600 hover:bg-slate-100 hover:text-slate-900"
              }`}
            >
              <link.icon className="w-5 h-5" />
              {link.label}
            </Link>
          ))}
        </nav>

        {/* User section at bottom */}
        <div className="border-t border-slate-100 px-4 py-4">
          <div className="flex items-center gap-3 mb-3">
            <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center">
              <span className="text-sm font-bold text-primary">
                {user.email?.charAt(0).toUpperCase()}
              </span>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-slate-900 truncate">
                {user.email}
              </p>
              <p className="text-[11px] text-slate-500 uppercase font-semibold tracking-wider">
                {role}
              </p>
            </div>
          </div>
          <Button
            variant="outline"
            size="sm"
            onClick={handleSignOut}
            className="w-full gap-2 border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
          >
            <LogOut className="w-4 h-4" />
            Sign Out
          </Button>
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 lg:ml-64">
        {/* Top bar (mobile only) */}
        <header className="lg:hidden border-b border-slate-200 bg-white px-4 py-3 flex items-center justify-between sticky top-0 z-10 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-primary flex items-center justify-center">
              <GraduationCap className="w-5 h-5 text-primary-foreground" />
            </div>
            <span className="font-bold text-slate-900">RVA Admin</span>
          </div>
          <div className="flex items-center gap-2">
            {/* Mobile nav links */}
            <div className="flex gap-1">
              {sidebarLinks.map((link) => (
                <Link
                  key={link.path}
                  to={link.path}
                  className={`p-2 rounded-lg transition-colors ${
                    isActive(link.path)
                      ? "bg-primary/10 text-primary"
                      : "text-slate-500 hover:bg-slate-100"
                  }`}
                >
                  <link.icon className="w-5 h-5" />
                </Link>
              ))}
            </div>
            <Button
              variant="ghost"
              size="icon"
              onClick={handleSignOut}
              className="text-red-600 hover:bg-red-50"
            >
              <LogOut className="w-5 h-5" />
            </Button>
          </div>
        </header>

        <main className="p-4 md:p-6 lg:p-8 max-w-7xl mx-auto">
          {/* Page Header */}
          <div className="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
              <div className="flex items-center gap-2 mb-1">
                <TrendingUp className="w-5 h-5 text-primary" />
                <span className="text-sm font-semibold uppercase tracking-wider text-primary">
                  Dashboard
                </span>
              </div>
              <h2 className="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">
                Welcome back, Admin!
              </h2>
              <p className="text-slate-500 mt-1">
                Here's what's happening at Rose Valley Academy today.
              </p>
            </div>
            <Button
              onClick={fetchDashboardStats}
              variant="outline"
              size="sm"
              className="gap-2 border-slate-300 hover:bg-white self-start"
              disabled={statsLoading}
            >
              <RefreshCw
                className={`w-4 h-4 ${statsLoading ? "animate-spin" : ""}`}
              />
              Refresh
            </Button>
          </div>

          {/* Stats Grid */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {statCards.map((stat, i) => (
              <Link key={i} to={stat.path}>
                <Card className="hover:shadow-md transition-all cursor-pointer group border-slate-200 bg-white h-full">
                  <CardContent className="p-5">
                    <div className="flex items-center justify-between mb-3">
                      <div
                        className={`w-10 h-10 rounded-xl ${stat.lightColor} flex items-center justify-center`}
                      >
                        <stat.icon className="w-5 h-5" />
                      </div>
                      <ChevronRight className="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" />
                    </div>
                    <p className="text-2xl font-bold text-slate-900">
                      {statsLoading ? (
                        <Loader2 className="w-5 h-5 animate-spin text-slate-400" />
                      ) : (
                        stat.value.toLocaleString()
                      )}
                    </p>
                    <p className="text-sm text-slate-500 mt-0.5">
                      {stat.label}
                    </p>
                  </CardContent>
                </Card>
              </Link>
            ))}
          </div>

          {/* Bottom Grid - Quick Actions + Recent Activity */}
          <div className="grid md:grid-cols-2 gap-6">
            {/* Quick Actions */}
            <Card className="border-slate-200 bg-white shadow-sm">
              <CardHeader className="border-b border-slate-100 pb-4">
                <CardTitle className="flex items-center gap-2 text-lg text-slate-900">
                  <ClipboardList className="w-5 h-5 text-primary" />
                  Quick Actions
                </CardTitle>
              </CardHeader>
              <CardContent className="grid grid-cols-2 gap-3 pt-5">
                {quickActions.map((action, i) => (
                  <Link key={i} to={action.path}>
                    <div className="border border-slate-200 rounded-xl p-4 hover:border-primary/40 hover:shadow-sm transition-all cursor-pointer group h-full">
                      <action.icon
                        className={`w-6 h-6 ${action.color} mb-2 group-hover:scale-110 transition-transform`}
                      />
                      <p className="font-semibold text-sm text-slate-900">
                        {action.label}
                      </p>
                      <p className="text-xs text-slate-500 mt-0.5">
                        {action.description}
                      </p>
                    </div>
                  </Link>
                ))}
              </CardContent>
            </Card>

            {/* Recent Activity / Notices Summary */}
            <Card className="border-slate-200 bg-white shadow-sm">
              <CardHeader className="border-b border-slate-100 pb-4 flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2 text-lg text-slate-900">
                  <Bell className="w-5 h-5 text-rose-500" />
                  Notices Summary
                </CardTitle>
                <Button variant="ghost" size="sm" asChild className="text-xs">
                  <Link to="/dashboard/notices">View All</Link>
                </Button>
              </CardHeader>
              <CardContent className="pt-5">
                {statsLoading ? (
                  <div className="flex justify-center py-12">
                    <Loader2 className="w-8 h-8 animate-spin text-slate-400" />
                  </div>
                ) : stats.totalNotices === 0 ? (
                  <div className="text-center py-10 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                    <Megaphone className="w-10 h-10 text-slate-300 mx-auto mb-3" />
                    <p className="text-slate-600 font-medium">
                      No notices published yet
                    </p>
                    <p className="text-sm text-slate-400 mt-1 mb-4">
                      Create your first announcement for the school.
                    </p>
                    <Button size="sm" asChild>
                      <Link to="/dashboard/notices/create" className="gap-2">
                        <PlusCircle className="w-4 h-4" /> Create Notice
                      </Link>
                    </Button>
                  </div>
                ) : (
                  <div className="space-y-4">
                    <div className="flex items-center gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100">
                      <div className="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center">
                        <Megaphone className="w-6 h-6 text-rose-500" />
                      </div>
                      <div className="flex-1">
                        <p className="text-2xl font-bold text-slate-900">
                          {stats.totalNotices}
                        </p>
                        <p className="text-sm text-slate-500">
                          Published notices in system
                        </p>
                      </div>
                    </div>
                    <div className="flex gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        className="flex-1"
                        asChild
                      >
                        <Link to="/dashboard/notices">Manage All</Link>
                      </Button>
                      <Button size="sm" className="flex-1 gap-2" asChild>
                        <Link to="/dashboard/notices/create">
                          <PlusCircle className="w-4 h-4" /> New Notice
                        </Link>
                      </Button>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </main>
      </div>
    </div>
  );
}
