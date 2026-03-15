import { useAuth } from "@/hooks/useAuth";
import { useNavigate, Link } from "react-router-dom";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { GraduationCap, Users, BookOpen, Bell, LogOut, Loader2, PlusCircle, School } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function Dashboard() {
  const { user, role, loading: authLoading, signOut } = useAuth();
  const navigate = useNavigate();
  const { toast } = useToast();
  
  const [stats, setStats] = useState({
    students: 0,
    teachers: 0,
    classes: 0,
    notices: 0,
  });
  const [statsLoading, setStatsLoading] = useState(true);

  useEffect(() => {
    if (!authLoading && !user) {
      navigate("/login");
    }
    
    if (user && role === 'admin') {
      fetchDashboardStats();
    }
  }, [authLoading, user, role, navigate]);

  const fetchDashboardStats = async () => {
    try {
      setStatsLoading(true);
      const data = await api.get<{
        totalStudents: number;
        totalTeachers: number;
        totalClasses: number;
        totalNotices: number;
      }>('/api/dashboard/stats');

      setStats({
        students: data.totalStudents,
        teachers: data.totalTeachers,
        classes: data.totalClasses,
        notices: data.totalNotices,
      });
    } catch (error) {
      console.error("Error fetching stats:", error);
      toast({
        title: "Error",
        description: "Failed to load dashboard statistics.",
        variant: "destructive"
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

  const dashboardStats = [
    { icon: Users, label: "Students", value: stats.students, color: "text-blue-500", path: "/dashboard/students" },
    { icon: GraduationCap, label: "Teachers", value: stats.teachers, color: "text-emerald-500", path: "/dashboard/teachers" },
    { icon: BookOpen, label: "Classes", value: stats.classes, color: "text-amber-500", path: "/dashboard/classes" },
    { icon: Bell, label: "Notices", value: stats.notices, color: "text-rose-500", path: "/dashboard/notices" },
  ];

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b border-border bg-card px-6 py-4 flex items-center justify-between shadow-sm">
        <div className="flex items-center gap-3">
          <Link to="/dashboard" className="flex items-center gap-3 hover:opacity-80 transition-opacity">
            <div className="w-10 h-10 rounded-full bg-primary flex items-center justify-center">
              <GraduationCap className="w-6 h-6 text-primary-foreground" />
            </div>
            <div>
              <h1 className="font-display font-bold text-lg text-card-foreground leading-tight">Rose Valley Academy</h1>
              <p className="text-xs text-muted-foreground capitalize font-medium">{role || "User"} Portal</p>
            </div>
          </Link>
        </div>
        <div className="flex items-center gap-4">
          <div className="hidden sm:flex flex-col items-end">
            <span className="text-sm font-medium text-foreground">{user.email}</span>
            <span className="text-[10px] bg-primary/10 text-primary px-2 py-0.5 rounded-full uppercase font-bold tracking-wider">Active</span>
          </div>
          <Button variant="outline" size="sm" onClick={handleSignOut} className="gap-2 border-destructive/20 text-destructive hover:bg-destructive hover:text-white transition-all">
            <LogOut className="w-4 h-4" /> <span className="hidden xs:inline">Sign Out</span>
          </Button>
        </div>
      </header>

      <main className="container mx-auto px-4 py-8 max-w-7xl">
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
          <div>
            <h2 className="font-display text-3xl font-bold text-foreground">
              Welcome{role ? `, ${role.charAt(0).toUpperCase() + role.slice(1)}` : ""}!
            </h2>
            <p className="text-muted-foreground mt-1">Quick summary of Rose Valley Academy status.</p>
          </div>
          <Button onClick={fetchDashboardStats} variant="outline" size="sm" className="gap-2">
            <Loader2 className={`w-4 h-4 ${statsLoading ? "animate-spin" : ""}`} />
            Refresh Data
          </Button>
        </div>

        {/* Stats Cards */}
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {dashboardStats.map((stat, i) => (
            <Link key={i} to={stat.path}>
              <Card className="hover:shadow-md hover:border-primary/50 transition-all cursor-pointer group">
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">{stat.label}</CardTitle>
                  <stat.icon className={`w-5 h-5 ${stat.color} group-hover:scale-110 transition-transform`} />
                </CardHeader>
                <CardContent>
                  <p className="text-3xl font-display font-bold text-card-foreground">
                    {statsLoading ? <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" /> : stat.value}
                  </p>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>

        <div className="grid md:grid-cols-3 gap-6">
          {/* Recent Notices Section */}
          <Card className="md:col-span-2">
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="font-display flex items-center gap-2">
                <Bell className="w-5 h-5 text-primary" /> Recent Activity
              </CardTitle>
              <Button variant="ghost" size="sm" asChild>
                <Link to="/dashboard/notices" className="text-xs">View All</Link>
              </Button>
            </CardHeader>
            <CardContent>
              {stats.notices === 0 ? (
                <div className="text-center py-12 border-2 border-dashed rounded-lg">
                  <p className="text-muted-foreground">No recent notices found.</p>
                  <Button variant="link" className="text-primary mt-2" asChild>
                    <Link to="/dashboard/notices">Create your first notice</Link>
                  </Button>
                </div>
              ) : (
                <div className="space-y-4">
                   <p className="text-sm text-muted-foreground">You have <span className="font-bold text-foreground">{stats.notices}</span> published notices in the system.</p>
                   <div className="flex gap-2">
                     <Button variant="outline" size="sm" asChild>
                        <Link to="/dashboard/notices">Manage Notices</Link>
                     </Button>
                     <Button variant="default" size="sm" asChild>
                        <Link to="/dashboard/notices/create">Post New Notice</Link>
                     </Button>
                   </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Quick Actions Section */}
          <Card>
            <CardHeader>
              <CardTitle className="font-display">Administrative Actions</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-3">
              <Button className="w-full justify-start gap-3" variant="outline" asChild>
                <Link to="/dashboard/students/add">
                  <PlusCircle className="w-4 h-4 text-blue-500" /> Enroll New Student
                </Link>
              </Button>
              <Button className="w-full justify-start gap-3" variant="outline" asChild>
                <Link to="/dashboard/teachers/add">
                  <PlusCircle className="w-4 h-4 text-emerald-500" /> Register New Teacher
                </Link>
              </Button>
              <Button className="w-full justify-start gap-3" variant="outline" asChild>
                <Link to="/dashboard/classes">
                  <School className="w-4 h-4 text-amber-500" /> Manage School Structure
                </Link>
              </Button>
              <Button className="w-full justify-start gap-3 border-primary/20 bg-primary/5 hover:bg-primary/10" variant="ghost" asChild>
                <Link to="/dashboard/levels">
                  <PlusCircle className="w-4 h-4 text-indigo-500" /> Configure Levels
                </Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  );
}