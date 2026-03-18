import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import {
  CalendarDays,
  Plus,
  Loader2,
  Trash2,
  Clock,
  BookOpen,
  School,
  User,
  MapPin
} from "lucide-react";
import { toast } from "sonner";

interface ClassItem {
  id: string;
  name: string;
}

interface Subject {
  id: string;
  name: string;
  code: string;
}

interface Teacher {
  id: string;
  user_id: string;
  profiles?: {
    first_name: string;
    last_name: string;
  };
}

interface RoutineEntry {
  id: string;
  day_of_week: number;
  start_time: string;
  end_time: string;
  room: string | null;
  class_id: string;
  subject_id: string;
  teacher_id: string | null;
  classes: { id: string; name: string } | null;
  subjects: { id: string; name: string; code: string } | null;
  teacher_name: string | null;
}

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const SCHOOL_DAYS = [1, 2, 3, 4, 5, 6]; // Monday to Saturday

export default function RoutineManagement() {
  const [routines, setRoutines] = useState<RoutineEntry[]>([]);
  const [classes, setClasses] = useState<ClassItem[]>([]);
  const [subjects, setSubjects] = useState<Subject[]>([]);
  const [teachers, setTeachers] = useState<Teacher[]>([]);
  const [selectedClassId, setSelectedClassId] = useState<string>("");
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showForm, setShowForm] = useState(false);

  const [newRoutine, setNewRoutine] = useState({
    classId: "",
    subjectId: "",
    teacherId: "",
    dayOfWeek: "",
    startTime: "",
    endTime: "",
    room: ""
  });

  useEffect(() => {
    fetchInitialData();
  }, []);

  useEffect(() => {
    if (selectedClassId) {
      fetchRoutines(selectedClassId);
    }
  }, [selectedClassId]);

  const fetchInitialData = async () => {
    try {
      const [classesData, subjectsData, teachersData] = await Promise.all([
        api.get<ClassItem[]>('/api/classes/simple'),
        api.get<Subject[]>('/api/classes/subjects'),
        api.get<Teacher[]>('/api/teachers')
      ]);
      setClasses(classesData);
      setSubjects(subjectsData);
      setTeachers(teachersData);
      if (classesData.length > 0) {
        setSelectedClassId(classesData[0].id);
      }
    } catch (error: any) {
      toast.error("Failed to load data");
    } finally {
      setLoading(false);
    }
  };

  const fetchRoutines = async (classId: string) => {
    try {
      const data = await api.get<RoutineEntry[]>(`/api/routines?class_id=${classId}`);
      setRoutines(data);
    } catch (error: any) {
      toast.error("Failed to load routines");
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!newRoutine.classId || !newRoutine.subjectId || !newRoutine.dayOfWeek || !newRoutine.startTime || !newRoutine.endTime) {
      toast.error("Please fill all required fields");
      return;
    }

    setIsSubmitting(true);
    try {
      await api.post('/api/routines', {
        classId: newRoutine.classId,
        subjectId: newRoutine.subjectId,
        teacherId: newRoutine.teacherId || null,
        dayOfWeek: parseInt(newRoutine.dayOfWeek),
        startTime: newRoutine.startTime,
        endTime: newRoutine.endTime,
        room: newRoutine.room || null
      });

      toast.success("Routine entry added successfully");
      setNewRoutine({
        classId: selectedClassId,
        subjectId: "",
        teacherId: "",
        dayOfWeek: "",
        startTime: "",
        endTime: "",
        room: ""
      });
      setShowForm(false);
      if (selectedClassId) {
        fetchRoutines(selectedClassId);
      }
    } catch (error: any) {
      toast.error("Failed to add routine: " + error.message);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (routineId: string) => {
    if (!confirm("Are you sure you want to delete this routine entry?")) return;

    try {
      await api.delete(`/api/routines/${routineId}`);
      toast.success("Routine entry deleted");
      setRoutines(routines.filter(r => r.id !== routineId));
    } catch (error: any) {
      toast.error("Failed to delete routine");
    }
  };

  const formatTime = (timeStr: string) => {
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
  };

  const getTeacherName = (teacher: Teacher) => {
    if (teacher.profiles) {
      return `${teacher.profiles.first_name} ${teacher.profiles.last_name}`.trim();
    }
    return 'Unknown Teacher';
  };

  // Group routines by day
  const routinesByDay = SCHOOL_DAYS.map(dayIndex => ({
    dayIndex,
    dayName: DAYS[dayIndex],
    entries: routines.filter(r => r.day_of_week === dayIndex).sort((a, b) => a.start_time.localeCompare(b.start_time))
  }));

  const selectedClass = classes.find(c => c.id === selectedClassId);
  const totalPeriods = routines.length;
  const daysWithClasses = new Set(routines.map(r => r.day_of_week)).size;

  const getDayColor = (dayIndex: number) => {
    const colors = [
      { bg: 'bg-red-100', text: 'text-red-600', badge: 'bg-red-50' }, // Sunday
      { bg: 'bg-violet-100', text: 'text-violet-600', badge: 'bg-violet-50' }, // Monday
      { bg: 'bg-blue-100', text: 'text-blue-600', badge: 'bg-blue-50' }, // Tuesday
      { bg: 'bg-green-100', text: 'text-green-600', badge: 'bg-green-50' }, // Wednesday
      { bg: 'bg-amber-100', text: 'text-amber-600', badge: 'bg-amber-50' }, // Thursday
      { bg: 'bg-pink-100', text: 'text-pink-600', badge: 'bg-pink-50' }, // Friday
      { bg: 'bg-cyan-100', text: 'text-cyan-600', badge: 'bg-cyan-50' }, // Saturday
    ];
    return colors[dayIndex];
  };

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header Section */}
      <div className="flex flex-col gap-4">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <div className="p-2.5 bg-violet-100 rounded-xl shadow-sm">
              <CalendarDays className="w-6 h-6 text-violet-600" />
            </div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
              Routine Management
            </h1>
          </div>
          <p className="text-gray-500 text-sm sm:text-base ml-[3.25rem]">
            Create and manage class timetables and schedules.
          </p>
        </div>

        <Button
          onClick={() => {
            setNewRoutine({ ...newRoutine, classId: selectedClassId });
            setShowForm(!showForm);
          }}
          className="gap-2 bg-violet-600 hover:bg-violet-700 shadow-sm rounded-xl h-10 sm:h-11 w-full sm:w-auto sm:self-start"
        >
          <Plus className="w-4 h-4" /> {showForm ? 'Hide Form' : 'Add Period'}
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <Card className="bg-violet-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Periods</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-violet-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : totalPeriods}
              </p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-violet-100 flex items-center justify-center">
              <Clock className="w-5 h-5 sm:w-6 sm:h-6 text-violet-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-amber-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">School Days</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-amber-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : daysWithClasses}
              </p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-amber-100 flex items-center justify-center">
              <CalendarDays className="w-5 h-5 sm:w-6 sm:h-6 text-amber-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-green-50/80 border border-black/5 hover:shadow-md transition-all col-span-2 sm:col-span-1">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Subjects</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-green-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : new Set(routines.map(r => r.subject_id)).size}
              </p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-green-100 flex items-center justify-center">
              <BookOpen className="w-5 h-5 sm:w-6 sm:h-6 text-green-500" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Class Selector */}
      <Card className="border-0 shadow-sm rounded-2xl bg-white">
        <CardContent className="p-4 sm:p-6">
          <div className="flex flex-col sm:flex-row sm:items-center gap-4">
            <div className="flex-1 space-y-2">
              <Label className="text-sm font-medium">Select Class</Label>
              <Select value={selectedClassId} onValueChange={setSelectedClassId} disabled={loading}>
                <SelectTrigger className="bg-gray-50 rounded-xl h-11">
                  <SelectValue placeholder="Select a class to view routine" />
                </SelectTrigger>
                <SelectContent>
                  {classes.map((cls) => (
                    <SelectItem key={cls.id} value={cls.id}>{cls.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            {selectedClass && (
              <div className="flex items-center gap-3 px-4 py-3 bg-violet-50 rounded-xl">
                <School className="w-5 h-5 text-violet-600" />
                <div>
                  <p className="font-semibold text-gray-800">{selectedClass.name}</p>
                  <p className="text-xs text-gray-500">{totalPeriods} periods scheduled</p>
                </div>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Add Period Form */}
      {showForm && (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardHeader className="pb-4">
            <CardTitle className="flex items-center gap-2 text-lg">
              <Plus className="w-5 h-5" /> Add New Period
            </CardTitle>
            <CardDescription>Add a period to the class timetable</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>Class *</Label>
                  <Select
                    value={newRoutine.classId}
                    onValueChange={(val) => setNewRoutine({ ...newRoutine, classId: val })}
                  >
                    <SelectTrigger className="rounded-xl">
                      <SelectValue placeholder="Select class" />
                    </SelectTrigger>
                    <SelectContent>
                      {classes.map((cls) => (
                        <SelectItem key={cls.id} value={cls.id}>{cls.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Subject *</Label>
                  <Select
                    value={newRoutine.subjectId}
                    onValueChange={(val) => setNewRoutine({ ...newRoutine, subjectId: val })}
                  >
                    <SelectTrigger className="rounded-xl">
                      <SelectValue placeholder="Select subject" />
                    </SelectTrigger>
                    <SelectContent>
                      {subjects.map((subj) => (
                        <SelectItem key={subj.id} value={subj.id}>
                          {subj.name} ({subj.code})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Teacher (Optional)</Label>
                  <Select
                    value={newRoutine.teacherId}
                    onValueChange={(val) => setNewRoutine({ ...newRoutine, teacherId: val })}
                  >
                    <SelectTrigger className="rounded-xl">
                      <SelectValue placeholder="Select teacher" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">No teacher assigned</SelectItem>
                      {teachers.map((teacher) => (
                        <SelectItem key={teacher.id} value={teacher.id}>
                          {getTeacherName(teacher)}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Day *</Label>
                  <Select
                    value={newRoutine.dayOfWeek}
                    onValueChange={(val) => setNewRoutine({ ...newRoutine, dayOfWeek: val })}
                  >
                    <SelectTrigger className="rounded-xl">
                      <SelectValue placeholder="Select day" />
                    </SelectTrigger>
                    <SelectContent>
                      {SCHOOL_DAYS.map((dayIndex) => (
                        <SelectItem key={dayIndex} value={dayIndex.toString()}>
                          {DAYS[dayIndex]}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Start Time *</Label>
                  <Input
                    type="time"
                    value={newRoutine.startTime}
                    onChange={(e) => setNewRoutine({ ...newRoutine, startTime: e.target.value })}
                    className="rounded-xl"
                  />
                </div>

                <div className="space-y-2">
                  <Label>End Time *</Label>
                  <Input
                    type="time"
                    value={newRoutine.endTime}
                    onChange={(e) => setNewRoutine({ ...newRoutine, endTime: e.target.value })}
                    className="rounded-xl"
                  />
                </div>

                <div className="space-y-2">
                  <Label>Room (Optional)</Label>
                  <Input
                    placeholder="e.g. Room 101"
                    value={newRoutine.room}
                    onChange={(e) => setNewRoutine({ ...newRoutine, room: e.target.value })}
                    className="rounded-xl"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-2">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setShowForm(false)}
                  className="rounded-xl"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  disabled={isSubmitting}
                  className="rounded-xl bg-violet-600 hover:bg-violet-700"
                >
                  {isSubmitting ? (
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  ) : (
                    <Plus className="w-4 h-4 mr-2" />
                  )}
                  Add Period
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {/* Timetable View */}
      {loading ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 flex flex-col items-center justify-center">
            <Loader2 className="w-8 h-8 animate-spin text-violet-500 mb-4" />
            <p className="text-gray-500">Loading timetable...</p>
          </CardContent>
        </Card>
      ) : !selectedClassId ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 flex flex-col items-center justify-center">
            <School className="w-12 h-12 text-gray-200 mb-4" />
            <p className="text-lg font-semibold text-gray-700">Select a class</p>
            <p className="text-gray-500 mt-1">Choose a class to view its timetable</p>
          </CardContent>
        </Card>
      ) : routines.length === 0 ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 flex flex-col items-center justify-center">
            <CalendarDays className="w-12 h-12 text-gray-200 mb-4" />
            <p className="text-lg font-semibold text-gray-700">No routine set</p>
            <p className="text-gray-500 mt-1 text-center">
              Click "Add Period" to start building the timetable.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-3 sm:space-y-4">
          {routinesByDay.map(({ dayIndex, dayName, entries }) => {
            if (entries.length === 0) return null;
            const colors = getDayColor(dayIndex);

            return (
              <Card key={dayIndex} className="border-0 shadow-sm rounded-2xl bg-white overflow-hidden hover:shadow-md transition-shadow">
                {/* Day Header */}
                <div className={`px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between ${colors.badge} border-b border-gray-100`}>
                  <div className="flex items-center gap-3">
                    <div className={`w-10 h-10 sm:w-12 sm:h-12 rounded-xl ${colors.bg} flex items-center justify-center`}>
                      <CalendarDays className={`w-5 h-5 sm:w-6 sm:h-6 ${colors.text}`} />
                    </div>
                    <div>
                      <h3 className="text-base sm:text-lg font-bold text-gray-800">{dayName}</h3>
                      <p className="text-xs sm:text-sm text-gray-500">
                        {entries.length} period{entries.length !== 1 ? 's' : ''}
                      </p>
                    </div>
                  </div>
                  <Badge className={`${colors.bg} ${colors.text}`}>
                    {entries.length}
                  </Badge>
                </div>

                {/* Periods List */}
                <CardContent className="p-0">
                  <div className="divide-y divide-gray-50">
                    {entries.map((entry) => (
                      <div
                        key={entry.id}
                        className="px-4 sm:px-6 py-3 sm:py-4 hover:bg-gray-50/50 transition-colors"
                      >
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 mb-1">
                              <Badge variant="outline" className="text-xs font-mono">
                                {formatTime(entry.start_time)} - {formatTime(entry.end_time)}
                              </Badge>
                              <h4 className="font-semibold text-gray-800 truncate">
                                {entry.subjects?.name || 'Unknown'}
                              </h4>
                            </div>
                            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                              {entry.teacher_name && (
                                <span className="flex items-center gap-1">
                                  <User className="w-3 h-3" />
                                  {entry.teacher_name}
                                </span>
                              )}
                              {entry.room && (
                                <span className="flex items-center gap-1">
                                  <MapPin className="w-3 h-3" />
                                  {entry.room}
                                </span>
                              )}
                              <span className="flex items-center gap-1">
                                <BookOpen className="w-3 h-3" />
                                {entry.subjects?.code || '-'}
                              </span>
                            </div>
                          </div>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl shrink-0"
                            onClick={() => handleDelete(entry.id)}
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}

      {/* Footer */}
      {!loading && routines.length > 0 && (
        <div className="text-center text-xs sm:text-sm text-gray-500 py-3">
          {selectedClass?.name}: {totalPeriods} periods across {daysWithClasses} day{daysWithClasses !== 1 ? 's' : ''}
        </div>
      )}
    </div>
  );
}
