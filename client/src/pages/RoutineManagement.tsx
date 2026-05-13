import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
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
  subject_id: string | null;
  teacher_id: string | null;
  type?: 'period' | 'break';
  classes: { id: string; name: string } | null;
  subjects: { id: string; name: string; code: string } | null;
  teacher_name: string | null;
}

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const SCHOOL_DAYS = [1, 2, 3, 4, 5, 6]; // Monday to Saturday

// Sort classes serially
const sortClasses = (a: ClassItem, b: ClassItem) => {
  const aName = a.name.toLowerCase().trim();
  const bName = b.name.toLowerCase().trim();

  const aNumMatch = aName.match(/\d+/);
  const bNumMatch = bName.match(/\d+/);

  const aNum = aNumMatch ? parseInt(aNumMatch[0]) : null;
  const bNum = bNumMatch ? parseInt(bNumMatch[0]) : null;

  if (aNum !== null && bNum !== null) return aNum - bNum;
  if (aNum !== null) return -1;
  if (bNum !== null) return 1;

  return aName.localeCompare(bName);
};

export default function RoutineManagement() {
  const navigate = useNavigate();
  const [routines, setRoutines] = useState<RoutineEntry[]>([]);
  const [classes, setClasses] = useState<ClassItem[]>([]);
  const [subjects, setSubjects] = useState<Subject[]>([]);
  const [teachers, setTeachers] = useState<Teacher[]>([]);
  const [selectedClassId, setSelectedClassId] = useState<string>("");
  const [loading, setLoading] = useState(true);

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
      const sortedClasses = classesData.sort(sortClasses);
      setClasses(sortedClasses);
      setSubjects(subjectsData);
      setTeachers(teachersData);
    } catch (error: any) {
      toast.error("Failed to load data");
    } finally {
      setLoading(false);
    }
  };

  const fetchRoutines = async (classId: string) => {
    try {
      const data = await api.get<RoutineEntry[]>(`/api/routines?class_id=${classId}`);
      // Sort by day and then by start time
      const sorted = data.sort((a, b) => {
        if (a.day_of_week !== b.day_of_week) {
          return a.day_of_week - b.day_of_week;
        }
        return a.start_time.localeCompare(b.start_time);
      });
      setRoutines(sorted);
    } catch (error: any) {
      toast.error("Failed to load routines");
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

  const getTimeSlots = () => {
    const timeSlots = new Set<string>();
    routines.forEach(r => {
      timeSlots.add(`${r.start_time}|${r.end_time}`);
    });
    return Array.from(timeSlots)
      .map(slot => {
        const [start, end] = slot.split('|');
        return { start, end };
      })
      .sort((a, b) => a.start.localeCompare(b.start));
  };

  const getRoutineForSlot = (dayOfWeek: number, startTime: string, endTime: string) => {
    return routines.find(
      r => r.day_of_week === dayOfWeek && r.start_time === startTime && r.end_time === endTime
    );
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

  const selectedClass = classes.find(c => c.id === selectedClassId);
  const totalPeriods = routines.length;
  const daysWithClasses = new Set(routines.map(r => r.day_of_week)).size;

  const getDayColor = (dayIndex: number) => {
    const colors = [
      { bg: 'bg-red-100', text: 'text-red-600', badge: 'bg-red-50' }, // Sunday
      { bg: 'bg-orange-100', text: 'text-orange-600', badge: 'bg-orange-50' }, // Monday
      { bg: 'bg-blue-100', text: 'text-blue-600', badge: 'bg-blue-50' }, // Tuesday
      { bg: 'bg-green-100', text: 'text-green-600', badge: 'bg-green-50' }, // Wednesday
      { bg: 'bg-amber-100', text: 'text-amber-600', badge: 'bg-amber-50' }, // Thursday
      { bg: 'bg-pink-100', text: 'text-pink-600', badge: 'bg-pink-50' }, // Friday
      { bg: 'bg-cyan-100', text: 'text-cyan-600', badge: 'bg-cyan-50' }, // Saturday
    ];
    return colors[dayIndex];
  };

  return (
    <div className="space-y-3 sm:space-y-4 md:space-y-6">
      {/* Header Section */}
      <div className="flex flex-col gap-3 sm:gap-4">
        <div>
          <div className="flex items-center gap-2 sm:gap-3 mb-2">
            <div className="p-1.5 sm:p-2.5 bg-orange-100 rounded-lg sm:rounded-xl shadow-sm">
              <CalendarDays className="w-5 h-5 sm:w-6 sm:h-6 text-orange-600" />
            </div>
            <h1 className="text-lg sm:text-xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
              Routine Management
            </h1>
          </div>
          <p className="text-gray-500 text-xs sm:text-sm ml-[2.75rem] sm:ml-[3.25rem]">
            Create and manage class timetables and schedules.
          </p>
        </div>

        <Button
          onClick={() => navigate("/dashboard/routines/create")}
          className="gap-1.5 sm:gap-2 bg-orange-600 hover:bg-orange-700 shadow-sm rounded-lg sm:rounded-xl h-9 sm:h-10 md:h-11 w-full sm:w-auto sm:self-start text-xs sm:text-sm"
        >
          <Plus className="w-4 h-4" /> Create Routine
        </Button>
      </div>

      {/* Class Selector */}
      <Card className="border-0 shadow-sm rounded-lg sm:rounded-2xl bg-white">
        <CardContent className="p-3 sm:p-4 md:p-6">
          <div className="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
            <div className="flex-1 space-y-2">
              <Label className="text-xs sm:text-sm font-medium">Select Class</Label>
              <Select value={selectedClassId} onValueChange={setSelectedClassId} disabled={loading}>
                <SelectTrigger className="bg-gray-50 rounded-lg sm:rounded-xl h-9 sm:h-11 text-sm">
                  <SelectValue placeholder="Choose class" />
                </SelectTrigger>
                <SelectContent>
                  {classes.sort(sortClasses).map((cls) => (
                    <SelectItem key={cls.id} value={cls.id}>{cls.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            {selectedClass && (
              <div className="flex items-center gap-2 sm:gap-3 px-2 sm:px-3 md:px-4 py-2.5 sm:py-3 bg-orange-50 rounded-lg sm:rounded-xl flex-shrink-0">
                <School className="w-4 h-4 sm:w-5 sm:h-5 text-orange-600 shrink-0" />
                <div className="min-w-0">
                  <p className="font-semibold text-sm sm:text-base text-gray-800 truncate">{selectedClass.name}</p>
                  <p className="text-xs text-gray-500">{totalPeriods} periods</p>
                </div>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Timetable View */}
      {loading ? (
        <Card className="border-0 shadow-sm rounded-lg sm:rounded-2xl bg-white">
          <CardContent className="p-6 sm:p-8 flex flex-col items-center justify-center">
            <Loader2 className="w-6 h-6 sm:w-8 sm:h-8 animate-spin text-orange-500 mb-2 sm:mb-4" />
            <p className="text-xs sm:text-sm text-gray-500">Loading timetable...</p>
          </CardContent>
        </Card>
      ) : !selectedClassId ? (
        <Card className="border-0 shadow-sm rounded-lg sm:rounded-2xl bg-white">
          <CardContent className="p-6 sm:p-8 flex flex-col items-center justify-center">
            <School className="w-10 h-10 sm:w-12 sm:h-12 text-gray-200 mb-3 sm:mb-4" />
            <p className="text-base sm:text-lg font-semibold text-gray-700">Select a class</p>
            <p className="text-xs sm:text-sm text-gray-500 mt-1">Choose a class to view its timetable</p>
          </CardContent>
        </Card>
      ) : routines.length === 0 ? (
        <Card className="border-0 shadow-sm rounded-lg sm:rounded-2xl bg-white">
          <CardContent className="p-6 sm:p-8 flex flex-col items-center justify-center">
            <CalendarDays className="w-10 h-10 sm:w-12 sm:h-12 text-gray-200 mb-3 sm:mb-4" />
            <p className="text-base sm:text-lg font-semibold text-gray-700">No routine set</p>
            <p className="text-xs sm:text-sm text-gray-500 mt-1 text-center">
              Click "Create Routine" to start building the timetable.
            </p>
          </CardContent>
        </Card>
      ) : (
        <Card className="border-0 shadow-sm rounded-lg sm:rounded-2xl bg-white overflow-hidden">
          <CardHeader className="pb-3 sm:pb-4 px-3 sm:px-6 pt-3 sm:pt-6">
            <CardTitle className="text-base sm:text-lg">Weekly Timetable</CardTitle>
            <CardDescription className="text-xs sm:text-sm">
              {selectedClass?.name}
            </CardDescription>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full border-collapse">
                <thead>
                  <tr className="bg-gray-100 border-b border-gray-200">
                    <th className="px-2 sm:px-4 py-2 sm:py-3 text-left text-xs sm:text-sm font-semibold text-gray-700 bg-gray-50 sticky left-0 z-10 border-r border-gray-200">
                      Day
                    </th>
                    {getTimeSlots().map((slot, idx) => (
                      <th
                        key={`${slot.start}-${slot.end}`}
                        className="px-2 sm:px-3 py-2 sm:py-3 text-center text-xs sm:text-sm font-semibold text-gray-700 border-r border-gray-200 min-w-max"
                      >
                        {formatTime(slot.start)} - {formatTime(slot.end)}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {SCHOOL_DAYS.map((dayIndex) => {
                    const colors = getDayColor(dayIndex);
                    return (
                      <tr key={dayIndex} className="hover:bg-gray-50/30 transition-colors border-b border-gray-100">
                        <td
                          className={`px-2 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-semibold ${colors.text} bg-gray-50 sticky left-0 z-10 border-r border-gray-200`}
                        >
                          {DAYS[dayIndex]}
                        </td>
                        {getTimeSlots().map((slot) => {
                          const routine = getRoutineForSlot(dayIndex, slot.start, slot.end);
                          return (
                            <td
                              key={`${dayIndex}-${slot.start}-${slot.end}`}
                              className="px-1 sm:px-2 py-2 sm:py-3 border-r border-gray-200 min-w-[120px]"
                            >
                              {routine ? (
                                <div
                                  className={`p-1.5 sm:p-2 rounded-lg border-2 relative group ${
                                    routine.type === 'break'
                                      ? 'bg-amber-50 border-amber-200'
                                      : 'bg-orange-50 border-orange-200'
                                  }`}
                                >
                                  <div className="text-xs sm:text-sm font-semibold text-gray-800 truncate">
                                    {routine.type === 'break' ? '🕐 Break' : routine.subjects?.name || 'Unknown'}
                                  </div>
                                  {routine.type !== 'break' && routine.teacher_name && (
                                    <div className="text-xs text-gray-600 truncate">
                                      {routine.teacher_name}
                                    </div>
                                  )}
                                  {routine.type !== 'break' && routine.room && (
                                    <div className="text-xs text-gray-600 truncate">
                                      Room: {routine.room}
                                    </div>
                                  )}
                                  <Button
                                    variant="ghost"
                                    size="icon"
                                    className="absolute -top-2 -right-2 h-6 w-6 text-gray-400 hover:text-red-600 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-opacity rounded"
                                    onClick={() => handleDelete(routine.id)}
                                  >
                                    <Trash2 className="w-3 h-3" />
                                  </Button>
                                </div>
                              ) : (
                                <div className="h-12 sm:h-16 bg-gray-50 rounded border border-dashed border-gray-200"></div>
                              )}
                            </td>
                          );
                        })}
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
