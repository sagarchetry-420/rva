import { useState, useEffect } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "@/lib/api";
import { generateTeacherPDF } from "@/lib/pdfGenerator";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Loader2, ArrowLeft, Download } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function TeacherDetails() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { toast } = useToast();

  const [teacher, setTeacher] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [password, setPassword] = useState<string>("");

  useEffect(() => {
    if (id) {
      fetchTeacherDetails();
    }
  }, [id]);

  const fetchTeacherDetails = async () => {
    try {
      const data = await api.get<any>(`/api/teachers/${id}`);
      setTeacher(data);
      // Generate a display password based on teacher info
      // In a real system, you'd retrieve the actual password from secure storage
      setPassword(generateDisplayPassword(data.profiles?.first_name || "Teacher", data.hire_date));
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message || "Failed to load teacher details",
        variant: "destructive"
      });
      navigate("/dashboard/teachers");
    } finally {
      setLoading(false);
    }
  };

  // Generate a password for display purposes (based on teacher info)
  const generateDisplayPassword = (firstName: string, hireDate: string): string => {
    const year = new Date(hireDate).getFullYear();
    const initials = firstName.substring(0, 2).toUpperCase();
    const randomNum = Math.floor(Math.random() * 9000) + 1000;
    return `${initials}@${year}${randomNum}`;
  };

  const handleDownloadPDF = () => {
    if (teacher) {
      generateTeacherPDF(teacher, password);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  if (!teacher) {
    return (
      <div className="p-4 md:p-8">
        <p className="text-red-600">Teacher not found</p>
      </div>
    );
  }

  return (
    <div className="p-4 md:p-8 max-w-4xl mx-auto space-y-6">
      <Button
        variant="ghost"
        onClick={() => navigate("/dashboard/teachers")}
        className="gap-2 text-muted-foreground hover:text-primary"
      >
        <ArrowLeft className="w-4 h-4" /> Back to Teachers
      </Button>

      <Card className="shadow-lg border-t-4 border-t-primary">
        <CardHeader className="bg-primary/5">
          <div className="flex justify-between items-start">
            <div>
              <CardTitle className="text-3xl">
                {teacher.profiles?.first_name} {teacher.profiles?.last_name}
              </CardTitle>
              <p className="text-muted-foreground mt-2">Teacher Profile</p>
            </div>
            <Button onClick={handleDownloadPDF} className="gap-2">
              <Download className="w-4 h-4" /> Download PDF
            </Button>
          </div>
        </CardHeader>

        <CardContent className="pt-8 space-y-8">
          {/* Personal Information */}
          <div>
            <h3 className="text-lg font-semibold mb-4">Personal Information</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="p-4 bg-muted rounded-lg">
                <p className="text-sm text-muted-foreground">First Name</p>
                <p className="text-lg font-medium">{teacher.profiles?.first_name || "N/A"}</p>
              </div>
              <div className="p-4 bg-muted rounded-lg">
                <p className="text-sm text-muted-foreground">Last Name</p>
                <p className="text-lg font-medium">{teacher.profiles?.last_name || "N/A"}</p>
              </div>
              <div className="p-4 bg-muted rounded-lg">
                <p className="text-sm text-muted-foreground">Hire Date</p>
                <p className="text-lg font-medium">
                  {teacher.hire_date
                    ? new Date(teacher.hire_date).toLocaleDateString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric'
                    })
                    : "N/A"
                  }
                </p>
              </div>
            </div>
          </div>

          {/* Classes & Subjects Assignment */}
          <div>
            <h3 className="text-lg font-semibold mb-4">Classes & Subjects Assignment</h3>
            {teacher.teacher_subjects && teacher.teacher_subjects.length > 0 ? (
              <div className="space-y-3">
                {teacher.teacher_subjects.map((assignment: any, idx: number) => (
                  <div key={idx} className="flex items-center gap-3 p-4 bg-muted rounded-lg">
                    <Badge variant="default">{assignment.classes?.name || "Unknown Class"}</Badge>
                    <span className="text-muted-foreground">→</span>
                    <Badge variant="secondary">{assignment.subjects?.name || "Unknown Subject"}</Badge>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-muted-foreground">No class assignments</p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
