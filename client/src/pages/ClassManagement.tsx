import React, { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
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
  class_id?: string;
  classes?: { name: string } | null;
}

const ClassManagement: React.FC = () => {
  const [classes, setClasses] = useState<ClassRecord[]>([]);
  const [subjects, setSubjects] = useState<SubjectRecord[]>([]);
  const [levels, setLevels] = useState<SchoolLevel[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { toast } = useToast();

  const [newSubject, setNewSubject] = useState({
    name: "",
    code: "",
    classId: "",
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

  const handleSubmitSubject = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!newSubject.name.trim() || !newSubject.code.trim() || !newSubject.classId) {
      toast({ title: "Incomplete Form", description: "Please fill out all subject fields.", variant: "destructive" });
      return;
    }

    setIsSubmitting(true);

    try {
      await api.post('/api/classes/subjects', {
        name: newSubject.name,
        code: newSubject.code,
        classId: newSubject.classId,
      });
      setNewSubject({ name: "", code: "", classId: "" });
      toast({ title: "Success", description: "Subject added successfully." });
      fetchData();
    } catch (error: any) {
      toast({ title: "Save Error", description: error.message, variant: "destructive" });
    } finally {
      setIsSubmitting(false);
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

  // Group classes by school level
  const classesByLevel = levels.map(level => ({
    level,
    classes: classes.filter(cls => cls.school_level_id === level.id)
  })).filter(group => group.classes.length > 0);

  // Get class name by ID
  const getClassName = (classId?: string) => {
    if (!classId) return "N/A";
    const cls = classes.find(c => c.id === classId);
    return cls?.name || "N/A";
  };

  return (
    <div className="min-h-screen bg-slate-50/50 p-4 md:p-8 lg:p-12">
      <div className="max-w-7xl mx-auto space-y-8">

        {/* Page Header */}
        <div className="flex flex-col gap-2">
          <div className="flex items-center gap-3">
            <div className="p-2.5 bg-primary/10 rounded-xl shadow-sm border border-primary/10">
              <School className="w-6 h-6 text-primary" />
            </div>
            <h1 className="text-3xl font-bold tracking-tight text-slate-900">Class & Subject Management</h1>
          </div>
          <p className="text-muted-foreground ml-[3.25rem]">
            View classes and manage subjects for each class.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">

          {/* Left Column - Classes Overview (Read-only) */}
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <School className="w-5 h-5" /> Classes Overview
                </CardTitle>
                <CardDescription>Classes organized by school level</CardDescription>
              </CardHeader>
              <CardContent>
                {loading ? (
                  <div className="flex justify-center p-8">
                    <Loader2 className="animate-spin" />
                  </div>
                ) : classesByLevel.length === 0 ? (
                  <p className="text-center text-muted-foreground py-4">No classes available.</p>
                ) : (
                  <div className="space-y-4">
                    {classesByLevel.map(({ level, classes: levelClasses }) => (
                      <div key={level.id} className="space-y-2">
                        <h4 className="font-semibold text-sm text-slate-700 border-b pb-1">
                          {level.name}
                        </h4>
                        <div className="flex flex-wrap gap-2">
                          {levelClasses.map((cls) => (
                            <Badge key={cls.id} variant="secondary" className="px-3 py-1">
                              {cls.name}
                            </Badge>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Add Subject Form */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Plus className="w-5 h-5" /> Add New Subject
                </CardTitle>
                <CardDescription>Create a subject and assign it to a class</CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleSubmitSubject} className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="classSelect">Assign to Class</Label>
                    <Select
                      value={newSubject.classId}
                      onValueChange={(val) => setNewSubject({ ...newSubject, classId: val })}
                    >
                      <SelectTrigger className="bg-white">
                        <SelectValue placeholder="Select a class" />
                      </SelectTrigger>
                      <SelectContent>
                        {classesByLevel.map(({ level, classes: levelClasses }) => (
                          <React.Fragment key={level.id}>
                            <div className="px-2 py-1.5 text-xs font-semibold text-muted-foreground bg-slate-50">
                              {level.name}
                            </div>
                            {levelClasses.map((cls) => (
                              <SelectItem key={cls.id} value={cls.id}>
                                {cls.name}
                              </SelectItem>
                            ))}
                          </React.Fragment>
                        ))}
                      </SelectContent>
                    </Select>
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

                  <Button type="submit" className="w-full" disabled={isSubmitting}>
                    {isSubmitting ? (
                      <Loader2 className="animate-spin mr-2 w-4 h-4" />
                    ) : (
                      <Plus className="mr-2 w-4 h-4" />
                    )}
                    Add Subject
                  </Button>
                </form>
              </CardContent>
            </Card>
          </div>

          {/* Right Column - Subjects Table */}
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <BookOpen className="w-5 h-5" /> Subjects
              </CardTitle>
              <CardDescription>All subjects with their assigned classes</CardDescription>
            </CardHeader>
            <CardContent>
              {loading ? (
                <div className="flex justify-center p-8">
                  <Loader2 className="animate-spin" />
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Subject Name</TableHead>
                      <TableHead>Code</TableHead>
                      <TableHead>Class</TableHead>
                      <TableHead className="text-right w-20">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {subjects.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={4} className="text-center text-muted-foreground py-8">
                          No subjects found. Add a subject to get started.
                        </TableCell>
                      </TableRow>
                    ) : (
                      subjects.map((sub) => (
                        <TableRow key={sub.id}>
                          <TableCell className="font-medium">{sub.name}</TableCell>
                          <TableCell>
                            <Badge variant="outline">{sub.code}</Badge>
                          </TableCell>
                          <TableCell>
                            {sub.classes?.name || getClassName(sub.class_id)}
                          </TableCell>
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
