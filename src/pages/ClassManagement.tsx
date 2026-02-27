import React, { useEffect, useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useToast } from "@/hooks/use-toast";
import { Loader2, Plus, Trash2, School, LayoutGrid } from "lucide-react";

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

const ClassManagement: React.FC = () => {
  const [classes, setClasses] = useState<ClassRecord[]>([]);
  const [levels, setLevels] = useState<SchoolLevel[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { toast } = useToast();

  const [newClass, setNewClass] = useState({
    name: "",
    levelId: "",
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [classesRes, levelsRes] = await Promise.all([
        supabase.from("classes").select("*, school_levels(name)").order("name"),
        supabase.from("school_levels").select("id, name").order("name"),
      ]);

      if (classesRes.error) throw classesRes.error;
      if (levelsRes.error) throw levelsRes.error;

      // Debugging log to ensure data is actually coming back from Supabase
      console.log("Fetched School Levels:", levelsRes.data);

      setClasses(classesRes.data || []);
      setLevels(levelsRes.data || []);
    } catch (error: any) {
      toast({ title: "Fetch failed", description: error.message, variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  const handleCreateClass = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newClass.name || !newClass.levelId) return;

    setIsSubmitting(true);
    const { error } = await supabase.from("classes").insert([
      { name: newClass.name, school_level_id: newClass.levelId },
    ]);

    if (error) {
      toast({ title: "Error", description: error.message, variant: "destructive" });
    } else {
      toast({ title: "Success", description: "Class created successfully" });
      setNewClass({ name: "", levelId: "" });
      fetchData();
    }
    setIsSubmitting(false);
  };

  const handleDeleteClass = async (id: string) => {
    if (!confirm("Are you sure you want to delete this class?")) return;

    const { error } = await supabase.from("classes").delete().eq("id", id);
    if (error) {
      toast({ title: "Delete failed", description: error.message, variant: "destructive" });
    } else {
      setClasses(classes.filter((c) => c.id !== id));
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
                  <Input
                    id="className"
                    placeholder="e.g. Grade 10-A"
                    value={newClass.name}
                    onChange={(e) => setNewClass({ ...newClass, name: e.target.value })}
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
                          {level.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
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
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {classes.length === 0 ? (
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
                        </TableCell>
                      </TableRow>
                    ) : (
                      classes.map((cls) => (
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