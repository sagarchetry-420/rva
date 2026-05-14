import { Toaster } from "@/components/ui/toaster"
import { Toaster as Sonner } from "@/components/ui/sonner"
import { TooltipProvider } from "@/components/ui/tooltip"
import { QueryClient, QueryClientProvider } from "@tanstack/react-query"
import { BrowserRouter, Routes, Route } from "react-router-dom"

// Page Imports
import Index from "./pages/Index"
import Login from "./pages/Login"
import StudentLogin from "./pages/StudentLogin"
import TeacherLogin from "./pages/TeacherLogin"
import ResetPassword from "./pages/ResetPassword"
import Dashboard from "./pages/Dashboard"
import NotFound from "./pages/NotFound"

// Management Pages
import StudentManagement from "./pages/StudentManagement"
import AddStudent from "./pages/AddStudent"
import TeacherManagement from "./pages/TeacherManagement"
import AddTeacher from "./pages/AddTeacher"
import TeacherDetails from "./pages/teachers/TeacherDetails"
import ClassManagement from "./pages/ClassManagement"
import SubjectManagement from "./pages/SubjectManagement"
import CreateNotice from "./pages/CreateNotice"
import NoticeManagement from "./pages/NoticeManagement"
import AttendanceManagement from "./pages/AttendanceManagement"
import ExamManagement from "./pages/ExamManagement"
import ResultsManagement from "./pages/ResultsManagement"
import RoutineManagement from "./pages/RoutineManagement"
import CreateRoutine from "./pages/CreateRoutine"
import ClassDetail from "./pages/classes/ClassDetail"

// Teacher Pages
import TeacherDashboard from "./pages/TeacherDashboard"

// Student Pages
import StudentDashboard from "./pages/StudentDashboard"

// Component Imports
import { ProtectedRoute } from "./components/ProtectedRoute"
import { PublicRoute } from "./components/PublicRoute"
import DashboardLayout from "./components/layout/DashboardLayout"

const queryClient = new QueryClient()

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <Toaster />
        <Sonner />
        <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
          <Routes>
            {/* Public Routes - redirect logged-in users to their dashboard */}
            <Route path="/" element={<PublicRoute><Index /></PublicRoute>} />
            <Route path="/login" element={<PublicRoute><Login /></PublicRoute>} />
            <Route path="/studentlogin" element={<PublicRoute><StudentLogin /></PublicRoute>} />
            <Route path="/teacherlogin" element={<PublicRoute><TeacherLogin /></PublicRoute>} />
            <Route path="/reset-password" element={<ResetPassword />} />

            {/* Protected Admin Dashboard Routes - Nested under DashboardLayout */}
            <Route
              path="/dashboard"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <DashboardLayout />
                </ProtectedRoute>
              }
            >
              <Route index element={<Dashboard />} />
              <Route path="students" element={<StudentManagement />} />
              <Route path="students/add" element={<AddStudent />} />
              <Route path="students/class/:classId" element={<ClassDetail />} />
              <Route path="teachers" element={<TeacherManagement />} />
              <Route path="teachers/add" element={<AddTeacher />} />
              <Route path="teachers/:id" element={<TeacherDetails />} />
              <Route path="classes" element={<ClassManagement />} />
              <Route path="subjects" element={<SubjectManagement />} />
              <Route path="notices" element={<NoticeManagement />} />
              <Route path="notices/create" element={<CreateNotice />} />
              <Route path="attendance" element={<AttendanceManagement />} />
              <Route path="exams" element={<ExamManagement />} />
              <Route path="results" element={<ResultsManagement />} />
              <Route path="routines" element={<RoutineManagement />} />
              <Route path="routines/create" element={<CreateRoutine />} />
            </Route>

            {/* Teacher Dashboard Route */}
            <Route
              path="/dashboard/teacher"
              element={
                <ProtectedRoute allowedRoles={["teacher"]}>
                  <TeacherDashboard />
                </ProtectedRoute>
              }
            />

            {/* Student Dashboard Route */}
            <Route
              path="/student-dashboard"
              element={
                <ProtectedRoute allowedRoles={["student"]}>
                  <StudentDashboard />
                </ProtectedRoute>
              }
            />

            {/* Fallback Route */}
            <Route path="*" element={<NotFound />} />
          </Routes>
        </BrowserRouter>
      </TooltipProvider>
    </QueryClientProvider>
  )
}

export default App
