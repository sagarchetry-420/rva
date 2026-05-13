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
import { Loader2, Plus, Trash2, School, GraduationCap } from "lucide-react";

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
    schoolLevelId: "",
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [classesData, levelsData] = await Promise.all([
        api.get<ClassRecord[]>('/api/classes'),
        api.get<SchoolLevel[]>('/api/classes/school-levels'),
      ]);

      setClasses(classesData);
      setLevels(levelsData);
    } catch (error: any) {
      toast({ title: "Fetch failed", description: error.message, variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  const handleSubmitClass = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!newClass.name.trim() || !newClass.schoolLevelId) {
      toast({ title: "Incomplete Form", description: "Please fill out all fields.", variant: "destructive" });
      return;
    }

    setIsSubmitting(true);

    try {
      await api.post('/api/classes', {
        name: newClass.name,
        schoolLevelId: newClass.schoolLevelId,
      });
      setNewClass({ name: "", schoolLevelId: "" });
      toast({ title: "Success", description: "Class added successfully." });
      fetchData();
    } catch (error: any) {
      toast({ title: "Save Error", description: error.message, variant: "destructive" });
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeleteClass = async (id: string) => {
    if (!confirm("Are you sure you want to delete this class? This may affect associated subjects and students.")) return;

    try {
      await api.delete(`/api/classes/${id}`);
      setClasses(classes.filter((c) => c.id !== id));
      toast({ title: "Deleted", description: "Class removed successfully." });
    } catch (error: any) {
      toast({ title: "Delete failed", description: error.message, variant: "destructive" });
    }
  };

  // Sort levels and classes serially
  const sortClasses = (a: ClassRecord, b: ClassRecord) => {
    const aName = a.name.toLowerCase().trim();
    const bName = b.name.toLowerCase().trim();

    // Extract numeric values from class names
    const aNumMatch = aName.match(/\d+/);
    const bNumMatch = bName.match(/\d+/);

    const aNum = aNumMatch ? parseInt(aNumMatch[0]) : null;
    const bNum = bNumMatch ? parseInt(bNumMatch[0]) : null;

    // If both have numeric parts, sort by number
    if (aNum !== null && bNum !== null) {
      return aNum - bNum;
    }

    // If only one has a numeric part, number comes first
    if (aNum !== null) return -1;
    if (bNum !== null) return 1;

    // Otherwise sort alphabetically
    return aName.localeCompare(bName);
  };

  const sortLevels = (a: SchoolLevel, b: SchoolLevel) => {
    const levelOrder: { [key: string]: number } = {
      'nursery': 1,
      'play': 1,
      'pre-primary': 2,
      'kg': 2,
      'lkg': 2,
      'ukg': 2,
      'primary': 3,
      'middle': 4,
      'upper primary': 4,
      'middle school': 4,
      'secondary': 5,
      'senior secondary': 6,
      'higher secondary': 6,
    };

    const aKey = a.name.toLowerCase().trim();
    const bKey = b.name.toLowerCase().trim();

    // Check if the full level name matches
    const aOrder = levelOrder[aKey];
    const bOrder = levelOrder[bKey];

    if (aOrder && bOrder) return aOrder - bOrder;
    if (aOrder) return -1;
    if (bOrder) return 1;

    // Try to match partial strings for compound names
    for (const [key, order] of Object.entries(levelOrder)) {
      if (aKey.includes(key)) {
        const bNumMatch = bKey.match(/\d+/);
        const bNum = bNumMatch ? parseInt(bNumMatch[0]) : null;
        if (bNum !== null) {
          return order <= 3 ? -1 : 1;
        }
        return -1;
      }
      if (bKey.includes(key)) {
        const aNumMatch = aKey.match(/\d+/);
        const aNum = aNumMatch ? parseInt(aNumMatch[0]) : null;
        if (aNum !== null) {
          return order <= 3 ? 1 : -1;
        }
        return 1;
      }
    }

    // Otherwise, try to parse as numbers
    const aNum = parseInt(aKey.match(/\d+/)?.[0] || '');
    const bNum = parseInt(bKey.match(/\d+/)?.[0] || '');

    if (!isNaN(aNum) && !isNaN(bNum)) {
      return aNum - bNum;
    }

    if (!isNaN(aNum)) return -1;
    if (!isNaN(bNum)) return 1;

    return aKey.localeCompare(bKey);
  };

  const classesByLevel = levels
    .sort(sortLevels)
    .map(level => ({
      level,
      classes: classes.filter(cls => cls.school_level_id === level.id)
    }));

  // Count total classes
  const totalClasses = classes.length;

  return (
    <div className="min-h-screen bg-slate-50/50 p-4 md:p-8 lg:p-12">
      <div className="max-w-7xl mx-auto space-y-8">

        {/* Page Header */}
        <div className="flex flex-col gap-2">
          <div className="flex items-center gap-3">
            <div className="p-2.5 bg-primary/10 rounded-xl shadow-sm border border-primary/10">
              <School className="w-6 h-6 text-primary" />
            </div>
            <h1 className="text-3xl font-bold tracking-tight text-slate-900">Class Management</h1>
          </div>
          <p className="text-muted-foreground ml-[3.25rem]">
            Create and manage classes organized by school level.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">

          {/* Left Column - Add Class Form */}
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Plus className="w-5 h-5" /> Add New Class
                </CardTitle>
                <CardDescription>Create a class under a school level</CardDescription>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleSubmitClass} className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="levelSelect">School Level</Label>
                    <Select
                      value={newClass.schoolLevelId}
                      onValueChange={(val) => setNewClass({ ...newClass, schoolLevelId: val })}
                    >
                      <SelectTrigger className="bg-white">
                        <SelectValue placeholder="Select a level" />
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

                  <div className="space-y-2">
                    <Label htmlFor="className">Class Name</Label>
                    <Input
                      id="className"
                      placeholder="e.g. Class 10-A, Grade 5"
                      value={newClass.name}
                      onChange={(e) => setNewClass({ ...newClass, name: e.target.value })}
                    />
                  </div>

                  <Button type="submit" className="w-full" disabled={isSubmitting}>
                    {isSubmitting ? (
                      <Loader2 className="animate-spin mr-2 w-4 h-4" />
                    ) : (
                      <Plus className="mr-2 w-4 h-4" />
                    )}
                    Add Class
                  </Button>
                </form>
              </CardContent>
            </Card>

            {/* Stats Card */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <GraduationCap className="w-5 h-5" /> Overview
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  <div className="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                    <span className="text-sm text-muted-foreground">Total Classes</span>
                    <Badge variant="secondary" className="text-lg px-3">
                      {totalClasses}
                    </Badge>
                  </div>
                  {levels.map(level => {
                    const count = classes.filter(c => c.school_level_id === level.id).length;
                    return (
                      <div key={level.id} className="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                        <span className="text-sm text-muted-foreground">{level.name}</span>
                        <Badge variant="outline">{count}</Badge>
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Right Column - Classes organized by Level */}
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <School className="w-5 h-5" /> Classes by School Level
              </CardTitle>
              <CardDescription>All classes organized by their school level</CardDescription>
            </CardHeader>
            <CardContent>
              {loading ? (
                <div className="flex justify-center p-8">
                  <Loader2 className="animate-spin" />
                </div>
              ) : classesByLevel.every(group => group.classes.length === 0) ? (
                <p className="text-center text-muted-foreground py-8">
                  No classes found. Add a class to get started.
                </p>
              ) : (
                <div className="space-y-6">
                  {classesByLevel.map(({ level, classes: levelClasses }) => (
                    <div key={level.id} className="space-y-3">
                      <div className="flex items-center gap-2 border-b pb-2">
                        <GraduationCap className="w-4 h-4 text-muted-foreground" />
                        <h4 className="font-semibold text-slate-700">{level.name}</h4>
                        <Badge variant="secondary" className="ml-auto">
                          {levelClasses.length} class{levelClasses.length !== 1 ? "es" : ""}
                        </Badge>
                      </div>
                      {levelClasses.length === 0 ? (
                        <p className="text-sm text-muted-foreground py-2 pl-6">
                          No classes in this level yet.
                        </p>
                      ) : (
                        <Table>
                          <TableHeader>
                            <TableRow>
                              <TableHead className="w-12">S.No</TableHead>
                              <TableHead>Class Name</TableHead>
                              <TableHead className="text-right w-20">Actions</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {levelClasses.map((cls, index) => (
                              <TableRow key={cls.id}>
                                <TableCell className="font-medium text-muted-foreground">{index + 1}</TableCell>
                                <TableCell className="font-medium">{cls.name}</TableCell>
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
                            ))}
                          </TableBody>
                        </Table>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

        </div>
      </div>
    </div>
  );
};

export default ClassManagement;
