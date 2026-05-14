import { ReactNode, useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { supabase } from "@/integrations/supabase/client";
import { Loader2 } from "lucide-react";
import type { AppRole } from "@/hooks/useAuth";

interface ProtectedRouteProps {
  children: ReactNode;
  allowedRoles?: AppRole[];
}

function getDashboardPath(role: AppRole): string {
  switch (role) {
    case "admin":
      return "/dashboard";
    case "teacher":
      return "/dashboard/teacher";
    case "student":
      return "/student-dashboard";
    default:
      return "/dashboard";
  }
}

export const ProtectedRoute = ({ children, allowedRoles }: ProtectedRouteProps) => {
  const [state, setState] = useState<{
    loading: boolean;
    authenticated: boolean;
    role: AppRole | null;
  }>({ loading: true, authenticated: false, role: null });
  const location = useLocation();

  useEffect(() => {
    let mounted = true;

    const checkAuth = async () => {
      const {
        data: { session },
      } = await supabase.auth.getSession();

      if (!session) {
        if (mounted) setState({ loading: false, authenticated: false, role: null });
        return;
      }

      const { data } = await supabase
        .from("user_roles")
        .select("role")
        .eq("user_id", session.user.id)
        .maybeSingle();

      if (mounted) {
        setState({
          loading: false,
          authenticated: true,
          role: (data?.role as AppRole) ?? null,
        });
      }
    };

    checkAuth();

    const {
      data: { subscription },
    } = supabase.auth.onAuthStateChange((_event, session) => {
      if (!session && mounted) {
        setState({ loading: false, authenticated: false, role: null });
      }
    });

    return () => {
      mounted = false;
      subscription.unsubscribe();
    };
  }, []);

  // Loading
  if (state.loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  // Not authenticated → send to landing page
  if (!state.authenticated) {
    return <Navigate to="/" state={{ from: location }} replace />;
  }

  // Authenticated but role not allowed → redirect to their own dashboard
  if (allowedRoles && state.role && !allowedRoles.includes(state.role)) {
    return <Navigate to={getDashboardPath(state.role)} replace />;
  }

  return <>{children}</>;
};
