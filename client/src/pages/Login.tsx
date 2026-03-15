import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { GraduationCap, Eye, EyeOff } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  // We removed "signup" from the mode options
  const [mode, setMode] = useState<"login" | "forgot">("login");
  const navigate = useNavigate();
  const { toast } = useToast();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    setLoading(false);
    
    if (error) {
      toast({ title: "Login failed", description: error.message, variant: "destructive" });
    } else {
      toast({ title: "Welcome Admin", description: "Loading dashboard..." });
      navigate("/dashboard");
    }
  };

  const handleForgotPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    const { error } = await supabase.auth.resetPasswordForEmail(email, {
      redirectTo: `${window.location.origin}/reset-password`,
    });
    setLoading(false);
    
    if (error) {
      toast({ title: "Error", description: error.message, variant: "destructive" });
    } else {
      toast({ title: "Email sent", description: "Check your admin email for the reset link." });
      setMode("login"); // Send them back to the login screen after requesting a reset
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/5 via-background to-secondary/10 px-4">
      <Card className="w-full max-w-md border-primary/20 shadow-lg">
        <CardHeader className="text-center">
          <Link to="/" className="flex items-center justify-center gap-2 mb-4 hover:opacity-80 transition-opacity">
            <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center shadow-md">
              <GraduationCap className="w-7 h-7 text-primary-foreground" />
            </div>
          </Link>
          <CardTitle className="font-display text-2xl text-primary">
            {mode === "login" ? "Admin Portal" : "Reset Admin Password"}
          </CardTitle>
          <CardDescription>
            {mode === "login"
              ? "Secure access for Rose Valley Academy staff"
              : "Enter your admin email to receive a reset link"}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={mode === "login" ? handleLogin : handleForgotPassword} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="email">Admin Email</Label>
              <Input 
                id="email" 
                type="email" 
                placeholder="admin@rosevalley.edu" 
                value={email} 
                onChange={(e) => setEmail(e.target.value)} 
                required 
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
                  />
                  <button 
                    type="button" 
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-primary transition-colors" 
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  </button>
                </div>
              </div>
            )}
            <Button type="submit" className="w-full mt-2" disabled={loading}>
              {loading ? "Authenticating..." : mode === "login" ? "Sign In as Admin" : "Send Reset Link"}
            </Button>
          </form>

          <div className="mt-6 text-center text-sm space-y-2">
            {mode === "login" ? (
              <button 
                onClick={() => setMode("forgot")} 
                className="text-primary hover:underline font-medium"
              >
                Forgot admin password?
              </button>
            ) : (
              <button 
                onClick={() => setMode("login")} 
                className="text-primary hover:underline font-medium"
              >
                Back to secure login
              </button>
            )}
            
            {/* Added a helper note since signup is removed */}
            {mode === "login" && (
              <p className="text-muted-foreground text-xs mt-4">
                Authorized personnel only. Contact the system administrator if you need access.
              </p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}