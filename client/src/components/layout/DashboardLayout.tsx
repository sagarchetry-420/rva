import { useAuth } from "@/hooks/useAuth";
import { useNavigate, Link, Outlet, useLocation } from "react-router-dom";
import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import {
  GraduationCap,
  Users,
  Bell,
  LogOut,
  Loader2,
  School,
  LayoutDashboard,
  Calendar,
  Award,
  FileText,
  Trophy,
  CheckSquare,
  Menu,
  X,
} from "lucide-react";

export default function DashboardLayout() {
  const { user, role, loading: authLoading, signOut } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  useEffect(() => {
    if (authLoading) return;
    if (!user) {
      navigate("/login");
    }
  }, [authLoading, user, navigate]);

  // Close mobile menu on route change
  useEffect(() => {
    setIsMobileMenuOpen(false);
  }, [location.pathname]);

  if (authLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="animate-spin w-8 h-8 text-orange-500" />
      </div>
    );
  }

  if (!user) return null;

  const handleSignOut = async () => {
    await signOut();
    navigate("/");
  };

  const sidebarLinks = [
    { icon: LayoutDashboard, label: "Dashboard", path: "/dashboard" },
    { icon: Users, label: "Students", path: "/dashboard/students" },
    { icon: GraduationCap, label: "Teachers", path: "/dashboard/teachers" },
    { icon: School, label: "Class", path: "/dashboard/classes" },
    { icon: FileText, label: "Subject", path: "/dashboard/subjects" },
    { icon: Calendar, label: "Routine", path: "/dashboard/routines" },
    { icon: CheckSquare, label: "Attendance", path: "/dashboard/attendance" },
    { icon: Award, label: "Exam", path: "/dashboard/exams" },
    { icon: Trophy, label: "Results", path: "/dashboard/results" },
    { icon: Bell, label: "Notice", path: "/dashboard/notices" },
  ];

  const isActive = (path: string) => {
    if (path === "/dashboard") {
      return location.pathname === path;
    }
    return location.pathname.startsWith(path);
  };

  return (
    <div className="admin-dashboard-theme min-h-screen bg-white flex font-sans text-slate-900">
      {/* Mobile Sidebar Overlay */}
      {isMobileMenuOpen && (
        <div
          className="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 lg:hidden transition-opacity"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 transform transition-all duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-2xl lg:shadow-none print:hidden ${
          isMobileMenuOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        {/* Logo */}
        <div className="flex items-center justify-between gap-3 px-6 py-6 border-b border-white/10">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-white/10 border border-white/10 flex items-center justify-center overflow-hidden">
              <img src="/logo/logo.png" alt="RVA" className="w-8 h-8 object-contain" />
            </div>
            <div>
              <h1 className="font-bold text-lg text-white tracking-tight">Rose Valley Academy</h1>
              <p className="text-[10px] text-slate-400 uppercase tracking-wider font-medium">Admin Portal</p>
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

        {/* Nav Links */}
        <nav className="flex-1 px-3 py-6 space-y-1 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-600 scrollbar-track-transparent">
          {sidebarLinks.map((link) => (
            <Link
              key={link.path}
              to={link.path}
              className={`flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 group ${
                isActive(link.path)
                  ? "bg-white text-slate-900 shadow-lg scale-[1.02]"
                  : "text-white/70 hover:bg-white/10 hover:text-white"
              }`}
            >
              <div className="flex items-center gap-3.5">
                <link.icon
                  className={`w-5 h-5 transition-transform duration-200 ${
                    isActive(link.path) ? "scale-110" : "group-hover:scale-110"
                  }`}
                />
                {link.label}
              </div>
            </Link>
          ))}
        </nav>

        {/* Sidebar Footer — User Profile & Logout */}
        <div className="px-3 py-5 border-t border-white/10">
          <div className="flex items-center gap-3 mb-4 px-2">
            <div className="w-10 h-10 rounded-full bg-white/15 flex items-center justify-center border border-white/10 shrink-0">
              <span className="text-sm font-bold text-white">
                {(role || 'U').charAt(0).toUpperCase()}
              </span>
            </div>
            <div className="min-w-0 flex-1">
              <p className="text-sm font-semibold text-white truncate">{role === 'admin' ? 'Administrator' : role || 'User'}</p>
              <p className="text-xs text-slate-400 truncate text-capitalize">{role || 'Account'}</p>
            </div>
          </div>
          <button
            onClick={handleSignOut}
            className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-white/70 hover:bg-red-500/20 hover:text-white border border-white/10 hover:border-red-500/30 transition-all duration-200 group"
          >
            <LogOut className="w-5 h-5 group-hover:scale-110 transition-transform" />
            Sign Out
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col min-h-screen lg:ml-72 print:ml-0 transition-all duration-300 bg-white">
        {/* Mobile Menu Toggle — floating button */}
        <button
          className="lg:hidden print:hidden fixed top-4 left-4 z-30 w-11 h-11 rounded-xl bg-slate-900 text-white border border-slate-700 shadow-lg flex items-center justify-center hover:bg-slate-800 transition-all"
          onClick={() => setIsMobileMenuOpen(true)}
        >
          <Menu className="w-5 h-5" />
        </button>

        {/* Page Content - Outlet renders child routes */}
        <main className="admin-dashboard-content p-4 sm:p-6 lg:p-8 overflow-x-hidden flex-1">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
