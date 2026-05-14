import { useState, useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import { api } from "@/lib/api";
import { generateTeacherPDF, generateAllTeachersPDF } from "@/lib/pdfGenerator";
import { Card, CardContent } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Loader2, Search, UserPlus, Trash2, GraduationCap, ArrowLeft, Eye, Download } from "lucide-react";
import { toast } from "sonner";

export default function TeacherManagement() {
  const [teachers, setTeachers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");
  const navigate = useNavigate();

  useEffect(() => {
    fetchTeachers();
  }, []);

  async function fetchTeachers() {
    setLoading(true);
    try {
      const data = await api.get<any[]>('/api/teachers');
      setTeachers(data);
    } catch (error: any) {
      console.error("Fetch error:", error);
      toast.error("Failed to load teachers: " + error.message);
    } finally {
      setLoading(false);
    }
  }

  const handleDelete = async (id: string) => {
    if (!confirm("Are you sure you want to remove this teacher? This action cannot be undone.")) return;

    try {
      await api.delete(`/api/teachers/${id}`);
      toast.success("Teacher record deleted successfully");
      setTeachers(teachers.filter(t => t.id !== id));
    } catch (error: any) {
      toast.error("Could not delete teacher: " + error.message);
    }
  };

  const handleDownloadTeacher = (teacher: any) => {
    try {
      // Pass empty password to PDF - don't show passwords in downloaded PDFs
      // Passwords should only be shared at enrollment time
      generateTeacherPDF(teacher, "");
      toast.success("PDF downloaded successfully");
      toast.info("Tip: Original credentials were provided at enrollment time");
    } catch (error: any) {
      toast.error("Failed to download PDF: " + error.message);
    }
  };

  const handleDownloadAllTeachers = () => {
    try {
      if (filteredTeachers.length === 0) {
        toast.error("No teachers to download");
        return;
      }
      generateAllTeachersPDF(filteredTeachers);
      toast.success(`Downloaded details for ${filteredTeachers.length} teachers`);
    } catch (error: any) {
      toast.error("Failed to download PDF: " + error.message);
    }
  };

  const filteredTeachers = teachers.filter(t => {
    const firstName = t.profiles?.first_name || "";
    const lastName = t.profiles?.last_name || "";
    const fullName = `${firstName} ${lastName}`.toLowerCase();

    // Also search by class names
    const classNames = (t.teacher_subjects || [])
      .map((ts: any) => ts.classes?.name || "")
      .join(" ")
      .toLowerCase();

    const query = searchQuery.toLowerCase();

    return fullName.includes(query) || classNames.includes(query);
  });

  return (
    <div className="p-4 md:p-8 max-w-7xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <Button
            variant="ghost"
            onClick={() => navigate("/dashboard")}
            className="mb-2 p-0 h-auto hover:bg-transparent text-muted-foreground hover:text-primary"
          >
            <ArrowLeft className="w-4 h-4 mr-1" /> Back to Dashboard
          </Button>
          <h1 className="text-2xl md:text-3xl font-bold tracking-tight flex items-center gap-2">
            <GraduationCap className="w-6 md:w-8 h-6 md:h-8 text-primary" />
            Teacher Directory
          </h1>
          <p className="text-xs md:text-sm text-muted-foreground">Oversee faculty members and their departments.</p>
        </div>

        <div className="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
          <Button
            variant="outline"
            className="gap-2 w-full sm:w-auto"
            onClick={handleDownloadAllTeachers}
            disabled={teachers.length === 0}
          >
            <Download className="w-4 h-4" /> Download All
          </Button>
          <Button asChild className="gap-2 shadow-md w-full sm:w-auto">
            <Link to="/dashboard/teachers/add">
              <UserPlus className="w-4 h-4" /> Add Teacher
            </Link>
          </Button>
        </div>
      </div>

      {/* Search Bar */}
      <div className="flex items-center gap-4 bg-muted/30 p-3 md:p-4 rounded-lg border">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <Input
            placeholder="Search by name or class..."
            className="pl-9 bg-background text-sm"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
      </div>

      {/* Mobile Card View */}
      <div className="md:hidden space-y-3">
        {loading ? (
          <div className="flex flex-col items-center justify-center gap-2 py-12">
            <Loader2 className="w-8 h-8 animate-spin text-primary" />
            <p className="text-sm text-muted-foreground font-medium">Loading faculty records...</p>
          </div>
        ) : filteredTeachers.length === 0 ? (
          <div className="flex flex-col items-center justify-center gap-2 py-12">
            <Search className="w-10 h-10 opacity-20" />
            <p className="text-sm text-muted-foreground">No teachers found matching your search.</p>
          </div>
        ) : (
          filteredTeachers.map((teacher) => (
            <Card key={teacher.id} className="shadow-sm border overflow-hidden">
              <CardContent className="p-4 space-y-3">
                {/* Teacher Name */}
                  <div className="flex items-center gap-2">
                    <h3 className="font-semibold text-base">
                      {teacher.profiles?.first_name} {teacher.profiles?.last_name}
                    </h3>
                    {teacher.status === 'on_notice' && (
                      <Badge variant="outline" className="text-amber-600 border-amber-200 bg-amber-50 text-[10px]">On Notice</Badge>
                    )}
                    {teacher.status === 'left' && (
                      <Badge variant="outline" className="text-red-600 border-red-200 bg-red-50 text-[10px]">Left</Badge>
                    )}
                    {teacher.status === 'active' && teacher.resignation_document_url && (
                      <Badge variant="outline" className="text-blue-600 border-blue-200 bg-blue-50 text-[10px]">Pending Resignation</Badge>
                    )}
                  </div>

                {/* Classes & Subjects */}
                {(teacher.teacher_subjects || []).length > 0 && (
                  <div className="space-y-2">
                    <p className="text-xs font-medium text-muted-foreground uppercase">Classes & Subjects</p>
                    <div className="space-y-1">
                      {teacher.teacher_subjects.slice(0, 2).map((ts: any, idx: number) => (
                        <div key={idx} className="text-xs flex items-center gap-2">
                          <Badge variant="secondary" className="text-xs">
                            {ts.classes?.name || "Unknown"}
                          </Badge>
                          <span className="text-muted-foreground">
                            {ts.subjects?.name || "N/A"}
                          </span>
                        </div>
                      ))}
                      {(teacher.teacher_subjects || []).length > 2 && (
                        <p className="text-xs text-muted-foreground">
                          +{(teacher.teacher_subjects || []).length - 2} more
                        </p>
                      )}
                    </div>
                  </div>
                )}

                {/* Hire Date */}
                <div className="flex items-center justify-between pt-2 border-t">
                  <span className="text-xs text-muted-foreground">Hired:</span>
                  <span className="text-xs font-medium">
                    {teacher.hire_date
                      ? new Date(teacher.hire_date).toLocaleDateString(undefined, {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric'
                        })
                      : "N/A"
                    }
                  </span>
                </div>

                {/* Actions */}
                <div className="flex gap-2 pt-2">
                  <Button
                    variant="outline"
                    size="sm"
                    className="flex-1 gap-2 text-xs h-8"
                    onClick={() => navigate(`/dashboard/teachers/${teacher.id}`)}
                  >
                    <Eye className="w-3 h-3" />
                    View
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    className="gap-2 text-xs h-8 px-2"
                    onClick={() => handleDownloadTeacher(teacher)}
                  >
                    <Download className="w-3 h-3" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="text-muted-foreground hover:text-destructive hover:bg-destructive/10 h-8 w-8 p-0"
                    onClick={() => handleDelete(teacher.id)}
                  >
                    <Trash2 className="w-4 h-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {/* Desktop Table View */}
      <Card className="border shadow-sm overflow-hidden hidden md:block">
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <Table>
              <TableHeader className="bg-muted/50">
                <TableRow>
                  <TableHead className="font-semibold">Instructor Name</TableHead>
                  <TableHead className="font-semibold">Classes & Subjects</TableHead>
                  <TableHead className="font-semibold">Hire Date</TableHead>
                  <TableHead className="text-right font-semibold">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  <TableRow>
                    <TableCell colSpan={4} className="h-48 text-center">
                      <div className="flex flex-col items-center justify-center gap-2">
                        <Loader2 className="w-8 h-8 animate-spin text-primary" />
                        <p className="text-sm text-muted-foreground font-medium">Loading faculty records...</p>
                      </div>
                    </TableCell>
                  </TableRow>
                ) : filteredTeachers.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="h-48 text-center text-muted-foreground">
                      <div className="flex flex-col items-center justify-center gap-2">
                        <Search className="w-10 h-10 opacity-20" />
                        <p>No teachers found matching your search.</p>
                      </div>
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredTeachers.map((teacher) => (
                    <TableRow key={teacher.id} className="hover:bg-muted/30 transition-colors">
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <span className="font-medium">
                            {teacher.profiles?.first_name} {teacher.profiles?.last_name}
                          </span>
                          {teacher.status === 'on_notice' && (
                            <Badge variant="outline" className="text-amber-600 border-amber-200 bg-amber-50">On Notice</Badge>
                          )}
                          {teacher.status === 'left' && (
                            <Badge variant="outline" className="text-red-600 border-red-200 bg-red-50">Left</Badge>
                          )}
                          {teacher.status === 'active' && teacher.resignation_document_url && (
                            <Badge variant="outline" className="text-blue-600 border-blue-200 bg-blue-50">Pending Resignation</Badge>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        {(teacher.teacher_subjects || []).length > 0 ? (
                          <div className="space-y-1">
                            {teacher.teacher_subjects.map((ts: any, idx: number) => (
                              <div key={idx} className="text-sm">
                                <Badge variant="secondary" className="mr-2">
                                  {ts.classes?.name || "Unknown Class"}
                                </Badge>
                                <span className="text-xs text-muted-foreground">
                                  {ts.subjects?.name || "Unknown Subject"}
                                </span>
                              </div>
                            ))}
                          </div>
                        ) : (
                          <span className="text-muted-foreground text-sm">No assignments</span>
                        )}
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {teacher.hire_date
                          ? new Date(teacher.hire_date).toLocaleDateString(undefined, {
                              year: 'numeric',
                              month: 'long',
                              day: 'numeric'
                            })
                          : "N/A"
                        }
                      </TableCell>
                      <TableCell className="text-right space-x-2 flex justify-end">
                        <Button
                          variant="outline"
                          size="sm"
                          className="gap-2"
                          onClick={() => navigate(`/dashboard/teachers/${teacher.id}`)}
                        >
                          <Eye className="w-4 h-4" />
                          View Details
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          className="gap-2"
                          onClick={() => handleDownloadTeacher(teacher)}
                        >
                          <Download className="w-4 h-4" />
                          Download
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                          onClick={() => handleDelete(teacher.id)}
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <div className="text-xs text-muted-foreground px-2">
        Total Teachers: {filteredTeachers.length}
      </div>
    </div>
  );
}