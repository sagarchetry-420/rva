import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Presentation, Eye, EyeOff, Loader2 } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function TeacherLogin() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [mode, setMode] = useState<"login" | "forgot">("login");
  const navigate = useNavigate();
  const { toast } = useToast();

  const handleTeacherLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    
    try {
      // 1. Authenticate the user
      const { data: authData, error: authError } = await supabase.auth.signInWithPassword({ 
        email, 
        password 
      });
      
      // Catch common Auth errors (Invalid credentials, Email not confirmed, etc.)
      if (authError) throw authError;

      if (!authData.user) throw new Error("Authentication failed. No user found.");

      // 2. Fetch the user's role
      // We use .select("role") and ensure the user can read their own row via RLS
      const { data: roleData, error: roleError } = await supabase
        .from("user_roles")
        .select("role")
        .eq("user_id", authData.user.id)
        .maybeSingle();

      if (roleError) throw roleError;

      // 3. Strict Role Validation
      if (!roleData || roleData.role !== 'teacher') {
        // Security: If they aren't a teacher, sign them out immediately
        await supabase.auth.signOut();
        
        // We throw a generic error to avoid revealing role information to attackers
        throw new Error("Access denied. This portal is strictly for teacher accounts.");
      }

      // 4. Check if teacher is marked as 'left'
      const { data: teacherData } = await supabase
        .from("teachers")
        .select("status")
        .eq("user_id", authData.user.id)
        .maybeSingle();

      if (teacherData && teacherData.status === 'left') {
        await supabase.auth.signOut();
        throw new Error("Access denied. Your teacher account has been deactivated.");
      }

      // 4. Success Navigation
      toast({ 
        title: "Success", 
        description: "Welcome to the Teacher Portal." 
      });
      
      navigate("/dashboard/teacher");

    } catch (error: any) {
      console.error("Login Process Error:", error.message);
      
      toast({ 
        title: "Login failed", 
        description: error.message || "Invalid teacher email or password.", 
        variant: "destructive" 
      });
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email) {
      toast({ 
        title: "Email required", 
        description: "Please enter your teacher email first to reset your password.", 
        variant: "destructive" 
      });
      return;
    }
    
    setLoading(true);
    const { error } = await supabase.auth.resetPasswordForEmail(email, {
      redirectTo: `${window.location.origin}/reset-password`,
    });
    setLoading(false);
    
    if (error) {
      toast({ title: "Error", description: error.message, variant: "destructive" });
    } else {
      toast({ title: "Email sent", description: "Check your school email for the reset link." });
      setMode("login");
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-background to-emerald-100 px-4">
      <Card className="w-full max-w-md border-emerald-200 shadow-lg">
        <CardHeader className="text-center">
          <Link to="/" className="flex items-center justify-center gap-2 mb-4">
            <div className="w-12 h-12 rounded-full bg-emerald-600 flex items-center justify-center shadow-md">
              <Presentation className="w-7 h-7 text-white" />
            </div>
          </Link>
          <CardTitle className="font-display text-2xl text-emerald-900">
            {mode === "login" ? "Teacher Portal" : "Reset Teacher Password"}
          </CardTitle>
          <CardDescription>
            {mode === "login"
              ? "Sign in to manage your classes, grading, and attendance"
              : "Enter your teacher email to receive a reset link"}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={mode === "login" ? handleTeacherLogin : handleForgotPassword} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="email">Teacher Email</Label>
              <Input 
                id="email" 
                type="email" 
                placeholder="teacher@rosevalley.edu" 
                value={email} 
                onChange={(e) => setEmail(e.target.value)} 
                required 
                className="focus-visible:ring-emerald-500"
              />
            </div>
            
            {mode === "login" && (
              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <div className="relative">
                  <Input
                    id="password"
                    type={showPassword ? "text" : "password"}
                    placeholder="••••••••"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                    className="focus-visible:ring-emerald-500"
                  />
                  <button 
                    type="button" 
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-emerald-600 transition-colors" 
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  </button>
                </div>
              </div>
            )}
            
            <Button 
              type="submit" 
              className="w-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm" 
              disabled={loading}
            >
              {loading ? (
                <span className="flex items-center gap-2">
                  <Loader2 className="w-4 h-4 animate-spin" /> {mode === "login" ? "Verifying..." : "Sending..."}
                </span>
              ) : (
                mode === "login" ? "Access Portal" : "Send Reset Link"
              )}
            </Button>
          </form>

          <div className="mt-6 text-center text-sm space-y-2">
            {mode === "login" ? (
              <button 
                onClick={() => setMode("forgot")} 
                className="text-emerald-600 font-medium hover:underline hover:text-emerald-800 transition-colors"
              >
                Forgot your password?
              </button>
            ) : (
              <button 
                onClick={() => setMode("login")} 
                className="text-emerald-600 font-medium hover:underline hover:text-emerald-800 transition-colors"
              >
                Back to sign in
              </button>
            )}
            
            {mode === "login" && (
              <p className="text-muted-foreground mt-4 text-xs">
                Need an account or technical support? Please contact the IT admin.
              </p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}