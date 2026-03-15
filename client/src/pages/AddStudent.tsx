import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { ArrowLeft, UserPlus, Loader2 } from "lucide-react";

export default function AddStudent() {
  const [loading, setLoading] = useState(false);
  const [classesLoading, setClassesLoading] = useState(true);
  const [classes, setClasses] = useState<any[]>([]);
  const { toast } = useToast();
  const navigate = useNavigate();

  // Form State
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
    try {
<<<<<<< HEAD:src/pages/AddStudent.tsx
      const { data, error } = await supabase.from("classes").select("id, name");
      if (error) throw error;
      setClasses(data || []);
    } catch (error: any) {
      console.error("Error fetching classes:", error);
      toast({
        title: "Error",
        description: "Failed to load classes.",
        variant: "destructive"
      });
    } finally {
      setClassesLoading(false);
=======
      const data = await api.get<any[]>('/api/classes/simple');
      setClasses(data);
    } catch (error: any) {
      toast({
        title: "Error fetching classes",
        description: error.message,
        variant: "destructive",
      });
>>>>>>> testing:client/src/pages/AddStudent.tsx
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

<<<<<<< HEAD:src/pages/AddStudent.tsx
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
=======
  try {
    await api.post('/api/students', {
      email: formData.email,
      password: formData.password,
      firstName: formData.firstName,
      lastName: formData.lastName,
      classId: formData.classId,
      dob: formData.dob,
    });

    toast({
      title: "Enrollment Successful",
      description: `${formData.firstName} has been registered successfully.`,
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
>>>>>>> testing:client/src/pages/AddStudent.tsx

  return (
    <div className="container mx-auto py-8 max-w-2xl px-4">
      <Button 
        variant="ghost" 
        onClick={() => navigate("/dashboard/students")} 
        className="mb-4 gap-2"
      >
        <ArrowLeft className="w-4 h-4" /> Back to Students
      </Button>

      <Card className="shadow-lg border-primary/10">
        <CardHeader className="bg-primary/5">
          <CardTitle className="flex items-center gap-2">
            <UserPlus className="w-5 h-5 text-primary" /> Enroll New Student
          </CardTitle>
        </CardHeader>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="firstName">First Name</Label>
                <Input id="firstName" placeholder="John" required value={formData.firstName} onChange={handleInputChange} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="lastName">Last Name</Label>
                <Input id="lastName" placeholder="Doe" required value={formData.lastName} onChange={handleInputChange} />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">Student Email (Login ID)</Label>
              <Input id="email" type="email" placeholder="john.doe@school.com" required value={formData.email} onChange={handleInputChange} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">Temporary Password</Label>
              <Input id="password" type="password" placeholder="••••••••" required value={formData.password} onChange={handleInputChange} />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="class">Assign Class</Label>
                <Select onValueChange={(val) => setFormData({...formData, classId: val})}>
                  <SelectTrigger id="class">
                    <SelectValue placeholder="Select Class" />
                  </SelectTrigger>
                  <SelectContent>
                    {classes.map((c) => (
                      <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="dob">Date of Birth</Label>
                <Input id="dob" type="date" value={formData.dob} onChange={handleInputChange} />
              </div>
            </div>

            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
              Confirm Enrollment
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}