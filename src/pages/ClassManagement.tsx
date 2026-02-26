import React, { useEffect, useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useToast } from "@/hooks/use-toast";
import { Loader2, Plus, Trash2, School } from "lucide-react";

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
    <div className="container mx-auto p-6 space-y-8">
      <div className="flex items-center gap-2 mb-6">
        <School className="w-8 h-8 text-primary" />
        <h1 className="text-3xl font-bold">School Structure</h1>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Form Section */}
        <Card className="lg:col-span-1 h-fit">
          <CardHeader>
            <CardTitle>Add New Class</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleCreateClass} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="className">Class Name</Label>
                <Input
                  id="className"
                  placeholder="e.g. Grade 10-A"
                  value={newClass.name}
                  onChange={(e) => setNewClass({ ...newClass, name: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label>School Level</Label>
                <Select
                  value={newClass.levelId}
                  onValueChange={(val) => setNewClass({ ...newClass, levelId: val })}
                  required
                >
                  <SelectTrigger>
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
              <Button type="submit" className="w-full" disabled={isSubmitting}>
                {isSubmitting ? <Loader2 className="animate-spin" /> : <Plus className="mr-2" />}
                Create Class
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* Table Section */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Existing Classes</CardTitle>
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
      </div>
    </div>
  );
};

export default ClassManagement;