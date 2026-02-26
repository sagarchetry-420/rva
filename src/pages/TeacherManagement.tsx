import { useState, useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import { supabase } from "@/integrations/supabase/client";
import { Card, CardContent } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Loader2, Search, UserPlus, Trash2, GraduationCap, ArrowLeft } from "lucide-react";
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
      // Profiles!inner ensures we only get teachers who have an existing profile record
      const { data, error } = await supabase
        .from("teachers")
        .select(`
          id,
          department,
          hire_date,
          profiles!inner (
            first_name, 
            last_name
          )
        `)
        .order('hire_date', { ascending: false });
      
      if (error) throw error;
      setTeachers(data || []);
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
      const { error } = await supabase.from("teachers").delete().eq("id", id);
      if (error) throw error;
      
      toast.success("Teacher record deleted successfully");
      setTeachers(teachers.filter(t => t.id !== id));
    } catch (error: any) {
      toast.error("Could not delete teacher: " + error.message);
    }
  };

  const filteredTeachers = teachers.filter(t => {
    const firstName = t.profiles?.first_name || "";
    const lastName = t.profiles?.last_name || "";
    const fullName = `${firstName} ${lastName}`.toLowerCase();
    const dept = (t.department || "").toLowerCase();
    const query = searchQuery.toLowerCase();
    
    return fullName.includes(query) || dept.includes(query);
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
          <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
            <GraduationCap className="w-8 h-8 text-primary" />
            Teacher Directory
          </h1>
          <p className="text-muted-foreground">Oversee faculty members and their departments.</p>
        </div>
        
        <Button asChild className="gap-2 shadow-md">
          <Link to="/dashboard/teachers/add">
            <UserPlus className="w-4 h-4" /> Add Teacher
          </Link>
        </Button>
      </div>

      {/* Search Bar */}
      <div className="flex items-center gap-4 bg-muted/30 p-4 rounded-lg border">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <Input 
            placeholder="Search by name or department..." 
            className="pl-9 bg-background"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
      </div>

      {/* Table Card */}
      <Card className="border shadow-sm overflow-hidden">
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <Table>
              <TableHeader className="bg-muted/50">
                <TableRow>
                  <TableHead className="font-semibold">Instructor Name</TableHead>
                  <TableHead className="font-semibold">Department</TableHead>
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
                      <TableCell className="font-medium">
                        {teacher.profiles?.first_name} {teacher.profiles?.last_name}
                      </TableCell>
                      <TableCell>
                        <Badge variant="secondary" className="font-medium">
                          {teacher.department || "General Education"}
                        </Badge>
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
                      <TableCell className="text-right">
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