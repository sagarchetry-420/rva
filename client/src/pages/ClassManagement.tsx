import React, { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useToast } from "@/hooks/use-toast";
import { Loader2, Plus, Trash2, School, BookOpen } from "lucide-react";

interface SchoolLevel {
  id: string;
  name: string;
}

interface ClassRecord {
  id: string;
  name: string;
  school_level_id: string;
  school_levels: { name: string } | null;
}

interface SubjectRecord {
  id: string;
  name: string;
  code: string;
}

const ClassManagement: React.FC = () => {
  const [classes, setClasses] = useState<ClassRecord[]>([]);
  const [subjects, setSubjects] = useState<SubjectRecord[]>([]);
  const [levels, setLevels] = useState<SchoolLevel[]>([]);
  
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { toast } = useToast();

  const [newClass, setNewClass] = useState({
    name: "",
    levelId: "",
  });

  const [newSubject, setNewSubject] = useState({
    name: "",
    code: "",
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [classesData, levelsData, subjectsData] = await Promise.all([
        api.get<ClassRecord[]>('/api/classes'),
        api.get<SchoolLevel[]>('/api/classes/school-levels'),
        api.get<SubjectRecord[]>('/api/classes/subjects'),
      ]);

      setClasses(classesData);
      setLevels(levelsData);
      setSubjects(subjectsData);
    } catch (error: any) {
      toast({ title: "Fetch failed", description: error.message, variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
  e.preventDefault();
  
  const isClassFilled = newClass.name.trim() !== "" && newClass.levelId !== "";
  const isSubjectFilled = newSubject.name.trim() !== "" && newSubject.code.trim() !== "";

  if (!isClassFilled && !isSubjectFilled) {
    toast({ title: "Incomplete Form", description: "Please fill out a record.", variant: "destructive" });
    return;
  }

  setIsSubmitting(true);
  
  try {
    if (isClassFilled) {
      await api.post('/api/classes', { name: newClass.name, schoolLevelId: newClass.levelId });
      setNewClass({ name: "", levelId: "" });
    }

    if (isSubjectFilled) {
      console.log("Saving subject:", newSubject);
      await api.post('/api/classes/subjects', { name: newSubject.name, code: newSubject.code });
      setNewSubject({ name: "", code: "" });
    }

    toast({ title: "Success", description: "Records updated." });
    fetchData();
    
  } catch (error: any) {
    console.error("Submission failed:", error);
    toast({ title: "Save Error", description: error.message, variant: "destructive" });
  } finally {
    setIsSubmitting(false);
  }
};

  const handleDeleteClass = async (id: string) => {
    if (!confirm("Are you sure you want to delete this class?")) return;

    try {
      await api.delete(`/api/classes/${id}`);
      setClasses(classes.filter((c) => c.id !== id));
      toast({ title: "Deleted", description: "Class removed successfully." });
    } catch (error: any) {
      toast({ title: "Delete failed", description: error.message, variant: "destructive" });
    }
  };

  const handleDeleteSubject = async (id: string) => {
    if (!confirm("Are you sure you want to delete this subject?")) return;

    try {
      await api.delete(`/api/classes/subjects/${id}`);
      setSubjects(subjects.filter((s) => s.id !== id));
      toast({ title: "Deleted", description: "Subject removed successfully." });
    } catch (error: any) {
      toast({ title: "Delete failed", description: error.message, variant: "destructive" });
    }
  };

  return (
    <div className="container mx-auto p-6 space-y-8">
      <div className="flex items-center gap-2 mb-6">
        <School className="w-8 h-8 text-primary" />
        <h1 className="text-3xl font-bold">School Structure</h1>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Consolidated Form Section */}
        <Card className="lg:col-span-1 h-fit">
          <CardHeader>
            <CardTitle>Add New Records</CardTitle>
            <CardDescription>Create a new class, a new subject, or both.</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              
              {/* Class Inputs */}
              <div className="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                <div className="flex items-center gap-2 font-semibold text-slate-700">
                  <School className="w-4 h-4" /> Class Details
                </div>
                <div className="space-y-2">
                  <Label htmlFor="className">Class Name</Label>
                  <Input
                    id="className"
                    placeholder="e.g. Grade 10-A"
                    value={newClass.name}
                    onChange={(e) => setNewClass({ ...newClass, name: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>School Level</Label>
                  <Select
                    value={newClass.levelId}
                    onValueChange={(val) => setNewClass({ ...newClass, levelId: val })}
                  >
                    <SelectTrigger className="bg-white">
                      <SelectValue placeholder="Select Level" />
                    </SelectTrigger>
                    <SelectContent>
                      {levels.map((level) => (
                        <SelectItem key={level.id} value={level.id}>
                          {level.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              {/* Subject Inputs */}
              <div className="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                <div className="flex items-center gap-2 font-semibold text-slate-700">
                  <BookOpen className="w-4 h-4" /> Subject Details
                </div>
                <div className="space-y-2">
                  <Label htmlFor="subjectName">Subject Name</Label>
                  <Input
                    id="subjectName"
                    placeholder="e.g. Advanced Calculus"
                    value={newSubject.name}
                    onChange={(e) => setNewSubject({ ...newSubject, name: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="subjectCode">Subject Code</Label>
                  <Input
                    id="subjectCode"
                    placeholder="e.g. MATH-401"
                    value={newSubject.code}
                    onChange={(e) => setNewSubject({ ...newSubject, code: e.target.value })}
                  />
                </div>
              </div>

              <Button type="submit" className="w-full" disabled={isSubmitting}>
                {isSubmitting ? <Loader2 className="animate-spin mr-2 w-4 h-4" /> : <Plus className="mr-2 w-4 h-4" />}
                Save Records
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* Data Tables Section */}
        <div className="lg:col-span-2 space-y-8">
          
          {/* Classes Table */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <School className="w-5 h-5" /> Existing Classes
              </CardTitle>
            </CardHeader>
            <CardContent>
              {loading ? (
                <div className="flex justify-center p-8"><Loader2 className="animate-spin" /></div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Class Name</TableHead>
                      <TableHead>Level</TableHead>
                      <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {classes.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={3} className="text-center text-muted-foreground">
                          No classes found.
                        </TableCell>
                      </TableRow>
                    ) : (
                      classes.map((cls) => (
                        <TableRow key={cls.id}>
                          <TableCell className="font-medium">{cls.name}</TableCell>
                          <TableCell>{cls.school_levels?.name || "N/A"}</TableCell>
                          <TableCell className="text-right">
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-destructive hover:text-destructive hover:bg-destructive/10"
                              onClick={() => handleDeleteClass(cls.id)}
                            >
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>

          {/* Subjects Table */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <BookOpen className="w-5 h-5" /> Existing Subjects
              </CardTitle>
            </CardHeader>
            <CardContent>
              {loading ? (
                <div className="flex justify-center p-8"><Loader2 className="animate-spin" /></div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Subject Name</TableHead>
                      <TableHead>Code</TableHead>
                      <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {subjects.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={3} className="text-center text-muted-foreground">
                          No subjects found.
                        </TableCell>
                      </TableRow>
                    ) : (
                      subjects.map((sub) => (
                        <TableRow key={sub.id}>
                          <TableCell className="font-medium">{sub.name}</TableCell>
                          <TableCell>{sub.code}</TableCell>
                          <TableCell className="text-right">
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-destructive hover:text-destructive hover:bg-destructive/10"
                              onClick={() => handleDeleteSubject(sub.id)}
                            >
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
          
        </div>
      </div>
    </div>
  );
};

export default ClassManagement;