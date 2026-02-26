import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { ArrowLeft, UserPlus, Loader2 } from "lucide-react";

export default function AddStudent() {
  const [loading, setLoading] = useState(false);
  const [classes, setClasses] = useState<any[]>([]);
  const { toast } = useToast();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    email: "",
    password: "",
    firstName: "",
    lastName: "",
    classId: "",
    dob: "",
  });

  useEffect(() => {
    fetchClasses();
  }, []);

  const fetchClasses = async () => {
    const { data, error } = await supabase
      .from("classes")
      .select("id, name")
      .order("name", { ascending: true });
      
    if (error) {
      toast({
        title: "Error fetching classes",
        description: error.message,
        variant: "destructive",
      });
    } else {
      setClasses(data || []);
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      // 1. Create the Auth User
      // Note: In Supabase, if "Confirm Email" is ON, the user is created but not 'active'
      const { data: authData, error: authError } = await supabase.auth.signUp({
        email: formData.email,
        password: formData.password,
        options: {
          data: {
            first_name: formData.firstName,
            last_name: formData.lastName,
          },
        },
      });

      if (authError) throw authError;

      const userId = authData.user?.id;

      if (!userId) {
        throw new Error("Could not retrieve User ID after signup.");
      }

      // 2. Assign 'student' role in user_roles table
      // We do this first because other tables might depend on this role via RLS
      const { error: roleError } = await supabase
        .from("user_roles")
        .insert([{ 
            user_id: userId, 
            role: 'student' 
        }]);
      
      if (roleError) {
        console.error("Role Error:", roleError);
        throw new Error("User created, but failed to assign student role.");
      }

      // 3. Add record to students table
      // The 'profiles' table is handled by your SQL trigger 'handle_new_user'
      const { error: studentError } = await supabase
        .from("students")
        .insert([
          { 
            user_id: userId, 
            class_id: formData.classId || null,
            dob: formData.dob || null,
            enrollment_date: new Date().toISOString().split('T')[0] // Format: YYYY-MM-DD
          }
        ]);

      if (studentError) {
        console.error("Student Table Error:", studentError);
        throw new Error("User created, but failed to link to student profile.");
      }

      toast({ 
        title: "Enrollment Successful", 
        description: `${formData.firstName} has been added to the system.`,
      });
      
      navigate("/dashboard/students");

    } catch (error: any) {
      toast({ 
        title: "Registration Failed", 
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
        onClick={() => navigate("/dashboard/students")} 
        className="mb-4 gap-2"
      >
        <ArrowLeft className="w-4 h-4" /> Back to Student Directory
      </Button>

      <Card className="shadow-lg border-t-4 border-t-primary">
        <CardHeader className="border-b bg-muted/30">
          <CardTitle className="flex items-center gap-2">
            <UserPlus className="w-5 h-5 text-primary" /> Student Registration
          </CardTitle>
        </CardHeader>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="firstName">First Name</Label>
                <Input 
                  id="firstName" 
                  placeholder="John" 
                  required 
                  value={formData.firstName} 
                  onChange={handleInputChange} 
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="lastName">Last Name</Label>
                <Input 
                  id="lastName" 
                  placeholder="Doe" 
                  required 
                  value={formData.lastName} 
                  onChange={handleInputChange} 
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">Login Email</Label>
              <Input 
                id="email" 
                type="email" 
                placeholder="student@school.com" 
                required 
                value={formData.email} 
                onChange={handleInputChange} 
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">Login Password</Label>
              <Input 
                id="password" 
                type="password" 
                placeholder="Minimum 6 characters" 
                required 
                value={formData.password} 
                onChange={handleInputChange} 
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Assign Class</Label>
                <Select 
                  onValueChange={(val) => setFormData({...formData, classId: val})}
                  value={formData.classId}
                  required
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select a class" />
                  </SelectTrigger>
                  <SelectContent>
                    {classes.length > 0 ? (
                      classes.map((c) => (
                        <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
                      ))
                    ) : (
                      <div className="p-2 text-xs text-center text-muted-foreground">
                        No classes found. Add classes first.
                      </div>
                    )}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="dob">Date of Birth</Label>
                <Input 
                  id="dob" 
                  type="date" 
                  value={formData.dob} 
                  onChange={handleInputChange} 
                />
              </div>
            </div>

            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin mr-2" />
                  Creating Student Account...
                </>
              ) : (
                "Enroll Student"
              )}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}