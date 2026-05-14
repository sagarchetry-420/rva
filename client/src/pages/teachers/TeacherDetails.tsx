import { useState, useEffect } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "@/lib/api";
import { generateTeacherPDF } from "@/lib/pdfGenerator";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Loader2, ArrowLeft, Download, Settings } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export default function TeacherDetails() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { toast } = useToast();

  const [teacher, setTeacher] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [password, setPassword] = useState<string>("");

  // Status Management State
  const [isStatusDialogOpen, setIsStatusDialogOpen] = useState(false);
  const [updatingStatus, setUpdatingStatus] = useState(false);
  const [newStatus, setNewStatus] = useState<string>("active");
  const [noticeStartDate, setNoticeStartDate] = useState<string>("");
  const [lastWorkingDate, setLastWorkingDate] = useState<string>("");

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

  const handleUpdateStatus = async () => {
    if (newStatus === "on_notice" && (!noticeStartDate || !lastWorkingDate)) {
      toast({
        title: "Validation Error",
        description: "Please provide both notice start date and last working date.",
        variant: "destructive"
      });
      return;
    }

    setUpdatingStatus(true);
    try {
      await api.patch(`/api/teachers/${id}/status`, {
        status: newStatus,
        noticeStartDate: newStatus === "on_notice" ? noticeStartDate : null,
        lastWorkingDate: newStatus === "on_notice" ? lastWorkingDate : null
      });

      toast({
        title: "Success",
        description: "Teacher status updated successfully."
      });
      
      setIsStatusDialogOpen(false);
      fetchTeacherDetails(); // Refresh details
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message || "Failed to update teacher status",
        variant: "destructive"
      });
    } finally {
      setUpdatingStatus(false);
    }
  };

  const openStatusDialog = () => {
    setNewStatus(teacher?.status || "active");
    setNoticeStartDate(teacher?.notice_start_date ? new Date(teacher.notice_start_date).toISOString().split('T')[0] : "");
    setLastWorkingDate(teacher?.last_working_date ? new Date(teacher.last_working_date).toISOString().split('T')[0] : "");
    setIsStatusDialogOpen(true);
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
              <div className="flex items-center gap-3">
                <CardTitle className="text-3xl">
                  {teacher.profiles?.first_name} {teacher.profiles?.last_name}
                </CardTitle>
                {teacher.status === 'on_notice' && (
                  <Badge variant="outline" className="text-amber-600 border-amber-200 bg-amber-50">On Notice</Badge>
                )}
                {teacher.status === 'left' && (
                  <Badge variant="outline" className="text-red-600 border-red-200 bg-red-50">Left</Badge>
                )}
              </div>
              <p className="text-muted-foreground mt-2">Teacher Profile</p>
            </div>
            <div className="flex gap-2">
              <Button onClick={openStatusDialog} variant="outline" className="gap-2">
                <Settings className="w-4 h-4" /> Manage Status
              </Button>
              <Button onClick={handleDownloadPDF} className="gap-2">
                <Download className="w-4 h-4" /> Download PDF
              </Button>
            </div>
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
              {teacher.status === 'on_notice' && teacher.last_working_date && (
                <div className="p-4 bg-amber-50 rounded-lg border border-amber-100">
                  <p className="text-sm text-amber-700">Last Working Date</p>
                  <p className="text-lg font-medium text-amber-900">
                    {new Date(teacher.last_working_date).toLocaleDateString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric'
                    })}
                  </p>
                </div>
              )}
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

      {/* Status Management Dialog */}
      <Dialog open={isStatusDialogOpen} onOpenChange={setIsStatusDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Manage Teacher Status</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>Employment Status</Label>
              <Select value={newStatus} onValueChange={setNewStatus}>
                <SelectTrigger>
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="on_notice">On Notice</SelectItem>
                  <SelectItem value="left">Left School</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {newStatus === "on_notice" && (
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Notice Start Date</Label>
                  <Input 
                    type="date" 
                    value={noticeStartDate} 
                    onChange={(e) => setNoticeStartDate(e.target.value)} 
                  />
                </div>
                <div className="space-y-2">
                  <Label>Last Working Date</Label>
                  <Input 
                    type="date" 
                    value={lastWorkingDate} 
                    onChange={(e) => setLastWorkingDate(e.target.value)} 
                  />
                </div>
              </div>
            )}
            
            {newStatus === "left" && (
              <div className="bg-red-50 text-red-800 p-3 rounded-md text-sm border border-red-200">
                Warning: Marking a teacher as "Left School" will permanently remove all their current class and subject assignments.
              </div>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsStatusDialogOpen(false)} disabled={updatingStatus}>
              Cancel
            </Button>
            <Button onClick={handleUpdateStatus} disabled={updatingStatus}>
              {updatingStatus ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : null}
              Save Changes
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
