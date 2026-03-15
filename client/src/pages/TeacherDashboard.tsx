import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { useAuth } from "@/hooks/useAuth";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { Loader2, CheckCircle, XCircle, Clock, Calendar as CalendarIcon } from "lucide-react";
import { Label } from "recharts";

export default function TeacherAttendance() {
  const { user } = useAuth();
  const { toast } = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  
  // Data State
  const [assignedClasses, setAssignedClasses] = useState<any[]>([]);
  const [selectedClass, setSelectedClass] = useState<string>("");
  const [students, setStudents] = useState<any[]>([]);
  const [attendance, setAttendance] = useState<Record<string, string>>({});

  const today = new Date().toISOString().split('T')[0];

  useEffect(() => {
    if (user) fetchTeacherClasses();
  }, [user]);

  // 1. Fetch classes assigned to this teacher
 const fetchTeacherClasses = async () => {
  try {
    setLoading(true);
    const data = await api.get<any[]>('/api/attendance/teacher-classes');
    setAssignedClasses(data);
  } catch (error: any) {
    console.error("Critical Fetch Error:", error);
    toast({ title: "Fetch Error", description: error.message, variant: "destructive" });
  } finally {
    setLoading(false);
  }
};
  // 2. Fetch students when a class is selected
  const fetchStudents = async (classId: string) => {
    setSelectedClass(classId);
    setLoading(true);
    try {
      const data = await api.get<any[]>(`/api/attendance/students?class_id=${classId}`);
      setStudents(data || []);

      // Initialize attendance state as 'Present' for everyone
      const initialAttendance: Record<string, string> = {};
      data?.forEach(s => initialAttendance[s.id] = 'Present');
      setAttendance(initialAttendance);
    } finally {
      setLoading(false);
    }
  };

  // 3. Save attendance to DB
  const saveAttendance = async () => {
    setSaving(true);
    try {
      const records = Object.entries(attendance).map(([studentId, status]) => ({
        studentId,
        status,
      }));

      await api.post('/api/attendance', { records, date: today });

      toast({ title: "Success", description: "Attendance recorded for " + today });
    } catch (error: any) {
      toast({ title: "Error", description: error.message, variant: "destructive" });
    } finally {
      setSaving(false);
    }
  };

  if (loading && assignedClasses.length === 0) {
    return <div className="flex justify-center p-12"><Loader2 className="animate-spin" /></div>;
  }

  return (
    <div className="container mx-auto py-8 space-y-6">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold">Class Attendance</h1>
          <p className="text-muted-foreground flex items-center gap-2">
            <CalendarIcon className="w-4 h-4" /> {today}
          </p>
        </div>

        <div className="w-full md:w-64">
          <Label>Select Class</Label>
          <Select onValueChange={fetchStudents}>
            <SelectTrigger>
              <SelectValue placeholder="Choose a class" />
            </SelectTrigger>
            <SelectContent>
              {assignedClasses.map(c => (
                <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {selectedClass && (
        <Card className="shadow-md">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Student List ({students.length})</CardTitle>
            <Button onClick={saveAttendance} disabled={saving}>
              {saving ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle className="mr-2 h-4 w-4" />}
              Submit Attendance
            </Button>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Student Name</TableHead>
                  <TableHead className="text-center">Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {students.map((student) => (
                  <TableRow key={student.id}>
                    <TableCell className="font-medium">
                      {student.profiles.first_name} {student.profiles.last_name}
                    </TableCell>
                    <TableCell>
                      <div className="flex justify-center gap-2">
                        <Button 
                          size="sm" 
                          variant={attendance[student.id] === 'Present' ? 'default' : 'outline'}
                          className={attendance[student.id] === 'Present' ? 'bg-emerald-600 hover:bg-emerald-700' : ''}
                          onClick={() => setAttendance({...attendance, [student.id]: 'Present'})}
                        >
                          Present
                        </Button>
                        <Button 
                          size="sm" 
                          variant={attendance[student.id] === 'Absent' ? 'destructive' : 'outline'}
                          onClick={() => setAttendance({...attendance, [student.id]: 'Absent'})}
                        >
                          Absent
                        </Button>
                        <Button 
                          size="sm" 
                          variant={attendance[student.id] === 'Late' ? 'secondary' : 'outline'}
                          className={attendance[student.id] === 'Late' ? 'bg-amber-500 text-white hover:bg-amber-600' : ''}
                          onClick={() => setAttendance({...attendance, [student.id]: 'Late'})}
                        >
                          Late
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}
    </div>
  );
}