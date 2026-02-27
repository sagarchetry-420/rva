import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { ArrowLeft, GraduationCap, Loader2 } from "lucide-react";

const DEPARTMENTS = [
  "Mathematics", "Science", "English", "History", 
  "Physical Education", "Arts", "Computer Science", "Languages"
];

export default function AddTeacher() {
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    email: "",
    password: "",
    firstName: "",
    lastName: "",
    department: "",
    hireDate: new Date().toISOString().split('T')[0],
  });

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const { data: authData, error: authError } = await supabase.auth.signUp({
        email: formData.email,
        password: formData.password,
        options: {
          data: {
            first_name: formData.firstName, 
            last_name: formData.lastName,  
            role: 'teacher',               
            department: formData.department,
            hire_date: formData.hireDate
          },
        },
      });

      // 🛑 1. Catch Supabase Auth Errors
      if (authError) {
        // Email Rate Limit (the 2 per hour limit we saw earlier)
        if (authError.status === 429) {
          throw new Error("Email rate limit exceeded. Please wait an hour or disable 'Confirm Email' in Supabase settings.");
        }
        // Duplicate User
        if (authError.message.toLowerCase().includes("already registered") || authError.status === 422) {
          throw new Error("This email is already registered. Please use a different email or check the Teacher Directory.");
        }
        throw authError;
      }

      // 🛑 2. Handle "Silent" failures (User exists but trigger didn't run)
      if (authData.user?.identities?.length === 0) {
        throw new Error("This email is already associated with an account. Please delete the old user from the Auth dashboard first.");
      }

      toast({ 
        title: "Success", 
        description: `${formData.firstName} ${formData.lastName} has been enrolled as a faculty member.`,
      });
      
      navigate("/dashboard/teachers");

    } catch (error: any) {
      console.error("Enrollment Error:", error);
      toast({ 
        title: "Enrollment Blocked", 
        description: error.message, 
        variant: "destructive" 
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container mx-auto py-8 max-w-2xl px-4">
      <Button 
        variant="ghost" 
        onClick={() => navigate("/dashboard/teachers")} 
        className="mb-4 gap-2 text-muted-foreground hover:text-primary transition-colors"
      >
        <ArrowLeft className="w-4 h-4" /> Back to Directory
      </Button>

      <Card className="shadow-lg border-t-4 border-t-primary">
        <CardHeader className="space-y-1">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-primary/10 rounded-lg">
              <GraduationCap className="w-6 h-6 text-primary" />
            </div>
            <CardTitle className="text-2xl">New Faculty Member</CardTitle>
          </div>
          <CardDescription>
            Enter the details below to provision a new teacher account.
          </CardDescription>
        </CardHeader>
        <CardContent className="pt-4">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="firstName">First Name</Label>
                <Input 
                  id="firstName" 
                  placeholder="Jane" 
                  required 
                  value={formData.firstName} 
                  onChange={handleInputChange} 
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="lastName">Last Name</Label>
                <Input 
                  id="lastName" 
                  placeholder="Smith" 
                  required 
                  value={formData.lastName} 
                  onChange={handleInputChange} 
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">Work Email</Label>
              <Input 
                id="email" 
                type="email" 
                placeholder="teacher@school.edu" 
                required 
                value={formData.email} 
                onChange={handleInputChange} 
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">Temporary Password</Label>
              <Input 
                id="password" 
                type="password" 
                placeholder="••••••••" 
                required 
                value={formData.password} 
                onChange={handleInputChange} 
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Department</Label>
                <Select 
                  value={formData.department} // Controlled component
                  onValueChange={(val) => setFormData({...formData, department: val})}
                  required
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select Dept" />
                  </SelectTrigger>
                  <SelectContent>
                    {DEPARTMENTS.map((dept) => (
                      <SelectItem key={dept} value={dept}>{dept}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="hireDate">Hire Date</Label>
                <Input 
                  id="hireDate" 
                  type="date" 
                  value={formData.hireDate} 
                  onChange={handleInputChange} 
                  required 
                />
              </div>
            </div>

            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin mr-2" />
                  Provisioning Teacher...
                </>
              ) : (
                "Enroll Teacher"
              )}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}