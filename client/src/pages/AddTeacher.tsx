import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { ArrowLeft, GraduationCap, Loader2 } from "lucide-react";

// Hardcoded departments for the teachers table
const DEPARTMENTS = [
  "Mathematics", "Science", "English", "History",
  "Physical Education", "Arts", "Computer Science", "Languages"
];

export default function AddTeacher() {
  const [loading, setLoading] = useState(false);

  // State to hold the dynamic classes and subjects from your database
  const [classes, setClasses] = useState<any[]>([]);
  const [subjects, setSubjects] = useState<any[]>([]);

  const { toast } = useToast();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    email: "",
    password: "",
    firstName: "",
    lastName: "",
    department: "",
    classId: "",
    subjectId: "",
    hireDate: new Date().toISOString().split('T')[0],
  });

  // Fetch Classes and Subjects when the component loads
  useEffect(() => {
    const fetchDropdownData = async () => {
      try {
        const [classesData, subjectsData] = await Promise.all([
          api.get<any[]>('/api/classes/simple'),
          api.get<any[]>('/api/classes/subjects'),
        ]);
        setClasses(classesData);
        setSubjects(subjectsData);
      } catch (error: any) {
        console.error("Error fetching dropdowns:", error);
        toast({
          title: "Warning",
          description: "Could not load all class or subject options.",
          variant: "destructive"
        });
      }
    };

    fetchDropdownData();
  }, [toast]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      await api.post('/api/teachers', {
        email: formData.email,
        password: formData.password,
        firstName: formData.firstName,
        lastName: formData.lastName,
        department: formData.department,
        classId: formData.classId,
        subjectId: formData.subjectId,
        hireDate: formData.hireDate,
      });

      toast({
        title: "Teacher Registered",
        description: `${formData.firstName} ${formData.lastName} has been added and assigned successfully.`
      });

      navigate("/dashboard/teachers");

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
    <div className="container mx-auto py-8 max-w-3xl px-4">
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
            Enter the details below to provision a new teacher account and assign their first class.
          </CardDescription>
        </CardHeader>
        <CardContent className="pt-4">
          <form onSubmit={handleSubmit} className="space-y-6">

            {/* Personal Info Row */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="firstName">First Name</Label>
                <Input id="firstName" placeholder="Jane" required value={formData.firstName} onChange={handleInputChange} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="lastName">Last Name</Label>
                <Input id="lastName" placeholder="Smith" required value={formData.lastName} onChange={handleInputChange} />
              </div>
            </div>

            {/* Account Info Row */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="email">Work Email</Label>
                <Input id="email" type="email" placeholder="teacher@school.edu" required value={formData.email} onChange={handleInputChange} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password">Temporary Password</Label>
                <Input id="password" type="password" placeholder="••••••••" required value={formData.password} onChange={handleInputChange} />
              </div>
            </div>

            <hr className="my-4 border-slate-200" />

            {/* Assignment Info Row */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Department</Label>
                <Select
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
                <Input id="hireDate" type="date" value={formData.hireDate} onChange={handleInputChange} required />
              </div>
            </div>

            {/* Teacher Subjects Mapping Row */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
              <div className="space-y-2">
                <Label>Assigned Subject</Label>
                <Select onValueChange={(val) => setFormData({...formData, subjectId: val})} required>
                  <SelectTrigger className="bg-white"><SelectValue placeholder="Select Subject" /></SelectTrigger>
                  <SelectContent>
                    {subjects.map((s) => (
                      <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Assigned Class</Label>
                <Select onValueChange={(val) => setFormData({...formData, classId: val})} required>
                  <SelectTrigger className="bg-white"><SelectValue placeholder="Select Class" /></SelectTrigger>
                  <SelectContent>
                    {classes.map((c) => (
                      <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin mr-2" />
                  Provisioning Account & Assignments...
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
