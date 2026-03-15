import { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { ArrowLeft, UserPlus, Search, Loader2, Trash2, User } from "lucide-react";
import { toast } from "sonner";

export default function StudentManagement() {
  const [students, setStudents] = useState<any[]>([]);
  const [classes, setClasses] = useState<any[]>([]);
  const [selectedClass, setSelectedClass] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    fetchInitialData();
  }, []);

  useEffect(() => {
    fetchStudents();
  }, [selectedClass]);

  async function fetchInitialData() {
    try {
      const data = await api.get<any[]>('/api/classes/simple');
      setClasses(data);
    } catch {
      toast.error("Failed to load classes");
    }
  }

  async function fetchStudents() {
    setLoading(true);
    try {
<<<<<<< HEAD:src/pages/StudentManagement.tsx
      // Using !inner tells Supabase to only return students who HAVE a profile
    // Changing !inner to a standard join (Left Join)
let query = supabase.from("students").select(`
  id, 
  user_id,
  enrollment_date,
  profiles (
    first_name, 
    last_name,
    avatar_url
  ),
  classes (
    name
  )
`);
      
      if (selectedClass !== "all") {
        query = query.eq("class_id", selectedClass);
      }

      const { data, error } = await query;
      
      if (error) throw error;
      setStudents(data || []);
=======
      const classParam = selectedClass !== "all" ? `?class_id=${selectedClass}` : '';
      const data = await api.get<any[]>(`/api/students${classParam}`);
      setStudents(data);
>>>>>>> testing:client/src/pages/StudentManagement.tsx
    } catch (error: any) {
      console.error("Fetch Error:", error);
      toast.error(error.message);
    } finally {
      setLoading(false);
    }
  }

  const handleDelete = async (studentId: string, userId: string) => {
    if (!confirm("Are you sure? This will remove the student record, but keep the Auth account. Admin manual cleanup may be required in Auth.")) return;

    try {
      await api.delete(`/api/students/${studentId}`);
      toast.success("Student removed from directory");
      setStudents(students.filter(s => s.id !== studentId));
    } catch (error: any) {
      toast.error("Delete failed: " + error.message);
    }
  };

  // Filter students based on search query (client-side for speed)
  const filteredStudents = students.filter(s => {
    const fullName = `${s.profiles?.first_name} ${s.profiles?.last_name}`.toLowerCase();
    return fullName.includes(searchQuery.toLowerCase());
  });

  return (
    <div className="p-4 md:p-8 max-w-7xl mx-auto space-y-6">
      {/* Header Section */}
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <Button 
            variant="ghost" 
            onClick={() => navigate("/dashboard")} 
            className="mb-2 p-0 h-auto hover:bg-transparent text-muted-foreground hover:text-primary"
          >
            <ArrowLeft className="w-4 h-4 mr-1" /> Back to Dashboard
          </Button>
          <h1 className="text-3xl font-bold tracking-tight">Student Directory</h1>
          <p className="text-muted-foreground">Manage and view all enrolled students.</p>
        </div>
        
        <div className="flex items-center gap-3 w-full md:w-auto">
          <Button asChild className="gap-2 w-full md:w-auto shadow-sm">
            <Link to="/dashboard/students/add">
              <UserPlus className="w-4 h-4" /> Add Student
            </Link>
          </Button>
        </div>
      </div>

      {/* Filters Bar */}
      <div className="flex flex-col md:flex-row gap-4 items-center justify-between bg-muted/30 p-4 rounded-lg border">
        <div className="relative w-full md:w-96">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <Input 
            placeholder="Search student names..." 
            className="pl-9"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>

        <div className="flex items-center gap-2 w-full md:w-auto">
          <span className="text-sm font-medium text-muted-foreground whitespace-nowrap">Filter:</span>
          <Select onValueChange={setSelectedClass} defaultValue="all">
            <SelectTrigger className="w-full md:w-[200px] bg-background">
              <SelectValue placeholder="All Classes" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Classes</SelectItem>
              {classes.map((c) => (
                <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Main Table */}
      <Card className="border shadow-sm overflow-hidden">
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <Table>
              <TableHeader className="bg-muted/50">
                <TableRow>
                  <TableHead className="w-[300px]">Student Name</TableHead>
                  <TableHead>Assigned Class</TableHead>
                  <TableHead>Enrollment Date</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  <TableRow>
                    <TableCell colSpan={4} className="h-48 text-center">
                      <div className="flex flex-col items-center gap-2">
                        <Loader2 className="w-8 h-8 animate-spin text-primary" />
                        <p className="text-sm text-muted-foreground">Retrieving records...</p>
                      </div>
                    </TableCell>
                  </TableRow>
                ) : filteredStudents.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={4} className="h-48 text-center">
                      <div className="flex flex-col items-center gap-2">
                        <Search className="w-10 h-10 text-muted-foreground/20" />
                        <p className="font-medium">No students found</p>
                        <p className="text-sm text-muted-foreground">Try adjusting your filters or search terms.</p>
                      </div>
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredStudents.map((student) => (
                    <TableRow key={student.id} className="hover:bg-muted/30 transition-colors">
                      <TableCell className="font-medium">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                            <User className="w-4 h-4 text-primary" />
                          </div>
                          <span>
                            {student.profiles?.first_name} {student.profiles?.last_name}
                          </span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <span className="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-secondary/50">
                          {student.classes?.name || "Unassigned"}
                        </span>
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {new Date(student.enrollment_date).toLocaleDateString(undefined, {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric'
                        })}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button 
                          variant="ghost" 
                          size="icon" 
                          className="text-muted-foreground hover:text-destructive"
                          onClick={() => handleDelete(student.id, student.user_id)}
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
      
      {/* Footer Info */}
      <div className="text-xs text-muted-foreground px-2">
        Showing {filteredStudents.length} of {students.length} students
      </div>
    </div>
  );
}