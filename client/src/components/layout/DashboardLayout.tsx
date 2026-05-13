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
        <Loader2 className="animate-spin w-8 h-8 text-orange-600" />
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
    { icon: Bell, label: "Notice", path: "/dashboard/notices" },
  ];

  const isActive = (path: string) => {
    if (path === "/dashboard") {
      return location.pathname === path;
    }
    return location.pathname.startsWith(path);
  };

  return (
    <div className="min-h-screen bg-[#F8FAFC] flex font-sans">
      {/* Mobile Sidebar Overlay */}
      {isMobileMenuOpen && (
        <div
          className="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 lg:hidden transition-opacity"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-[#F77A46] via-[#F77A46] to-[#E86A30] transform transition-all duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-2xl lg:shadow-none ${
          isMobileMenuOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        {/* Logo */}
        <div className="flex items-center justify-between gap-3 px-6 py-6 border-b border-white/20">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-white/25 shadow-inner flex items-center justify-center backdrop-blur-md overflow-hidden">
              <img src="/logo/logo.png" alt="RVA" className="w-8 h-8 object-contain" />
            </div>
            <h1 className="font-bold text-xl text-white tracking-tight">Rose Valley Academy</h1>
          </div>
          {/* Mobile Close Button */}
          <Button
            variant="ghost"
            size="icon"
            className="lg:hidden text-white/70 hover:text-white hover:bg-white/20"
            onClick={() => setIsMobileMenuOpen(false)}
          >
            <X className="w-5 h-5" />
          </Button>
        </div>

        {/* Nav Links */}
        <nav className="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent">
          {sidebarLinks.map((link) => (
            <Link
              key={link.path}
              to={link.path}
              className={`flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 group ${
                isActive(link.path)
                  ? "bg-white/20 text-white shadow-lg scale-[1.02]"
                  : "text-white/70 hover:bg-white/15 hover:text-white"
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
        <div className="px-4 py-5 border-t border-white/20">
          <div className="flex items-center gap-3 mb-4 px-2">
            <div className="w-10 h-10 rounded-full bg-white/25 flex items-center justify-center shadow-inner border border-white/30 backdrop-blur-sm shrink-0">
              <span className="text-sm font-bold text-white">
                {user.email?.charAt(0).toUpperCase()}
              </span>
            </div>
            <div className="min-w-0 flex-1">
              <p className="text-sm font-semibold text-white truncate">{role === 'admin' ? 'Administrator' : role || 'User'}</p>
              <p className="text-xs text-white/60 truncate">{user.email}</p>
            </div>
          </div>
          <button
            onClick={handleSignOut}
            className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-white/70 hover:bg-white/15 hover:text-white transition-all duration-200 group"
          >
            <LogOut className="w-5 h-5 group-hover:scale-110 transition-transform" />
            Sign Out
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col min-h-screen lg:ml-72 transition-all duration-300">
        {/* Mobile Menu Toggle — floating button */}
        <button
          className="lg:hidden fixed top-4 left-4 z-30 w-11 h-11 rounded-xl bg-gradient-to-br from-[#F77A46] to-[#E86A30] text-white shadow-lg flex items-center justify-center hover:shadow-xl hover:scale-105 transition-all"
          onClick={() => setIsMobileMenuOpen(true)}
        >
          <Menu className="w-5 h-5" />
        </button>

        {/* Page Content - Outlet renders child routes */}
        <main className="p-4 sm:p-6 lg:p-8 overflow-x-hidden flex-1">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
