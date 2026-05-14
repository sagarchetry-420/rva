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
import { Loader2, Plus, Trash2, BookOpen, School } from "lucide-react";

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

const SubjectManagement: React.FC = () => {
  const [classes, setClasses] = useState<ClassRecord[]>([]);
  const [subjects, setSubjects] = useState<SubjectRecord[]>([]);
  const [levels, setLevels] = useState<SchoolLevel[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [filterClassId, setFilterClassId] = useState<string>("all");
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

    // Validate that subject with same name doesn't already exist in this class
    const subjectExists = subjects.some(
      (s) => s.class_id === newSubject.classId &&
             s.name.toLowerCase() === newSubject.name.toLowerCase()
    );

    if (subjectExists) {
      toast({
        title: "Duplicate Subject",
        description: "A subject with this name already exists in the selected class.",
        variant: "destructive"
      });
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

  // Sort levels and classes serially
  const sortClasses = (a: ClassRecord, b: ClassRecord) => {
    const classOrder: { [key: string]: number } = {
      'play': 1,
      'nursery': 2,
      'lkg': 3,
      'kg': 4,
      'ukg': 5,
    };

    const aName = a.name.toLowerCase().trim();
    const bName = b.name.toLowerCase().trim();

    const getOrder = (name: string) => {
      for (const [key, order] of Object.entries(classOrder)) {
        if (name.includes(key)) return order;
      }
      const numMatch = name.match(/\d+/);
      if (numMatch) return parseInt(numMatch[0]) + 10;
      return 100;
    };

    const aOrder = getOrder(aName);
    const bOrder = getOrder(bName);

    if (aOrder !== bOrder) return aOrder - bOrder;
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

  // Group classes by school level (sorted)
  const classesByLevel = levels
    .sort(sortLevels)
    .map(level => ({
      level,
      classes: classes.filter(cls => cls.school_level_id === level.id).sort(sortClasses)
    }))
    .filter(group => group.classes.length > 0);

  // Get class name by ID
  const getClassName = (classId?: string) => {
    if (!classId) return "N/A";
    const cls = classes.find(c => c.id === classId);
    return cls?.name || "N/A";
  };

  // Filter subjects by class
  const filteredSubjects = filterClassId === "all"
    ? subjects
    : subjects.filter(sub => sub.class_id === filterClassId);

  // Group subjects by class for display (sorted serially by level and class)
  const subjectsByClass = levels
    .sort(sortLevels)
    .flatMap(level =>
      classes
        .filter(cls => cls.school_level_id === level.id)
        .sort(sortClasses)
        .map(cls => ({
          class: cls,
          subjects: subjects.filter(sub => sub.class_id === cls.id)
        }))
    )
    .filter(group => group.subjects.length > 0);

  return (
    <div className="min-h-screen bg-slate-50/50 p-3 sm:p-4 md:p-8 lg:p-12">
      <div className="max-w-7xl mx-auto space-y-6 sm:space-y-8">

        {/* Page Header */}
        <div className="flex flex-col gap-1 sm:gap-2">
          <div className="flex items-center gap-2 sm:gap-3">
            <div className="p-1.5 sm:p-2.5 bg-primary/10 rounded-lg sm:rounded-xl shadow-sm border border-primary/10">
              <BookOpen className="w-5 h-5 sm:w-6 sm:h-6 text-primary" />
            </div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-bold tracking-tight text-slate-900">Subject Management</h1>
          </div>
          <p className="text-xs sm:text-sm text-muted-foreground ml-11 sm:ml-[3.25rem]">
            Create and manage subjects for each class.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">

          {/* Left Column - Add Subject Form */}
          <div className="space-y-4 sm:space-y-6">
            <Card className="shadow-sm">
              <CardHeader className="p-4 sm:p-6">
                <CardTitle className="flex items-center gap-2 text-lg sm:text-xl">
                  <Plus className="w-4 h-4 sm:w-5 sm:h-5" /> Add New Subject
                </CardTitle>
                <CardDescription className="text-xs sm:text-sm">Create a subject and assign it to a class</CardDescription>
              </CardHeader>
              <CardContent className="p-4 sm:p-6 pt-0 sm:pt-0">
                <form onSubmit={handleSubmitSubject} className="space-y-3 sm:space-y-4">
                  <div className="space-y-1 sm:space-y-2">
                    <Label htmlFor="classSelect" className="text-xs sm:text-sm">Assign to Class</Label>
                    <Select
                      value={newSubject.classId}
                      onValueChange={(val) => setNewSubject({ ...newSubject, classId: val })}
                    >
                      <SelectTrigger className="bg-white text-sm">
                        <SelectValue placeholder="Select a class" />
                      </SelectTrigger>
                      <SelectContent>
                        {classesByLevel.map(({ level, classes: levelClasses }) => (
                          <React.Fragment key={level.id}>
                            <div className="px-2 py-1.5 text-xs font-semibold text-muted-foreground bg-slate-50">
                              {level.name}
                            </div>
                            {levelClasses.map((cls) => (
                              <SelectItem key={cls.id} value={cls.id} className="text-sm">
                                {cls.name}
                              </SelectItem>
                            ))}
                          </React.Fragment>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-1 sm:space-y-2">
                    <Label htmlFor="subjectName" className="text-xs sm:text-sm">Subject Name</Label>
                    <Input
                      id="subjectName"
                      placeholder="e.g. Advanced Calculus"
                      value={newSubject.name}
                      onChange={(e) => setNewSubject({ ...newSubject, name: e.target.value })}
                      className="text-sm"
                    />
                  </div>

                  <div className="space-y-1 sm:space-y-2">
                    <Label htmlFor="subjectCode" className="text-xs sm:text-sm">Subject Code</Label>
                    <Input
                      id="subjectCode"
                      placeholder="e.g. MATH-401"
                      value={newSubject.code}
                      onChange={(e) => setNewSubject({ ...newSubject, code: e.target.value })}
                      className="text-sm"
                    />
                  </div>

                  <Button type="submit" className="w-full text-sm" disabled={isSubmitting}>
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

          {/* Right Column - Subjects organized by Class */}
          <Card className="lg:col-span-2 shadow-sm">
            <CardHeader className="p-4 sm:p-6">
              <div className="flex flex-col gap-3 sm:gap-4">
                <div>
                  <CardTitle className="flex items-center gap-2 text-lg sm:text-xl">
                    <BookOpen className="w-4 h-4 sm:w-5 sm:h-5" /> Subjects by Class
                  </CardTitle>
                  <CardDescription className="text-xs sm:text-sm">All subjects organized by their assigned class</CardDescription>
                </div>
                <div className="w-full">
                  <Select value={filterClassId} onValueChange={setFilterClassId}>
                    <SelectTrigger className="bg-white text-sm">
                      <SelectValue placeholder="Filter by class" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all" className="text-sm">All Classes</SelectItem>
                      {classesByLevel.map(({ level, classes: levelClasses }) => (
                        <React.Fragment key={level.id}>
                          <div className="px-2 py-1.5 text-xs font-semibold text-muted-foreground bg-slate-50">
                            {level.name}
                          </div>
                          {levelClasses.map((cls) => (
                            <SelectItem key={cls.id} value={cls.id} className="text-sm">
                              {cls.name}
                            </SelectItem>
                          ))}
                        </React.Fragment>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardHeader>
            <CardContent className="p-4 sm:p-6 pt-0 sm:pt-0">
              {loading ? (
                <div className="flex justify-center p-8">
                  <Loader2 className="animate-spin" />
                </div>
              ) : filterClassId === "all" ? (
                // Show grouped by class
                subjectsByClass.length === 0 ? (
                  <p className="text-center text-muted-foreground py-8 text-sm">
                    No subjects found. Add a subject to get started.
                  </p>
                ) : (
                  <div className="space-y-4 sm:space-y-6">
                    {subjectsByClass.map(({ class: cls, subjects: classSubjects }) => (
                      <div key={cls.id} className="space-y-2 sm:space-y-3">
                        <div className="flex items-center gap-2 border-b pb-2">
                          <School className="w-3 h-3 sm:w-4 sm:h-4 text-muted-foreground flex-shrink-0" />
                          <h4 className="font-semibold text-sm sm:text-base text-slate-700 truncate">{cls.name}</h4>
                          <Badge variant="secondary" className="ml-auto text-xs">
                            {classSubjects.length}
                          </Badge>
                        </div>

                        {/* Mobile Card View */}
                        <div className="md:hidden space-y-2">
                          {classSubjects.map((sub) => (
                            <div key={sub.id} className="p-3 bg-slate-50 rounded-lg flex items-center justify-between gap-2">
                              <div className="min-w-0 flex-1">
                                <p className="font-medium text-sm text-slate-900 truncate">{sub.name}</p>
                                <Badge variant="outline" className="text-xs mt-1 w-fit">{sub.code}</Badge>
                              </div>
                              <Button
                                variant="ghost"
                                size="icon"
                                className="text-destructive hover:text-destructive hover:bg-destructive/10 flex-shrink-0 h-8 w-8"
                                onClick={() => handleDeleteSubject(sub.id)}
                              >
                                <Trash2 className="w-4 h-4" />
                              </Button>
                            </div>
                          ))}
                        </div>

                        {/* Desktop Table View */}
                        <div className="hidden md:block overflow-x-auto">
                          <Table>
                            <TableHeader>
                              <TableRow>
                                <TableHead className="text-xs">Subject Name</TableHead>
                                <TableHead className="text-xs">Code</TableHead>
                                <TableHead className="text-right w-20 text-xs">Actions</TableHead>
                              </TableRow>
                            </TableHeader>
                            <TableBody>
                              {classSubjects.map((sub) => (
                                <TableRow key={sub.id}>
                                  <TableCell className="font-medium text-sm">{sub.name}</TableCell>
                                  <TableCell>
                                    <Badge variant="outline" className="text-xs">{sub.code}</Badge>
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
                              ))}
                            </TableBody>
                          </Table>
                        </div>
                      </div>
                    ))}
                  </div>
                )
              ) : (
                // Show filtered table/cards
                <div className="md:hidden space-y-2">
                  {filteredSubjects.length === 0 ? (
                    <p className="text-center text-muted-foreground py-8 text-sm">
                      No subjects found for this class.
                    </p>
                  ) : (
                    filteredSubjects.map((sub) => (
                      <div key={sub.id} className="p-3 bg-slate-50 rounded-lg flex items-center justify-between gap-2">
                        <div className="min-w-0 flex-1">
                          <p className="font-medium text-sm text-slate-900 truncate">{sub.name}</p>
                          <Badge variant="outline" className="text-xs mt-1 w-fit">{sub.code}</Badge>
                        </div>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-destructive hover:text-destructive hover:bg-destructive/10 flex-shrink-0 h-8 w-8"
                          onClick={() => handleDeleteSubject(sub.id)}
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    ))
                  )}
                </div>
              )}

              {/* Desktop Table for Filtered View */}
              {filterClassId !== "all" && (
                <div className="hidden md:block overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="text-xs">Subject Name</TableHead>
                        <TableHead className="text-xs">Code</TableHead>
                        <TableHead className="text-right w-20 text-xs">Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {filteredSubjects.length === 0 ? (
                        <TableRow>
                          <TableCell colSpan={3} className="text-center text-muted-foreground py-8 text-sm">
                            No subjects found for this class.
                          </TableCell>
                        </TableRow>
                      ) : (
                        filteredSubjects.map((sub) => (
                          <TableRow key={sub.id}>
                            <TableCell className="font-medium text-sm">{sub.name}</TableCell>
                            <TableCell>
                              <Badge variant="outline" className="text-xs">{sub.code}</Badge>
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
                </div>
              )}
            </CardContent>
          </Card>

        </div>
      </div>
    </div>
  );
};

export default SubjectManagement;
