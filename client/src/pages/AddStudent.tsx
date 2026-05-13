import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { generateTemporaryPassword } from "@/lib/passwordGenerator";
import { copyToClipboard } from "@/lib/clipboard";
import { generateStudentPDF } from "@/lib/pdfGenerator";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { ArrowLeft, UserPlus, Loader2, CheckCircle, RefreshCw, Copy, Eye, EyeOff, Download } from "lucide-react";

// Class sorting order (same as StudentManagement)
const CLASS_ORDER: Record<string, number> = {
  'nursery': 1,
  'kg': 2,
  'kindergarten': 2,
  'lkg': 2,
  'ukg': 3,
  '1': 4, 'class 1': 4, 'grade 1': 4, 'i': 4,
  '2': 5, 'class 2': 5, 'grade 2': 5, 'ii': 5,
  '3': 6, 'class 3': 6, 'grade 3': 6, 'iii': 6,
  '4': 7, 'class 4': 7, 'grade 4': 7, 'iv': 7,
  '5': 8, 'class 5': 8, 'grade 5': 8, 'v': 8,
  '6': 9, 'class 6': 9, 'grade 6': 9, 'vi': 9,
  '7': 10, 'class 7': 10, 'grade 7': 10, 'vii': 10,
  '8': 11, 'class 8': 11, 'grade 8': 11, 'viii': 11,
  '9': 12, 'class 9': 12, 'grade 9': 12, 'ix': 12,
  '10': 13, 'class 10': 13, 'grade 10': 13, 'x': 13,
  '11': 14, 'class 11': 14, 'grade 11': 14, 'xi': 14,
  '12': 15, 'class 12': 15, 'grade 12': 15, 'xii': 15,
};

function getClassSortOrder(className: string): number {
  const lower = className.toLowerCase().trim();
  if (CLASS_ORDER[lower] !== undefined) return CLASS_ORDER[lower];

  // Extract number from class name
  const numMatch = lower.match(/(\d+)/);
  if (numMatch) {
    const num = parseInt(numMatch[1]);
    if (num >= 1 && num <= 12) return num + 3;
  }

  return 50; // Default for unknown classes
}

export default function AddStudent() {
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [passwordCopied, setPasswordCopied] = useState(false);
  const [classes, setClasses] = useState<any[]>([]);
  const [enrollmentSuccess, setEnrollmentSuccess] = useState(false);
  const [enrollmentData, setEnrollmentData] = useState<{
    email: string;
    password: string;
    firstName: string;
    lastName: string;
  } | null>(null);
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
    let isMounted = true;
    try {
      const data = await api.get<any[]>('/api/classes/simple');
      if (isMounted) {
        setClasses(data);
      }
    } catch (error: any) {
      if (isMounted) {
        toast({
          title: "Error fetching classes",
          description: error.message,
          variant: "destructive",
        });
      }
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.password) {
      toast({
        title: "Password Required",
        description: "Please generate a temporary password before enrolling.",
        variant: "destructive"
      });
      return;
    }

    if (!formData.classId) {
      toast({
        title: "Class Required",
        description: "Please select a class for the student.",
        variant: "destructive"
      });
      return;
    }

    setLoading(true);

    try {
      console.log('[AddStudent] Submitting:', {
        email: formData.email,
        firstName: formData.firstName,
        lastName: formData.lastName,
        classId: formData.classId
      });

      await api.post('/api/students', {
        email: formData.email,
        password: formData.password,
        firstName: formData.firstName,
        lastName: formData.lastName,
        classId: formData.classId,
        dob: formData.dob,
      });

      // Store enrollment data and show success screen
      setEnrollmentData({
        email: formData.email,
        password: formData.password,
        firstName: formData.firstName,
        lastName: formData.lastName,
      });
      setEnrollmentSuccess(true);

      toast({
        title: "Enrollment Successful",
        description: `${formData.firstName} has been registered successfully.`,
      });

    } catch (error: any) {
      console.error('[AddStudent] Error:', error);
      toast({
        title: "Registration Failed",
        description: error.message || "An error occurred during registration",
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
        <ArrowLeft className="w-4 h-4" /> Back to Students
      </Button>

      {enrollmentSuccess && enrollmentData ? (
        <Card className="shadow-lg border-green-200 bg-green-50">
          <CardContent className="pt-6">
            <div className="text-center space-y-6">
              <div className="flex justify-center">
                <div className="rounded-full bg-green-100 p-4">
                  <CheckCircle className="w-12 h-12 text-green-600" />
                </div>
              </div>

              <div>
                <h2 className="text-2xl font-bold text-gray-900">Enrollment Successful!</h2>
                <p className="text-gray-600 mt-2">
                  {enrollmentData.firstName} {enrollmentData.lastName} has been registered
                </p>
              </div>

              {/* Download PDF Button */}
              <Button
                onClick={() => {
                  // Create temporary student object with the actual password
                  const studentData: any = {
                    id: 'temp',
                    userId: 'temp',
                    firstName: enrollmentData.firstName,
                    lastName: enrollmentData.lastName,
                    email: enrollmentData.email,
                    className: classes.find(c => c.id === enrollmentData.classId)?.name || 'N/A',
                    classId: enrollmentData.classId,
                    enrollmentDate: new Date().toISOString(),
                    attendance: {
                      totalDays: 0,
                      presentDays: 0,
                      absentDays: 0,
                      lateDays: 0,
                      attendancePercentage: 0
                    }
                  };
                  generateStudentPDF(studentData, enrollmentData.password);
                  toast({ description: "PDF downloaded with credentials" });
                }}
                className="gap-2 bg-blue-600 hover:bg-blue-700"
              >
                <Download className="w-4 h-4" /> Download Credentials PDF
              </Button>

              <div className="flex gap-3">
                <Button
                  variant="outline"
                  onClick={() => {
                    setEnrollmentSuccess(false);
                    setEnrollmentData(null);
                    setFormData({
                      email: "",
                      password: "",
                      firstName: "",
                      lastName: "",
                      classId: "",
                      dob: "",
                    });
                  }}
                  className="flex-1 rounded-xl"
                >
                  Enroll Another Student
                </Button>
                <Button
                  onClick={() => navigate("/dashboard/students")}
                  className="flex-1 rounded-xl"
                >
                  Done
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      ) : (
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
                <Label>Temporary Password</Label>
                <Button
                  type="button"
                  variant={formData.password ? "default" : "outline"}
                  className={`w-full justify-center gap-2 rounded-xl h-10 border-dashed border-2 ${
                    formData.password ? 'bg-green-600 hover:bg-green-700 border-green-600' : ''
                  }`}
                  onClick={() => {
                    const newPassword = generateTemporaryPassword();
                    setFormData({ ...formData, password: newPassword });
                    setShowPassword(true);
                    setPasswordCopied(false);
                    console.log('[AddStudent] Password generated:', newPassword);
                    toast({
                      title: "Password Generated",
                      description: "A secure temporary password has been created.",
                    });
                  }}
                >
                  <RefreshCw className="w-4 h-4" />
                  {formData.password ? "Regenerate Password" : "Generate Password"}
                </Button>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="class">Assign Class</Label>
                  <Select onValueChange={(val) => setFormData({...formData, classId: val})}>
                    <SelectTrigger id="class">
                      <SelectValue placeholder="Select Class" />
                    </SelectTrigger>
                    <SelectContent>
                      {classes
                        .sort((a, b) => getClassSortOrder(a.name) - getClassSortOrder(b.name))
                        .map((c) => (
                          <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
                        ))}
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
                    max={new Date().toISOString().split('T')[0]}
                  />
                </div>
              </div>

              <Button type="submit" className="w-full" disabled={loading}>
                {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                Confirm Enrollment
              </Button>
            </form>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
