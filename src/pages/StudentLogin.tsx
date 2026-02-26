import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { BookOpen, Eye, EyeOff } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function StudentLogin() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { toast } = useToast();

  const handleStudentLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    
    // Authenticate the student using Supabase
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    
    setLoading(false);
    
    if (error) {
      toast({ 
        title: "Login failed", 
        description: "Please check your student email and password.", 
        variant: "destructive" 
      });
    } else {
      toast({ title: "Welcome back!", description: "Loading your student portal..." });
      // Redirect to the student-specific dashboard
      navigate("/student-dashboard");
    }
  };

  const handleForgotPassword = async () => {
    if (!email) {
      toast({ 
        title: "Email required", 
        description: "Please enter your student email first to reset your password.", 
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
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-background to-blue-100 px-4">
      <Card className="w-full max-w-md border-blue-200 shadow-lg">
        <CardHeader className="text-center">
          <Link to="/" className="flex items-center justify-center gap-2 mb-4">
            <div className="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center">
              {/* Using a Book icon to differentiate from the Admin Graduation Cap */}
              <BookOpen className="w-7 h-7 text-white" />
            </div>
          </Link>
          <CardTitle className="font-display text-2xl text-blue-900">
            Student Portal
          </CardTitle>
          <CardDescription>
            Sign in to access your classes, grades, and assignments
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleStudentLogin} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="email">Student Email</Label>
              <Input 
                id="email" 
                type="email" 
                placeholder="student@rosevalley.edu" 
                value={email} 
                onChange={(e) => setEmail(e.target.value)} 
                required 
                className="focus-visible:ring-blue-500"
              />
            </div>
            
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
                  className="focus-visible:ring-blue-500"
                />
                <button 
                  type="button" 
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-blue-600 transition-colors" 
                  onClick={() => setShowPassword(!showPassword)}
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>
            
            <Button 
              type="submit" 
              className="w-full bg-blue-600 hover:bg-blue-700 text-white" 
              disabled={loading}
            >
              {loading ? "Verifying..." : "Access Portal"}
            </Button>
          </form>

          <div className="mt-6 text-center text-sm">
            <button 
              onClick={handleForgotPassword} 
              className="text-blue-600 hover:underline hover:text-blue-800 transition-colors"
            >
              Forgot your password?
            </button>
            <p className="text-muted-foreground mt-4 text-xs">
              Need an account? Please contact your school administrator.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}