import { useAuth } from "@/hooks/useAuth";
import { useNavigate, Link, Outlet, useLocation } from "react-router-dom";
import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import {
  GraduationCap,
  Users,
  BookOpen,
  Bell,
  LogOut,
  Loader2,
  School,
  RefreshCw,
  LayoutDashboard,
  ChevronDown,
  ClipboardList,
  Search,
  Calendar,
  Award,
  FileText,
  CheckSquare,
  Bus,
  Home,
  Menu,
  X,
} from "lucide-react";

interface DashboardLayoutProps {
  onRefresh?: () => void;
  isLoading?: boolean;
}

export default function DashboardLayout({ onRefresh, isLoading = false }: DashboardLayoutProps) {
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
        <Loader2 className="animate-spin w-8 h-8 text-violet-600" />
      </div>
    );
  }

  if (!user) return null;

  const handleSignOut = async () => {
    await signOut();
    navigate("/");
  };

  const sidebarLinks = [
    { icon: LayoutDashboard, label: "Dashboard", path: "/dashboard", expandable: true },
    { icon: Users, label: "Students", path: "/dashboard/students", expandable: true },
    { icon: GraduationCap, label: "Teachers", path: "/dashboard/teachers", expandable: true },
    { icon: BookOpen, label: "Library", path: "/dashboard/library", expandable: false },
    { icon: ClipboardList, label: "Account", path: "/dashboard/account", expandable: true },
    { icon: School, label: "Class", path: "/dashboard/classes", expandable: true },
    { icon: FileText, label: "Subject", path: "/dashboard/subjects", expandable: true },
    { icon: Calendar, label: "Routine", path: "/dashboard/routines", expandable: false },
    { icon: CheckSquare, label: "Attendance", path: "/dashboard/attendance", expandable: false },
    { icon: Award, label: "Exam", path: "/dashboard/exams", expandable: true },
    { icon: Bell, label: "Notice", path: "/dashboard/notices", expandable: false },
    { icon: Bus, label: "Transport", path: "/dashboard/transport", expandable: false },
    { icon: Home, label: "Hostel", path: "/dashboard/hostel", expandable: false },
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
        className={`fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-violet-700 via-violet-800 to-indigo-950 transform transition-all duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-2xl lg:shadow-none ${
          isMobileMenuOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        {/* Logo */}
        <div className="flex items-center justify-between gap-3 px-6 py-6 border-b border-white/10">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-white/20 shadow-inner flex items-center justify-center backdrop-blur-md">
              <GraduationCap className="w-6 h-6 text-white" />
            </div>
            <h1 className="font-bold text-xl text-white tracking-tight">Rose Valley</h1>
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
        <nav className="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto scrollbar-thin scrollbar-thumb-white/10 scrollbar-track-transparent">
          {sidebarLinks.map((link) => (
            <Link
              key={link.path}
              to={link.path}
              className={`flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 group ${
                isActive(link.path)
                  ? "bg-white text-violet-700 shadow-lg scale-[1.02]"
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
              {link.expandable && (
                <ChevronDown
                  className={`w-4 h-4 transition-transform ${
                    isActive(link.path) ? "text-violet-400" : "text-white/40 group-hover:text-white/70"
                  }`}
                />
              )}
            </Link>
          ))}
        </nav>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col min-h-screen lg:ml-72 transition-all duration-300">
        {/* Top Header */}
        <header className="bg-white/80 backdrop-blur-md border-b border-gray-100/80 px-4 sm:px-6 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
          <div className="flex items-center gap-2 sm:gap-4 flex-1">
            {/* Mobile Menu Toggle */}
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden text-gray-500 hover:bg-violet-50 hover:text-violet-600"
              onClick={() => setIsMobileMenuOpen(true)}
            >
              <Menu className="w-6 h-6" />
            </Button>

            {/* Search */}
            <div className="relative max-w-md flex-1 group hidden sm:block">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-violet-500 transition-colors" />
              <input
                type="text"
                placeholder="Search anything here..."
                className="w-full pl-10 pr-4 py-2.5 bg-gray-50/80 hover:bg-gray-100/80 border border-transparent rounded-xl text-sm transition-all focus:outline-none focus:bg-white focus:border-violet-200 focus:ring-4 focus:ring-violet-500/10"
              />
            </div>
            {/* Mobile Search Icon Only */}
            <Button variant="ghost" size="icon" className="sm:hidden text-gray-500 ml-auto">
              <Search className="w-5 h-5" />
            </Button>
          </div>

          <div className="flex items-center gap-2 sm:gap-4 ml-2">
            {onRefresh && (
              <Button
                onClick={onRefresh}
                variant="ghost"
                size="icon"
                disabled={isLoading}
                className="text-gray-400 hover:text-violet-600 hover:bg-violet-50 hidden sm:flex transition-colors"
              >
                <RefreshCw className={`w-5 h-5 ${isLoading ? "animate-spin" : ""}`} />
              </Button>
            )}

            <button className="p-2 sm:p-2.5 hover:bg-violet-50 rounded-xl transition-all relative group">
              <Bell className="w-5 h-5 text-gray-500 group-hover:text-violet-600" />
            </button>

            <div className="h-8 w-px bg-gray-200 hidden sm:block mx-1"></div>

            <div className="flex items-center gap-3">
              <div className="hidden md:flex flex-col items-end">
                <p className="text-sm font-bold text-gray-800 leading-tight">
                  {user.email?.split("@")[0] || "Admin"}
                </p>
                <p className="text-xs font-medium text-violet-600 uppercase tracking-wider">{role}</p>
              </div>
              <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-100 to-violet-200 flex items-center justify-center shadow-sm border border-violet-100">
                <span className="text-sm font-bold text-violet-700">
                  {user.email?.charAt(0).toUpperCase()}
                </span>
              </div>
              <Button
                variant="ghost"
                size="icon"
                onClick={handleSignOut}
                className="text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors ml-1"
              >
                <LogOut className="w-5 h-5" />
              </Button>
            </div>
          </div>
        </header>

        {/* Page Content - Outlet renders child routes */}
        <main className="p-4 sm:p-6 lg:p-8 overflow-x-hidden flex-1">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
