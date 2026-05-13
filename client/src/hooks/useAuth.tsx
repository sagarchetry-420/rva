import { useEffect, useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import type { User, Session } from "@supabase/supabase-js";

export type AppRole = "admin" | "teacher" | "student" | "staff";

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [session, setSession] = useState<Session | null>(null);
  const [role, setRole] = useState<AppRole | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;

    // Helper to fetch user role from the database
    const fetchUserRole = async (userId: string) => {
      try {
        const { data, error } = await supabase
          .from("user_roles")
          .select("role")
          .eq("user_id", userId)
          .maybeSingle();

        if (error) {
          console.error("Error fetching user role:", error.message);
          return null;
        }
        return data?.role as AppRole;
      } catch (err) {
        console.error("Unexpected error in fetchUserRole:", err);
        return null;
      }
    };

    // Main sync function
    const syncAuthState = async (currentSession: Session | null) => {
      if (!mounted) return;

      if (!currentSession) {
        setUser(null);
        setSession(null);
        setRole(null);
        setLoading(false);
        return;
      }

      setSession(currentSession);
      setUser(currentSession.user);

      // Fetch role before we stop loading
      const userRole = await fetchUserRole(currentSession.user.id);
      
      if (mounted) {
        setRole(userRole);
        setLoading(false);
      }
    };

    // 1. Initial Session Check
    supabase.auth.getSession().then(({ data: { session: initialSession } }) => {
      syncAuthState(initialSession);
    });

    // 2. Listen for Auth Changes
    const { data: { subscription } } = supabase.auth.onAuthStateChange(async (event, currentSession) => {
      if (event === 'SIGNED_OUT') {
        syncAuthState(null);
      } else if (event === 'SIGNED_IN' || event === 'TOKEN_REFRESHED' || event === 'USER_UPDATED') {
        syncAuthState(currentSession);
      }
    });

    return () => {
      mounted = false;
      subscription.unsubscribe();
    };
  }, []);

  const signOut = async () => {
    try {
      await supabase.auth.signOut();
    } catch (err) {
      console.error("Logout error:", err);
    } finally {
      setUser(null);
      setSession(null);
      setRole(null);
    }
  };

  return { user, session, role, loading, signOut };
}