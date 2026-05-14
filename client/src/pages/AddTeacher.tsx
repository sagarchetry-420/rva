import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { generateTemporaryPassword } from "@/lib/passwordGenerator";
import { generateTeacherPDF } from "@/lib/pdfGenerator";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { ArrowLeft, GraduationCap, Loader2, RefreshCw, Trash2, Plus, Copy, CheckCircle, Download, X } from "lucide-react";

// Class sorting order
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

export default function AddTeacher() {
  type CreateTeacherResponse = {
    user: { id: string } | null;
    emailSent?: boolean;
    emailError?: string | null;
  };

  const [loading, setLoading] = useState(false);

  // Success state to track enrolled teacher
  const [enrollmentSuccess, setEnrollmentSuccess] = useState(false);
  const [enrollmentData, setEnrollmentData] = useState<{
    email: string;
    password: string;
    firstName: string;
    lastName: string;
    hireDate: string;
    assignments: Array<{ classId: string; subjectId: string }>;
    emailSent: boolean;
    emailError: string | null;
  } | null>(null);

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
    hireDate: new Date().toISOString().split('T')[0],
  });

  // State for multiple assignments
  const [assignments, setAssignments] = useState<Array<{ classId: string; subjectId: string }>>([]);
  const [tempAssignment, setTempAssignment] = useState({ classId: "", subjectId: "" });

  // Fetch Classes and Subjects when the component loads
  useEffect(() => {
    let isMounted = true;
    const controller = new AbortController();

    const fetchDropdownData = async () => {
      try {
        const [classesData, subjectsData] = await Promise.all([
          api.get<any[]>('/api/classes/simple'),
          api.get<any[]>('/api/classes/subjects'),
        ]);

        if (isMounted) {
          setClasses(classesData);
          setSubjects(subjectsData);
        }
      } catch (error: any) {
        if (isMounted && error.name !== 'AbortError') {
          console.error("Error fetching dropdowns:", error);
          toast({
            title: "Error Loading Data",
            description: error.message || "Could not load class or subject options.",
            variant: "destructive"
          });
        }
      }
    };

    fetchDropdownData();

    return () => {
      isMounted = false;
      controller.abort();
    };
  }, [toast]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.password) {
      toast({
        title: "Password Required",
        description: "Please generate a temporary password.",
        variant: "destructive"
      });
      return;
    }

    if (assignments.length === 0) {
      toast({
        title: "Assignments Required",
        description: "Please add at least one class-subject assignment.",
        variant: "destructive"
      });
      return;
    }

    setLoading(true);

    const payload = {
      email: formData.email,
      password: formData.password,
      firstName: formData.firstName,
      lastName: formData.lastName,
      hireDate: formData.hireDate,
      assignments: assignments
    };

    console.log('[AddTeacher] Sending payload:', JSON.stringify(payload, null, 2));

    try {
      const response = await api.post<CreateTeacherResponse>('/api/teachers', payload);
      console.log('[AddTeacher] Success response:', response);

      // Store enrollment data for success screen
      setEnrollmentData({
        email: formData.email,
        password: formData.password,
        firstName: formData.firstName,
        lastName: formData.lastName,
        hireDate: formData.hireDate,
        assignments: assignments,
        emailSent: response.emailSent !== false,
        emailError: response.emailError ?? null,
      });
      setEnrollmentSuccess(true);

      toast({
        title: "Enrollment Successful",
        description: response.emailSent === false
          ? `${formData.firstName} has been registered, but credentials email could not be sent.`
          : `${formData.firstName} has been registered and credentials email has been sent.`,
      });

    } catch (error: any) {
      console.error('[AddTeacher] Error details:', {
        message: error.message,
        status: error.status,
        body: error.body,
        fullError: error
      });

      toast({
        title: "Registration Failed",
        description: error.message || "Unknown error occurred",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container mx-auto py-8 max-w-3xl px-4">
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
                <h2 className="text-2xl font-bold text-gray-900">Teacher Enrolled Successfully!</h2>
                <p className="text-gray-600 mt-2">
                  {enrollmentData.firstName} {enrollmentData.lastName} has been registered with {enrollmentData.assignments.length} assignment(s)
                </p>
              </div>

              <div
                className={`rounded-lg border p-3 text-sm ${
                  enrollmentData.emailSent
                    ? "border-green-200 bg-green-100 text-green-800"
                    : "border-orange-200 bg-orange-100 text-orange-800"
                }`}
              >
                {enrollmentData.emailSent ? (
                  <p>
                    Credentials email sent to <span className="font-semibold">{enrollmentData.email}</span>
                  </p>
                ) : (
                  <p>
                    Teacher created, but email was not sent. {enrollmentData.emailError ? `Reason: ${enrollmentData.emailError}` : "Please verify email setup and share credentials manually."}
                  </p>
                )}
              </div>

              {/* Download PDF Button */}
              <Button
                onClick={() => {
                  const teacherData = {
                    id: 'temp',
                    user_id: 'temp',
                    profiles: {
                      first_name: enrollmentData.firstName,
                      last_name: enrollmentData.lastName
                    },
                    hire_date: enrollmentData.hireDate,
                    email: enrollmentData.email,
                    teacher_subjects: enrollmentData.assignments.map(a => ({
                      classes: { id: a.classId, name: '' },
                      subjects: { id: a.subjectId, name: '' }
                    }))
                  };
                  generateTeacherPDF(teacherData, enrollmentData.password);
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
                      hireDate: new Date().toISOString().split('T')[0],
                    });
                    setAssignments([]);
                    setTempAssignment({ classId: "", subjectId: "" });
                  }}
                  className="flex-1 rounded-xl"
                >
                  Add Another Teacher
                </Button>
                <Button
                  onClick={() => navigate("/dashboard/teachers")}
                  className="flex-1 rounded-xl"
                >
                  Done
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      ) : (
        <>
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
            Enter the details below to provision a new teacher account and assign them to multiple classes and subjects.
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
              <div className="space-y-4">
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
            </div>

            <hr className="my-4 border-slate-200" />

            {/* Hire Date */}
            <div className="space-y-2">
              <Label htmlFor="hireDate">Hire Date</Label>
              <Input
                id="hireDate"
                type="date"
                value={formData.hireDate}
                onChange={handleInputChange}
                max={new Date().toISOString().split('T')[0]}
                required
              />
            </div>

            {/* Teacher Class and Subject Assignments */}
            <div className="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
              <h3 className="font-semibold text-lg">Class & Subject Assignments</h3>

              {/* Add Assignment Row */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div className="space-y-2">
                  <Label>Select Class</Label>
                  <Select
                    onValueChange={(val) => setTempAssignment({ ...tempAssignment, classId: val, subjectId: "" })}
                    value={tempAssignment.classId}
                  >
                    <SelectTrigger className="bg-white"><SelectValue placeholder="Select Class" /></SelectTrigger>
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
                  <Label>Select Subject</Label>
                  <Select
                    onValueChange={(val) => setTempAssignment({ ...tempAssignment, subjectId: val })}
                    value={tempAssignment.subjectId}
                    disabled={!tempAssignment.classId}
                  >
                    <SelectTrigger className={`bg-white ${!tempAssignment.classId ? 'opacity-50 cursor-not-allowed' : ''}`}>
                      <SelectValue placeholder={tempAssignment.classId ? "Select Subject" : "Select Class First"} />
                    </SelectTrigger>
                    <SelectContent>
                      {subjects
                        .filter((s: any) => s.classes?.id === tempAssignment.classId)
                        .map((s) => (
                          <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>
                        ))}
                    </SelectContent>
                  </Select>
                </div>

                <Button
                  type="button"
                  onClick={() => {
                    if (!tempAssignment.classId || !tempAssignment.subjectId) {
                      toast({
                        title: "Missing Selection",
                        description: "Please select both a class and subject.",
                        variant: "destructive"
                      });
                      return;
                    }

                    // Check for duplicates
                    const isDuplicate = assignments.some(
                      (a) => a.classId === tempAssignment.classId && a.subjectId === tempAssignment.subjectId
                    );

                    if (isDuplicate) {
                      toast({
                        title: "Duplicate Assignment",
                        description: "This class-subject combination is already added.",
                        variant: "destructive"
                      });
                      return;
                    }

                    setAssignments([...assignments, tempAssignment]);
                    setTempAssignment({ classId: "", subjectId: "" });
                  }}
                  className="gap-2 bg-blue-600 hover:bg-blue-700"
                >
                  <Plus className="w-4 h-4" />
                  Add Assignment
                </Button>
              </div>

              {/* List of Assignments */}
              {assignments.length > 0 && (
                <div className="space-y-2 mt-6 pt-4 border-t border-slate-200">
                  <h4 className="font-medium text-sm">Selected Assignments:</h4>
                  {assignments.map((assignment, index) => {
                    const classObj = classes.find((c) => c.id === assignment.classId);
                    const subjectObj = subjects.find((s) => s.id === assignment.subjectId);
                    return (
                      <div key={index} className="flex items-center justify-between p-3 bg-white rounded border border-slate-200">
                        <span className="text-sm">
                          <span className="font-semibold">{classObj?.name}</span> - <span className="text-gray-600">{subjectObj?.name}</span>
                        </span>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => {
                            setAssignments(assignments.filter((_, i) => i !== index));
                          }}
                          className="text-red-600 hover:text-red-700 hover:bg-red-50"
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    );
                  })}
                </div>
              )}
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
        </>
      )}
    </div>
  );
}
