import { ReactNode, useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { supabase } from "@/integrations/supabase/client";
import { Loader2 } from "lucide-react";
import type { AppRole } from "@/hooks/useAuth";

interface PublicRouteProps {
  children: ReactNode;
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

export const PublicRoute = ({ children }: PublicRouteProps) => {
  const [state, setState] = useState<{
    loading: boolean;
    redirectTo: string | null;
  }>({ loading: true, redirectTo: null });

  useEffect(() => {
    let mounted = true;

    const checkAuth = async () => {
      const {
        data: { session },
      } = await supabase.auth.getSession();

      if (!session) {
        if (mounted) setState({ loading: false, redirectTo: null });
        return;
      }

      // User is logged in — fetch their role to redirect to the right dashboard
      const { data } = await supabase
        .from("user_roles")
        .select("role")
        .eq("user_id", session.user.id)
        .maybeSingle();

      if (mounted) {
        const role = data?.role as AppRole | undefined;
        setState({
          loading: false,
          redirectTo: role ? getDashboardPath(role) : null,
        });
      }
    };

    checkAuth();

    return () => {
      mounted = false;
    };
  }, []);

  if (state.loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  if (state.redirectTo) {
    return <Navigate to={state.redirectTo} replace />;
  }

  return <>{children}</>;
};
