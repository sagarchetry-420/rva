import { useAuth } from "@/hooks/useAuth";
import { useNavigate, Link } from "react-router-dom";
import { useEffect, useState } from "react";
import { supabase } from "@/integrations/supabase/client";
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
  LayoutDashboard
} from "lucide-react";
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
    console.log("Dashboard State -> authLoading:", authLoading, "| user:", user?.email, "| role:", role);

    if (authLoading) return;

    if (!user) {
      navigate("/login");
      return;
    }
    
    if (role === 'admin') {
      fetchDashboardStats();
    } else {
      setStatsLoading(false);
    }
  }, [authLoading, user, role, navigate]);

  const fetchDashboardStats = async () => {
    try {
      setStatsLoading(true);
      console.log("Fetching stats... Current role is:", role);

      const [studentsRes, teachersRes, classesRes, noticesRes] = await Promise.all([
        supabase.from('students').select('id', { count: 'exact', head: true }),
        supabase.from('teachers').select('id', { count: 'exact', head: true }),
        supabase.from('classes').select('id', { count: 'exact', head: true }),
        supabase.from('notices').select('id', { count: 'exact', head: true }),
      ]);

      // Explicitly check for Supabase errors
      if (studentsRes.error) throw new Error(`Students Error: ${studentsRes.error.message}`);
      if (teachersRes.error) throw new Error(`Teachers Error: ${teachersRes.error.message}`);
      if (classesRes.error) throw new Error(`Classes Error: ${classesRes.error.message}`);
      if (noticesRes.error) throw new Error(`Notices Error: ${noticesRes.error.message}`);

      console.log("Fetch successful!", {
        students: studentsRes.count,
        teachers: teachersRes.count,
        classes: classesRes.count,
        notices: noticesRes.count
      });

      setStats({
        students: studentsRes.count || 0,
        teachers: teachersRes.count || 0,
        classes: classesRes.count || 0,
        notices: noticesRes.count || 0,
      });
    } catch (error: any) {
      console.error("Dashboard Stats Blocked:", error.message);
      toast({
        title: "Stats Error",
        description: error.message,
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
      <header className="border-b border-border bg-card px-6 py-4 flex items-center justify-between shadow-sm sticky top-0 z-10">
        <div className="flex items-center gap-3">
          <Link to="/dashboard" className="flex items-center gap-3 hover:opacity-80 transition-opacity">
            <div className="w-10 h-10 rounded-full bg-primary flex items-center justify-center shadow-inner">
              <GraduationCap className="w-6 h-6 text-primary-foreground" />
            </div>
            <div>
              <h1 className="font-bold text-lg text-card-foreground leading-tight">Rose Valley Academy</h1>
              <p className="text-xs text-muted-foreground capitalize font-medium">{role || "User"} Portal</p>
            </div>
          </Link>
        </div>
        <div className="flex items-center gap-4">
          <div className="hidden sm:flex flex-col items-end">
            <span className="text-sm font-medium text-foreground">{user.email}</span>
            <span className="text-[10px] bg-primary/10 text-primary px-2 py-0.5 rounded-full uppercase font-bold tracking-wider">Active</span>
          </div>
          <Button 
            variant="outline" 
            size="sm" 
            onClick={handleSignOut} 
            className="gap-2 border-destructive/20 text-destructive hover:bg-destructive hover:text-white transition-all shadow-sm"
          >
            <LogOut className="w-4 h-4" /> <span className="hidden xs:inline">Sign Out</span>
          </Button>
        </div>
      </header>

      <main className="container mx-auto px-4 py-8 max-w-7xl">
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
          <div>
            <div className="flex items-center gap-2 mb-1">
              <LayoutDashboard className="w-5 h-5 text-primary" />
              <span className="text-sm font-semibold uppercase tracking-wider text-primary">Overview</span>
            </div>
            <h2 className="text-3xl font-bold text-foreground tracking-tight">
              Welcome{role ? `, ${role.charAt(0).toUpperCase() + role.slice(1)}` : ""}!
            </h2>
            <p className="text-muted-foreground">Quick summary of school operations and active notices.</p>
          </div>
          {role === 'admin' && (
            <Button 
              onClick={fetchDashboardStats} 
              variant="outline" 
              size="sm" 
              className="gap-2 hover:bg-primary/5 border-primary/20"
              disabled={statsLoading}
            >
              <RefreshCw className={`w-4 h-4 ${statsLoading ? "animate-spin" : ""}`} />
              Refresh Data
            </Button>
          )}
        </div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          {dashboardStats.map((stat, i) => (
            <Link key={i} to={stat.path}>
              <Card className="hover:shadow-md hover:border-primary/50 transition-all cursor-pointer group bg-card h-full">
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">{stat.label}</CardTitle>
                  <stat.icon className={`w-5 h-5 ${stat.color} group-hover:scale-110 transition-transform`} />
                </CardHeader>
                <CardContent>
                  <p className="text-3xl font-bold text-card-foreground">
                    {statsLoading ? (
                      <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                    ) : (
                      stat.value.toLocaleString()
                    )}
                  </p>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>

        <div className="grid md:grid-cols-3 gap-6">
          <Card className="md:col-span-2 shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between border-b bg-muted/20 pb-4">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Bell className="w-5 h-5 text-rose-500" /> Recent Activity
              </CardTitle>
              <Button variant="ghost" size="sm" asChild>
                <Link to="/dashboard/notices" className="text-xs">View All Board</Link>
              </Button>
            </CardHeader>
            <CardContent className="pt-6">
              {statsLoading ? (
                <div className="flex justify-center py-12">
                   <Loader2 className="w-8 h-8 animate-spin text-muted-foreground" />
                </div>
              ) : stats.notices === 0 ? (
                <div className="text-center py-12 border-2 border-dashed rounded-lg bg-muted/5">
                  <p className="text-muted-foreground mb-4">No recent notices found in the system.</p>
                  <Button variant="outline" className="text-primary gap-2" asChild>
                    <Link to="/dashboard/notices"><PlusCircle className="w-4 h-4" /> Create First Notice</Link>
                  </Button>
                </div>
              ) : (
                <div className="space-y-4">
<<<<<<< HEAD
                   <div className="flex items-center justify-between p-4 bg-muted/30 rounded-lg border border-border">
                     <div>
                        <p className="font-semibold text-foreground">Active Notice Board</p>
                        <p className="text-sm text-muted-foreground">You currently have {stats.notices} live announcements.</p>
                     </div>
                     <div className="flex gap-2">
                       <Button variant="default" size="sm" asChild>
                          <Link to="/dashboard/notices">Manage</Link>
                       </Button>
                     </div>
=======
                   <p className="text-sm text-muted-foreground">You have <span className="font-bold text-foreground">{stats.notices}</span> published notices in the system.</p>
                   <div className="flex gap-2">
                     <Button variant="outline" size="sm" asChild>
                        <Link to="/dashboard/notices">Manage Notices</Link>
                     </Button>
                     <Button variant="default" size="sm" asChild>
                        <Link to="/dashboard/notices/create">Post New Notice</Link>
                     </Button>
>>>>>>> testing
                   </div>
                </div>
              )}
            </CardContent>
          </Card>

          <Card className="shadow-sm">
            <CardHeader className="border-b bg-muted/20 pb-4">
              <CardTitle className="text-lg">Admin Quick Actions</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-3 pt-6">
              <Button className="w-full justify-start gap-3 h-11" variant="outline" asChild>
                <Link to="/dashboard/students/add">
                  <PlusCircle className="w-4 h-4 text-blue-500" /> Enroll New Student
                </Link>
              </Button>
              <Button className="w-full justify-start gap-3 h-11" variant="outline" asChild>
                <Link to="/dashboard/teachers/add">
                  <PlusCircle className="w-4 h-4 text-emerald-500" /> Register New Teacher
                </Link>
              </Button>
              <Button className="w-full justify-start gap-3 h-11" variant="outline" asChild>
                <Link to="/dashboard/classes">
                  <School className="w-4 h-4 text-amber-500" /> Manage Structure
                </Link>
              </Button>
              <Button className="w-full justify-start gap-3 h-11 border-primary/20 bg-primary/5 hover:bg-primary/10" variant="ghost" asChild>
                <Link to="/dashboard/levels">
                  <PlusCircle className="w-4 h-4 text-indigo-500" /> Manage Levels
                </Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  );
}