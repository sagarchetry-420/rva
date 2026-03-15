import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route } from "react-router-dom";

// Page Imports
import Index from "./pages/Index";
import Login from "./pages/Login";
import StudentLogin from "./pages/StudentLogin";
import TeacherLogin from "./pages/TeacherLogin";
import ResetPassword from "./pages/ResetPassword";
import Dashboard from "./pages/Dashboard";
import NotFound from "./pages/NotFound";

// Management Pages
import StudentManagement from "./pages/StudentManagement";
import AddStudent from "./pages/AddStudent";
import TeacherManagement from "./pages/TeacherManagement";
import AddTeacher from "./pages/AddTeacher";
import ClassManagement from "./pages/ClassManagement";
import CreateNotice from "./pages/CreateNotice";
import TeacherDashboard from "./pages/TeacherDashboard";

// Component Imports
import { ProtectedRoute } from "./components/ProtectedRoute";

const queryClient = new QueryClient();

const App = () => (
  <QueryClientProvider client={queryClient}>
    <TooltipProvider>
      <Toaster />
      <Sonner />
      <BrowserRouter>
        <Routes>
          {/* Public Routes */}
          <Route path="/" element={<Index />} />
          <Route path="/login" element={<Login />} />
          <Route path="/studentlogin" element={<StudentLogin />} />
          <Route path="/teacherlogin" element={<TeacherLogin />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          
          {/* Protected Admin/Dashboard Routes */}
          <Route path="/dashboard" element={<ProtectedRoute><Dashboard /></ProtectedRoute>} />

          <Route path="/dashboard/notices/create" element={<ProtectedRoute><CreateNotice /></ProtectedRoute>} />

          <Route path="/dashboard/teacher" element={<ProtectedRoute><TeacherDashboard /></ProtectedRoute>} />
          
          {/* Student Routes */}
          <Route path="/dashboard/students" element={<ProtectedRoute><StudentManagement /></ProtectedRoute>} />
          <Route path="/dashboard/students/add" element={<ProtectedRoute><AddStudent /></ProtectedRoute>} />
          
          {/* Teacher Routes */}
          <Route path="/dashboard/teachers" element={<ProtectedRoute><TeacherManagement /></ProtectedRoute>} />
          <Route path="/dashboard/teachers/add" element={<ProtectedRoute><AddTeacher /></ProtectedRoute>} />
          
          {/* School Structure Routes */}
          <Route path="/dashboard/classes" element={<ProtectedRoute><ClassManagement /></ProtectedRoute>} />
          

          {/* Fallback Route */}
          <Route path="*" element={<NotFound />} />
        </Routes>
      </BrowserRouter>
    </TooltipProvider>
  </QueryClientProvider>
);

export default App;