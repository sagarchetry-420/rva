import React, { useEffect, useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useToast } from "@/hooks/use-toast";
<<<<<<< HEAD
import { Loader2, Plus, Trash2, School, LayoutGrid } from "lucide-react";
=======
import { Loader2, Plus, Trash2, School, BookOpen } from "lucide-react";
>>>>>>> testing

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
      const [classesRes, levelsRes, subjectsRes] = await Promise.all([
        supabase.from("classes").select("*, school_levels(name)").order("name"),
        supabase.from("school_levels").select("id, name").order("name"),
        supabase.from("subjects").select("*").order("name"),
      ]);

      if (classesRes.error) throw classesRes.error;
      if (levelsRes.error) throw levelsRes.error;
      if (subjectsRes.error) throw subjectsRes.error;

      // Debugging log to ensure data is actually coming back from Supabase
      console.log("Fetched School Levels:", levelsRes.data);

      setClasses(classesRes.data || []);
      setLevels(levelsRes.data || []);
      setSubjects(subjectsRes.data || []);
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
      const { error: clsErr } = await supabase
        .from("classes")
        .insert([{ name: newClass.name, school_level_id: newClass.levelId }]);
      if (clsErr) throw new Error(`Class Error: ${clsErr.message}`);
      setNewClass({ name: "", levelId: "" });
    }

    if (isSubjectFilled) {
      // 🕵️ DEBUG: Log the data being sent
      console.log("Saving subject:", newSubject);
      
      const { error: subErr } = await supabase
        .from("subjects")
        .insert([{ name: newSubject.name, code: newSubject.code }]);
      
      if (subErr) throw new Error(`Subject Error: ${subErr.message}`);
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

    const { error } = await supabase.from("classes").delete().eq("id", id);
    if (error) {
      toast({ title: "Delete failed", description: error.message, variant: "destructive" });
    } else {
      setClasses(classes.filter((c) => c.id !== id));
      toast({ title: "Deleted", description: "Class removed successfully." });
    }
  };

  const handleDeleteSubject = async (id: string) => {
    if (!confirm("Are you sure you want to delete this subject?")) return;

    const { error } = await supabase.from("subjects").delete().eq("id", id);
    if (error) {
      toast({ title: "Delete failed", description: error.message, variant: "destructive" });
    } else {
      setSubjects(subjects.filter((s) => s.id !== id));
      toast({ title: "Deleted", description: "Subject removed successfully." });
    }
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
            <h1 className="text-3xl font-bold tracking-tight text-slate-900">School Structure</h1>
          </div>
          <p className="text-muted-foreground ml-[3.25rem]">
            Manage all educational levels and active classes within your institution.
          </p>
        </div>

<<<<<<< HEAD
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* Form Section */}
          <Card className="lg:col-span-4 shadow-sm border-slate-200/60 sticky top-8">
            <CardHeader className="bg-slate-50/50 border-b border-slate-100 rounded-t-xl pb-4">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Plus className="w-5 h-5 text-primary" />
                Add New Class
              </CardTitle>
              <CardDescription>Create a new classroom and assign it to a level.</CardDescription>
            </CardHeader>
            <CardContent className="pt-6">
              <form onSubmit={handleCreateClass} className="space-y-5">
                <div className="space-y-2.5">
                  <Label htmlFor="className" className="text-slate-700 font-medium">Class Name</Label>
=======
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
>>>>>>> testing
                  <Input
                    id="className"
                    placeholder="e.g. Grade 10-A"
                    value={newClass.name}
                    onChange={(e) => setNewClass({ ...newClass, name: e.target.value })}
<<<<<<< HEAD
                    className="focus-visible:ring-primary/50"
                    required
                  />
                </div>
                <div className="space-y-2.5">
                  <Label className="text-slate-700 font-medium">School Level</Label>
                  <Select
                    value={newClass.levelId || undefined}
                    onValueChange={(val) => setNewClass({ ...newClass, levelId: val })}
                    required
                  >
                    <SelectTrigger className="focus:ring-primary/50">
                      <SelectValue placeholder="Select educational level..." />
                    </SelectTrigger>
                    <SelectContent>
                      {levels.map((level) => (
                        <SelectItem key={level.id} value={level.id} className="cursor-pointer">
=======
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
>>>>>>> testing
                          {level.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
<<<<<<< HEAD
                <Button 
                  type="submit" 
                  className="w-full shadow-sm transition-all hover:shadow-md mt-2" 
                  disabled={isSubmitting}
                >
                  {isSubmitting ? (
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  ) : (
                    <Plus className="w-4 h-4 mr-2" />
                  )}
                  {isSubmitting ? "Creating..." : "Create Class"}
                </Button>
              </form>
            </CardContent>
          </Card>

          {/* Table Section */}
          <Card className="lg:col-span-8 shadow-sm border-slate-200/60">
            <CardHeader className="bg-slate-50/50 border-b border-slate-100 rounded-t-xl pb-4 flex flex-row items-center justify-between">
              <div>
                <CardTitle className="flex items-center gap-2 text-lg">
                  <LayoutGrid className="w-5 h-5 text-primary" />
                  Existing Classes
                </CardTitle>
                <CardDescription className="mt-1">A complete list of all registered classes.</CardDescription>
              </div>
              {/* Optional: Add a badge showing total count */}
              {!loading && classes.length > 0 && (
                <div className="px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full">
                  {classes.length} Total
                </div>
              )}
            </CardHeader>
            <CardContent className="p-0">
              {loading ? (
                <div className="flex flex-col items-center justify-center p-12 text-muted-foreground">
                  <Loader2 className="w-8 h-8 animate-spin text-primary/50 mb-4" />
                  <p className="text-sm font-medium">Loading classes...</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow className="hover:bg-transparent border-b-slate-200">
                      <TableHead className="w-[40%] font-semibold text-slate-900 pl-6">Class Name</TableHead>
                      <TableHead className="w-[40%] font-semibold text-slate-900">Level</TableHead>
                      <TableHead className="w-[20%] text-right font-semibold text-slate-900 pr-6">Actions</TableHead>
=======
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
>>>>>>> testing
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {classes.length === 0 ? (
<<<<<<< HEAD
                      <TableRow className="hover:bg-transparent">
                        <TableCell colSpan={3} className="h-64 text-center">
                          <div className="flex flex-col items-center justify-center space-y-3">
                            <div className="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-2">
                              <School className="w-8 h-8 text-slate-400" />
                            </div>
                            <h3 className="text-lg font-medium text-slate-900">No classes found</h3>
                            <p className="text-sm text-muted-foreground max-w-sm mx-auto">
                              Your school doesn't have any classes set up yet. Use the form to create your first one.
                            </p>
                          </div>
=======
                      <TableRow>
                        <TableCell colSpan={3} className="text-center text-muted-foreground">
                          No classes found.
>>>>>>> testing
                        </TableCell>
                      </TableRow>
                    ) : (
                      classes.map((cls) => (
<<<<<<< HEAD
                        <TableRow 
                          key={cls.id} 
                          className="group transition-colors hover:bg-slate-50/80 border-b-slate-100 last:border-0"
                        >
                          <TableCell className="font-medium text-slate-900 pl-6">
                            {cls.name}
                          </TableCell>
                          <TableCell className="text-slate-600">
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                              {cls.school_levels?.name || "Unassigned"}
                            </span>
                          </TableCell>
                          <TableCell className="text-right pr-6">
                            <Button
                              variant="ghost"
                              size="icon"
                              className="opacity-0 group-hover:opacity-100 transition-opacity text-slate-400 hover:text-destructive hover:bg-destructive/10 h-8 w-8"
                              onClick={() => handleDeleteClass(cls.id)}
                              title="Delete class"
=======
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
>>>>>>> testing
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