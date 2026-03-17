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
import ClassManagement from "./pages/ClassManagement"
import CreateNotice from "./pages/CreateNotice"
import NoticeManagement from "./pages/NoticeManagement"

// Teacher Pages
import TeacherDashboard from "./pages/TeacherDashboard"

// Student Pages
import StudentDashboard from "./pages/StudentDashboard"

// Component Imports
import { ProtectedRoute } from "./components/ProtectedRoute"
import { PublicRoute } from "./components/PublicRoute"

const queryClient = new QueryClient()

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <Toaster />
        <Sonner />
        <BrowserRouter>
          <Routes>
            {/* Public Routes - redirect logged-in users to their dashboard */}
            <Route path="/" element={<PublicRoute><Index /></PublicRoute>} />
            <Route path="/login" element={<PublicRoute><Login /></PublicRoute>} />
            <Route path="/studentlogin" element={<PublicRoute><StudentLogin /></PublicRoute>} />
            <Route path="/teacherlogin" element={<PublicRoute><TeacherLogin /></PublicRoute>} />
            <Route path="/reset-password" element={<PublicRoute><ResetPassword /></PublicRoute>} />

            {/* Protected Admin Dashboard Routes */}
            <Route
              path="/dashboard"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <Dashboard />
                </ProtectedRoute>
              }
            />

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

            {/* Admin-only Management Routes */}
            <Route
              path="/dashboard/students"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <StudentManagement />
                </ProtectedRoute>
              }
            />
            <Route
              path="/dashboard/students/add"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <AddStudent />
                </ProtectedRoute>
              }
            />
            <Route
              path="/dashboard/teachers"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <TeacherManagement />
                </ProtectedRoute>
              }
            />
            <Route
              path="/dashboard/teachers/add"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <AddTeacher />
                </ProtectedRoute>
              }
            />
            <Route
              path="/dashboard/classes"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <ClassManagement />
                </ProtectedRoute>
              }
            />
            <Route
              path="/dashboard/notices"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <NoticeManagement />
                </ProtectedRoute>
              }
            />
            <Route
              path="/dashboard/notices/create"
              element={
                <ProtectedRoute allowedRoles={["admin"]}>
                  <CreateNotice />
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
