import { useState, useEffect, useMemo } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
  CalendarDays,
  Plus,
  Loader2,
  ArrowLeft,
  X,
  Edit2
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
  classes?: {
    id: string;
    name: string;
  } | null;
}

interface Teacher {
  id: string;
  user_id: string;
  profiles?: {
    first_name: string;
    last_name: string;
  };
  teacher_subjects?: Array<{
    classes?: {
      id: string;
      name: string;
    };
    subjects?: {
      id: string;
      name: string;
      code: string;
    };
  }>;
}

interface PeriodEntry {
  tempId: string;
  dayOfWeek: number;
  startTime: string;
  endTime: string;
  subjectId: string;
  teacherId: string;
  room: string;
  type: 'period' | 'break';
}

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const SCHOOL_DAYS = [1, 2, 3, 4, 5, 6]; // Monday to Saturday

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

interface PeriodCellProps {
  period: PeriodEntry;
  isEditing: boolean;
  editingInput: { start: string; end: string } | undefined;
  updatePeriod: (tempId: string, field: string, value: string | number) => void;
  removePeriod: (tempId: string) => void;
  setEditingId: (id: string | null) => void;
  setEditingInputs: React.Dispatch<React.SetStateAction<{[tempId: string]: {start: string, end: string}}>>;
  getClassSubjects: () => Subject[];
  getTimeValidationError: (period: PeriodEntry) => string | null;
  validateStartTimeOnly: (period: PeriodEntry, dayOfWeek: number) => string | null;
  validateEndTimeOnly: (period: PeriodEntry, dayOfWeek: number) => string | null;
  formatTimeDisplay: (time24h: string) => string;
  parseTimeInput: (timeStr: string) => string | null;
  teachers: Teacher[];
  getTeacherName: (teacher: Teacher) => string;
  getTeachersForSubject: (subjectId: string) => Teacher[];
}

const PeriodCell = ({
  period,
  isEditing,
  editingInput,
  updatePeriod,
  removePeriod,
  setEditingId,
  setEditingInputs,
  getClassSubjects,
  getTimeValidationError,
  validateStartTimeOnly,
  validateEndTimeOnly,
  formatTimeDisplay,
  parseTimeInput,
  teachers,
  getTeacherName,
  getTeachersForSubject,
}: PeriodCellProps) => {
  const hasError = getTimeValidationError(period);

  if (!isEditing) {
    return (
      <div className={`p-3 rounded-lg border-2 ${
        period.type === 'break'
          ? 'bg-amber-50 border-amber-200'
          : 'bg-orange-50 border-orange-200'
      } ${hasError ? 'border-red-500 bg-red-50' : ''} flex flex-col justify-between text-xs h-full`}>
        {period.startTime && period.endTime && (
          <div>
            <p className="font-semibold text-base sm:text-sm">{period.startTime} - {period.endTime}</p>
            {period.type === 'period' ? (
              <>
                <p className="text-gray-600 text-xs sm:text-xs mt-1">{getClassSubjects().find(s => s.id === period.subjectId)?.name || 'N/A'}</p>
                {period.teacherId && (
                  <p className="text-gray-500 text-xs sm:text-xs mt-0.5">{teachers.find(t => t.id === period.teacherId) ? getTeacherName(teachers.find(t => t.id === period.teacherId)!) : ''}</p>
                )}
                {period.room && (
                  <p className="text-gray-500 text-xs sm:text-xs mt-0.5">Room: {period.room}</p>
                )}
              </>
            ) : (
              <p className="text-amber-600 font-semibold text-xs sm:text-xs mt-1">Break</p>
            )}
          </div>
        )}
        <div className="flex gap-2 mt-3">
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="h-9 sm:h-7 w-9 sm:w-7"
            onClick={() => setEditingId(period.tempId)}
          >
            <Edit2 className="w-3.5 h-3.5" />
          </Button>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="h-9 sm:h-7 w-9 sm:w-7 text-red-600 hover:bg-red-50"
            onClick={() => removePeriod(period.tempId)}
          >
            <X className="w-3.5 h-3.5" />
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="p-3 border-2 border-blue-500 bg-blue-50 rounded-lg space-y-3 sm:space-y-2 text-xs">
      <div>
        <Label className="text-xs sm:text-sm font-semibold mb-1.5 block">Start Time * <span className="text-gray-400 text-xs font-normal">(12:30PM)</span></Label>
        <Input
          type="text"
          placeholder="12:30PM"
          value={editingInput?.start ?? (period.startTime ? formatTimeDisplay(period.startTime) : '')}
          onChange={(e) => {
            const val = e.target.value;
            setEditingInputs(prev => ({
              ...prev,
              [period.tempId]: { ...prev[period.tempId], start: val }
            }));
            const parsed = parseTimeInput(val);
            if (parsed || val === '') {
              updatePeriod(period.tempId, 'startTime', parsed || '');
            }
          }}
          className={`h-10 sm:h-9 text-xs rounded ${!period.startTime ? 'border-red-300 bg-red-50' : validateStartTimeOnly(period, period.dayOfWeek) ? 'border-red-300 bg-red-50' : ''}`}
        />
        {!period.startTime && <p className="text-red-600 text-xs sm:text-sm mt-1">Start time is required</p>}
        {period.startTime && validateStartTimeOnly(period, period.dayOfWeek) && (
          <p className="text-red-600 text-xs sm:text-sm mt-1">{validateStartTimeOnly(period, period.dayOfWeek)}</p>
        )}
      </div>

      <div>
        <Label className="text-xs sm:text-sm font-semibold mb-1.5 block">End Time * <span className="text-gray-400 text-xs font-normal">(12:30PM)</span></Label>
        <Input
          type="text"
          placeholder="12:30PM"
          value={editingInput?.end ?? (period.endTime ? formatTimeDisplay(period.endTime) : '')}
          onChange={(e) => {
            const val = e.target.value;
            setEditingInputs(prev => ({
              ...prev,
              [period.tempId]: { ...prev[period.tempId], end: val }
            }));
            const parsed = parseTimeInput(val);
            if (parsed || val === '') {
              updatePeriod(period.tempId, 'endTime', parsed || '');
            }
          }}
          className={`h-10 sm:h-9 text-xs rounded ${!period.endTime ? 'border-red-300 bg-red-50' : validateEndTimeOnly(period, period.dayOfWeek) ? 'border-red-300 bg-red-50' : ''}`}
        />
        {!period.endTime && <p className="text-red-600 text-xs sm:text-sm mt-1">End time is required</p>}
        {period.endTime && validateEndTimeOnly(period, period.dayOfWeek) && (
          <p className="text-red-600 text-xs sm:text-sm mt-1">{validateEndTimeOnly(period, period.dayOfWeek)}</p>
        )}
      </div>

      {period.startTime && period.endTime && (
        <div className={`p-2 rounded text-xs ${
          getTimeValidationError(period)
            ? 'bg-red-50 border border-red-200 text-red-700'
            : 'bg-green-50 border border-green-200 text-green-700'
        }`}>
          {getTimeValidationError(period) ? (
            <p className="font-semibold">{getTimeValidationError(period)}</p>
          ) : (
            <p className="font-semibold">✓ Times are valid</p>
          )}
        </div>
      )}

      {period.type === 'period' && (
        <>
          <div>
            <Label className="text-xs sm:text-sm font-semibold mb-1.5 block">Subject *</Label>
            <Select value={period.subjectId} onValueChange={(val) => updatePeriod(period.tempId, 'subjectId', val)}>
              <SelectTrigger className={`h-10 sm:h-9 text-xs rounded ${!period.subjectId ? 'border-red-300 bg-red-50' : ''}`}>
                <SelectValue placeholder="Select Subject" />
              </SelectTrigger>
              <SelectContent>
                {getClassSubjects().map((subj) => (
                  <SelectItem key={subj.id} value={subj.id}>{subj.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            {!period.subjectId && <p className="text-red-600 text-xs sm:text-sm mt-1">Subject is required</p>}
          </div>

          <div>
            <Label className="text-xs sm:text-sm font-semibold mb-1.5 block">Teacher {period.subjectId && <span className="text-gray-500 text-xs font-normal">(Optional)</span>}</Label>
            <Select
              value={period.teacherId}
              onValueChange={(val) => updatePeriod(period.tempId, 'teacherId', val)}
              disabled={!period.subjectId}
            >
              <SelectTrigger className={`h-10 sm:h-9 text-xs rounded ${!period.subjectId ? 'opacity-50 cursor-not-allowed' : ''}`}>
                <SelectValue placeholder={period.subjectId ? "Select Teacher" : "Select a subject first"} />
              </SelectTrigger>
              <SelectContent>
                {getTeachersForSubject(period.subjectId).map((teacher) => (
                  <SelectItem key={teacher.id} value={teacher.id}>{getTeacherName(teacher)}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            {!period.subjectId && <p className="text-gray-500 text-xs sm:text-sm mt-1">Select a subject first to choose a teacher</p>}
          </div>

          <div>
            <Label className="text-xs sm:text-sm font-semibold mb-1.5 block">Room</Label>
            <Input
              autoFocus
              placeholder="Enter room number"
              value={period.room}
              onChange={(e) => updatePeriod(period.tempId, 'room', e.target.value)}
              className="h-10 sm:h-9 text-xs rounded"
            />
          </div>
        </>
      )}

      <Button
        type="button"
        variant="outline"
        size="sm"
        onClick={() => {
          setEditingId(null);
          setEditingInputs(prev => {
            const updated = { ...prev };
            delete updated[period.tempId];
            return updated;
          });
        }}
        className="w-full h-9 sm:h-8 text-xs sm:text-sm rounded"
      >
        Done
      </Button>
    </div>
  );
};

export default function CreateRoutine() {
  const navigate = useNavigate();
  const [classes, setClasses] = useState<ClassItem[]>([]);
  const [subjects, setSubjects] = useState<Subject[]>([]);
  const [teachers, setTeachers] = useState<Teacher[]>([]);
  const [selectedClassId, setSelectedClassId] = useState<string>("");
  const [selectedDayOfWeek, setSelectedDayOfWeek] = useState<number>(1); // Monday by default
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [periods, setPeriods] = useState<PeriodEntry[]>([]);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editingInputs, setEditingInputs] = useState<{[tempId: string]: {start: string, end: string}}>({});

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
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

  const addPeriod = () => {
    if (!selectedClassId) {
      toast.error("Please select a class first");
      return;
    }
    setPeriods([
      ...periods,
      {
        tempId: `temp-${Date.now()}-${Math.random()}`,
        dayOfWeek: selectedDayOfWeek,
        startTime: "",
        endTime: "",
        subjectId: "",
        teacherId: "",
        room: "",
        type: "period"
      }
    ]);
  };

  const addBreak = () => {
    if (!selectedClassId) {
      toast.error("Please select a class first");
      return;
    }
    setPeriods([
      ...periods,
      {
        tempId: `temp-${Date.now()}-${Math.random()}`,
        dayOfWeek: selectedDayOfWeek,
        startTime: "",
        endTime: "",
        subjectId: "",
        teacherId: "",
        room: "",
        type: "break"
      }
    ]);
  };

  const updatePeriod = (tempId: string, field: string, value: string | number) => {
    setPeriods(periods.map(p => p.tempId === tempId ? { ...p, [field]: value } : p));
  };

  const removePeriod = (tempId: string) => {
    setPeriods(periods.filter(p => p.tempId !== tempId));
    setEditingId(null);
  };

  const getTeacherName = (teacher: Teacher) => {
    if (teacher.profiles) {
      return `${teacher.profiles.first_name} ${teacher.profiles.last_name}`.trim();
    }
    return 'Unknown Teacher';
  };

  const getClassSubjects = () => {
    return subjects.filter(subj => subj.classes?.id === selectedClassId);
  };

  const getTeachersForSubject = (subjectId: string) => {
    if (!subjectId) return teachers;

    return teachers.filter(teacher => {
      if (!teacher.teacher_subjects) return false;
      return teacher.teacher_subjects.some(ts => ts.subjects?.id === subjectId);
    });
  };

  const parseTimeInput = (timeStr: string): string | null => {
    if (!timeStr) return null;

    const timeRegex = /^(\d{1,2}):(\d{2})\s*(AM|PM|am|pm)$/;
    const match = timeStr.match(timeRegex);

    if (!match) return null;

    let hours = parseInt(match[1]);
    const minutes = match[2];
    const period = match[3].toUpperCase();

    if (hours < 1 || hours > 12 || parseInt(minutes) > 59) return null;

    // Convert to 24-hour format
    if (period === 'PM' && hours !== 12) {
      hours += 12;
    } else if (period === 'AM' && hours === 12) {
      hours = 0;
    }

    return `${String(hours).padStart(2, '0')}:${minutes}`;
  };

  const formatTimeDisplay = (time24h: string): string => {
    if (!time24h) return '';

    const [hours, minutes] = time24h.split(':');
    let hour12 = parseInt(hours);
    const period = hour12 >= 12 ? 'PM' : 'AM';

    if (hour12 > 12) {
      hour12 -= 12;
    } else if (hour12 === 0) {
      hour12 = 12;
    }

    return `${hour12}:${minutes}${period}`;
  };

  const getPeriodNumberForDay = (tempId: string, dayOfWeek: number) => {
    const periodsForDay = periods.filter(p => p.dayOfWeek === dayOfWeek);
    const periodIndex = periodsForDay.findIndex(p => p.tempId === tempId);
    return periodIndex + 1;
  };

  const validateTime = (startTime: string, endTime: string): string | null => {
    if (!startTime) return "Start time is required";
    if (!endTime) return "End time is required";

    const start = new Date(`2000-01-01 ${startTime}`);
    const end = new Date(`2000-01-01 ${endTime}`);

    if (end <= start) return "End time must be after start time";

    // Check minimum duration (at least 15 minutes)
    const durationMinutes = (end.getTime() - start.getTime()) / (1000 * 60);
    if (durationMinutes < 15) return "Period must be at least 15 minutes long";

    return null;
  };

  const validateStartTimeOnly = (period: PeriodEntry, dayOfWeek: number): string | null => {
    if (!period.startTime) return null;

    const periodsOnSameDay = periods.filter(p => p.dayOfWeek === dayOfWeek && p.tempId !== period.tempId);

    for (const otherPeriod of periodsOnSameDay) {
      if (!otherPeriod.startTime || !otherPeriod.endTime) continue;

      const start = new Date(`2000-01-01 ${period.startTime}`);
      const otherStart = new Date(`2000-01-01 ${otherPeriod.startTime}`);
      const otherEnd = new Date(`2000-01-01 ${otherPeriod.endTime}`);

      // Check if start time falls within another period's time range
      if (start >= otherStart && start < otherEnd) {
        return `Start time conflicts with existing period (${otherPeriod.startTime} - ${otherPeriod.endTime})`;
      }
    }

    return null;
  };

  const validateEndTimeOnly = (period: PeriodEntry, dayOfWeek: number): string | null => {
    if (!period.endTime) return null;

    const periodsOnSameDay = periods.filter(p => p.dayOfWeek === dayOfWeek && p.tempId !== period.tempId);

    for (const otherPeriod of periodsOnSameDay) {
      if (!otherPeriod.startTime || !otherPeriod.endTime) continue;

      const end = new Date(`2000-01-01 ${period.endTime}`);
      const otherStart = new Date(`2000-01-01 ${otherPeriod.startTime}`);
      const otherEnd = new Date(`2000-01-01 ${otherPeriod.endTime}`);

      // Check if end time falls within another period's time range
      if (end > otherStart && end <= otherEnd) {
        return `End time conflicts with existing period (${otherPeriod.startTime} - ${otherPeriod.endTime})`;
      }
    }

    return null;
  };

  const validatePeriodTimes = (period: PeriodEntry, dayOfWeek: number): string | null => {
    const timeError = validateTime(period.startTime, period.endTime);
    if (timeError) return timeError;

    const periodsOnSameDay = periods.filter(p => p.dayOfWeek === dayOfWeek && p.tempId !== period.tempId);

    if (periodsOnSameDay.length > 0) {
      const start = new Date(`2000-01-01 ${period.startTime}`);
      const end = new Date(`2000-01-01 ${period.endTime}`);

      for (const otherPeriod of periodsOnSameDay) {
        if (!otherPeriod.startTime || !otherPeriod.endTime) continue;

        const otherStart = new Date(`2000-01-01 ${otherPeriod.startTime}`);
        const otherEnd = new Date(`2000-01-01 ${otherPeriod.endTime}`);

        // Check if periods overlap or are adjacent
        // No overlap should occur: current.start must be >= other.end AND current.end must be <= other.start
        // If start < otherEnd AND end > otherStart, they overlap
        if (start < otherEnd && end > otherStart) {
          // Check if start time is at or before another period's end time
          if (start <= otherEnd) {
            return `Start time (${period.startTime}) must be strictly after ${otherPeriod.endTime} (end time of another period on this day)`;
          }
          // Check if end time is at or after another period's start time
          if (end >= otherStart) {
            return `End time (${period.endTime}) must be strictly before ${otherPeriod.startTime} (start time of another period on this day)`;
          }
          return "Time overlaps with another period on this day";
        }
      }
    }

    return null;
  };

  const getTimeValidationError = (period: PeriodEntry): string | null => {
    return validatePeriodTimes(period, period.dayOfWeek);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedClassId) {
      toast.error("Please select a class");
      return;
    }

    if (periods.length === 0) {
      toast.error("Please add at least one period");
      return;
    }

    for (const period of periods) {
      if (!period.dayOfWeek) {
        toast.error("Please select a day for all entries");
        return;
      }
      if (!period.startTime || !period.endTime) {
        toast.error("Please fill start and end times for all entries");
        return;
      }

      const timeError = validatePeriodTimes(period, period.dayOfWeek);
      if (timeError) {
        toast.error(timeError);
        return;
      }

      if (period.type === 'period') {
        if (!period.subjectId) {
          toast.error("Please select a subject for all periods");
          return;
        }
      }
    }

    setIsSubmitting(true);
    try {
      const existingRoutines = await api.get<any[]>(`/api/routines?class_id=${selectedClassId}`);

      for (const routine of existingRoutines) {
        await api.delete(`/api/routines/${routine.id}`);
      }

      for (const period of periods) {
        await api.post('/api/routines', {
          classId: selectedClassId,
          subjectId: period.type === 'break' ? null : period.subjectId,
          teacherId: period.type === 'break' ? null : (period.teacherId || null),
          dayOfWeek: parseInt(period.dayOfWeek.toString()),
          startTime: period.startTime,
          endTime: period.endTime,
          room: period.type === 'break' ? null : (period.room || null),
          type: period.type
        });
      }

      toast.success("Weekly routine created successfully");
      navigate("/dashboard/routines");
    } catch (error: any) {
      toast.error("Failed to create routine: " + error.message);
    } finally {
      setIsSubmitting(false);
    }
  };

  const selectedClass = classes.find(c => c.id === selectedClassId);

  const sortedPeriodsForDay = useMemo(() => {
    return periods
      .filter(p => p.dayOfWeek === selectedDayOfWeek)
      .sort((a, b) => {
        if (!a.startTime || !b.startTime) return 0;
        return a.startTime.localeCompare(b.startTime);
      });
  }, [periods, selectedDayOfWeek]);

  return (
    <div className="min-h-screen bg-slate-50/50 p-3 sm:p-4 md:p-8 lg:p-12">
      <div className="max-w-full mx-auto space-y-4 sm:space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4 mb-4 sm:mb-6">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => navigate("/dashboard/routines")}
            className="text-gray-400 hover:text-gray-600 shrink-0"
          >
            <ArrowLeft className="w-5 h-5" />
          </Button>
          <div className="flex-1 min-w-0">
            <h1 className="text-lg sm:text-xl md:text-3xl font-extrabold text-gray-900 flex items-center gap-2 flex-wrap">
              <CalendarDays className="w-6 h-6 sm:w-8 sm:h-8 text-orange-600 shrink-0" />
              Create Weekly Routine
            </h1>
            <p className="text-gray-500 text-xs sm:text-sm mt-1">
              Add all periods for the week (Monday to Saturday)
            </p>
          </div>
        </div>

        {/* Class Selector Card */}
        <Card className="border-0 shadow-sm rounded-xl sm:rounded-2xl bg-white">
          <CardContent className="p-3 sm:p-4 md:p-6">
            <div className="space-y-2">
              <Label className="text-xs sm:text-sm font-medium">Select Class *</Label>
              <Select value={selectedClassId} onValueChange={setSelectedClassId} disabled={loading}>
                <SelectTrigger className="bg-gray-50 rounded-lg sm:rounded-xl h-10 sm:h-11 text-sm">
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
              <div className="mt-3 sm:mt-4 p-2.5 sm:p-3 bg-orange-50 rounded-lg sm:rounded-xl border border-orange-200">
                <p className="text-xs sm:text-sm font-medium text-orange-900">
                  Creating routine for: <span className="font-bold">{selectedClass.name}</span>
                </p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Table View */}
        {!loading && (
          <Card className="border-0 shadow-sm rounded-xl sm:rounded-2xl bg-white">
            <CardHeader className="pb-2 sm:pb-3 md:pb-4 px-3 sm:px-6 pt-3 sm:pt-6">
              <CardTitle className="text-base sm:text-lg">
                Weekly Timetable
              </CardTitle>
              <CardDescription className="text-xs sm:text-sm">
                Build your weekly schedule by adding periods and breaks
              </CardDescription>
            </CardHeader>
            <CardContent className="p-2 sm:p-4 md:p-6">
              <form onSubmit={handleSubmit} className="space-y-2 sm:space-y-4">
                {/* Instructions */}
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-2 sm:p-3 md:p-4 text-xs sm:text-sm text-blue-800">
                  <p className="font-semibold mb-1">How to use:</p>
                  <ul className="list-disc list-inside space-y-0.5 sm:space-y-1">
                    <li>Select a day using the day selector below</li>
                    <li>Click <strong>"+ Period"</strong> button to add a class period for that day</li>
                    <li>Click <strong>"+ Break"</strong> button to add a break time slot for that day</li>
                    <li>Click the edit icon on any cell to modify its details</li>
                    <li>Click the X icon on any cell to remove it</li>
                  </ul>
                </div>

                {/* Day Selector */}
                <div className="bg-gradient-to-r from-orange-50 to-orange-50 border border-orange-200 rounded-lg p-2 sm:p-3 md:p-4">
                  <Label className="text-xs sm:text-sm font-semibold text-gray-700 mb-3 block">Select Day to Edit *</Label>
                  <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                    {SCHOOL_DAYS.map((dayIndex) => (
                      <Button
                        key={dayIndex}
                        type="button"
                        variant={selectedDayOfWeek === dayIndex ? "default" : "outline"}
                        onClick={() => setSelectedDayOfWeek(dayIndex)}
                        className={`text-xs sm:text-sm h-9 sm:h-8 rounded-lg transition-colors ${
                          selectedDayOfWeek === dayIndex
                            ? 'bg-orange-600 hover:bg-orange-700 text-white'
                            : 'hover:bg-orange-100'
                        }`}
                      >
                        {DAYS[dayIndex]}
                      </Button>
                    ))}
                  </div>
                  {periods.filter(p => p.dayOfWeek === selectedDayOfWeek).length > 0 && (
                    <p className="text-xs text-orange-600 mt-2 font-medium">
                      {periods.filter(p => p.dayOfWeek === selectedDayOfWeek).length} item(s) for {DAYS[selectedDayOfWeek]}
                    </p>
                  )}
                </div>

                {/* Periods List for Selected Day */}
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <h3 className="text-sm sm:text-base font-semibold text-gray-900">
                      {DAYS[selectedDayOfWeek]}'s Schedule
                    </h3>
                    <div className="flex gap-2">
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addPeriod}
                        className="h-9 sm:h-8 px-3 sm:px-4 rounded-lg text-xs gap-1.5"
                        title="Add a new period"
                      >
                        <Plus className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Period</span>
                      </Button>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addBreak}
                        className="h-9 sm:h-8 px-3 sm:px-4 rounded-lg text-xs gap-1.5"
                        title="Add a new break"
                      >
                        <Plus className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Break</span>
                      </Button>
                    </div>
                  </div>

                  {periods.filter(p => p.dayOfWeek === selectedDayOfWeek).length === 0 ? (
                    <div className="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed">
                      <p className="text-sm">No periods or breaks added for {DAYS[selectedDayOfWeek]} yet</p>
                      <p className="text-xs text-gray-400 mt-1">Click the + buttons above to add one</p>
                    </div>
                  ) : (
                    <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                      {sortedPeriodsForDay.map((period) => (
                        <PeriodCell
                          key={period.tempId}
                          period={period}
                          isEditing={editingId === period.tempId}
                          editingInput={editingInputs[period.tempId]}
                          updatePeriod={updatePeriod}
                          removePeriod={removePeriod}
                          setEditingId={setEditingId}
                          setEditingInputs={setEditingInputs}
                          getClassSubjects={getClassSubjects}
                          getTimeValidationError={getTimeValidationError}
                          validateStartTimeOnly={validateStartTimeOnly}
                          validateEndTimeOnly={validateEndTimeOnly}
                          formatTimeDisplay={formatTimeDisplay}
                          parseTimeInput={parseTimeInput}
                          teachers={teachers}
                          getTeacherName={getTeacherName}
                          getTeachersForSubject={getTeachersForSubject}
                        />
                      ))}
                    </div>
                  )}
                </div>

                {/* Progress Table */}
                {periods.length > 0 && (
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <h3 className="text-sm sm:text-base font-semibold text-gray-900">
                        Routine Preview
                      </h3>
                      <span className="text-xs sm:text-sm text-gray-500">
                        {periods.filter(p => p.type === 'period').length} periods, {periods.filter(p => p.type === 'break').length} breaks
                      </span>
                    </div>

                    <div className="overflow-x-auto border rounded-lg -mx-3 sm:-mx-4 md:-mx-6 lg:mx-0">
                      <div className="px-3 sm:px-4 md:px-6 lg:px-0">
                        <table className="w-full">
                        <thead>
                          <tr className="bg-gray-100 border-b">
                            <th className="px-2 sm:px-3 py-2 text-left text-xs sm:text-sm font-semibold text-gray-700 w-16 sm:w-20 md:w-24">Day</th>
                            <th className="px-2 sm:px-3 py-2 text-center text-xs sm:text-sm font-semibold text-gray-700">Periods</th>
                          </tr>
                        </thead>
                        <tbody>
                          {SCHOOL_DAYS.map((dayIndex) => {
                            const dayPeriods = periods.filter(p => p.dayOfWeek === dayIndex).sort((a, b) => {
                              if (!a.startTime || !b.startTime) return 0;
                              return a.startTime.localeCompare(b.startTime);
                            });
                            return (
                              <tr key={dayIndex} className="border-b hover:bg-gray-50">
                                <td className="px-1 sm:px-3 py-1 sm:py-2 bg-gray-50 font-semibold text-xs sm:text-sm text-gray-700">{DAYS[dayIndex]}</td>
                                <td className="px-1 sm:px-3 py-2">
                                  <div className="flex gap-1 sm:gap-2 overflow-x-auto pb-2">
                                    {dayPeriods.map((period) => (
                                      <div key={period.tempId} className={`p-1 sm:p-2 md:p-3 rounded border-2 text-xs whitespace-nowrap flex-shrink-0 min-w-fit ${
                                        getTimeValidationError(period)
                                          ? 'bg-red-50 border-red-300'
                                          : period.type === 'break'
                                          ? 'bg-amber-50 border-amber-200'
                                          : 'bg-orange-50 border-orange-200'
                                      } min-h-14 sm:min-h-20 md:min-h-24 flex flex-col justify-between`}>
                                        {getTimeValidationError(period) && (
                                          <div className="bg-red-100 text-red-700 p-0.5 rounded mb-0.5 sm:mb-2 border border-red-200 text-center">
                                            <p className="text-xs font-bold">⚠</p>
                                          </div>
                                        )}
                                        {period.startTime && period.endTime && (
                                          <div className="text-xs sm:text-sm">
                                            <p className="font-semibold leading-tight">{period.startTime} - {period.endTime}</p>
                                            {period.type === 'period' ? (
                                              <>
                                                <p className="text-gray-600 text-xs mt-0.5 truncate leading-tight">
                                                  {getClassSubjects().find(s => s.id === period.subjectId)?.name || 'N/A'}
                                                </p>
                                                {period.teacherId && (
                                                  <p className="text-gray-500 text-xs mt-0.5 truncate hidden sm:block leading-tight">{teachers.find(t => t.id === period.teacherId) ? getTeacherName(teachers.find(t => t.id === period.teacherId)!) : ''}</p>
                                                )}
                                              </>
                                            ) : (
                                              <p className="text-amber-600 font-semibold text-xs mt-0.5">Break</p>
                                            )}
                                          </div>
                                        )}
                                      </div>
                                    ))}
                                  </div>
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                )}

                {/* Submit Buttons */}
                <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 sm:pt-4 border-t">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => navigate("/dashboard/routines")}
                    className="rounded-lg sm:rounded-xl h-9 sm:h-10 text-xs sm:text-sm"
                  >
                    Cancel
                  </Button>
                  <Button
                    type="submit"
                    disabled={isSubmitting || !selectedClassId || periods.length === 0}
                    className="rounded-lg sm:rounded-xl h-9 sm:h-10 text-xs sm:text-sm bg-orange-600 hover:bg-orange-700"
                  >
                    {isSubmitting ? (
                      <>
                        <Loader2 className="w-3.5 h-3.5 animate-spin mr-1" />
                        Creating...
                      </>
                    ) : (
                      <>
                        <Plus className="w-3.5 h-3.5 mr-1" />
                        Create Routine
                      </>
                    )}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        )}

        {loading && (
          <Card className="border-0 shadow-sm rounded-xl sm:rounded-2xl bg-white">
            <CardContent className="p-6 sm:p-12 flex items-center justify-center min-h-[200px]">
              <div className="flex flex-col items-center gap-2 sm:gap-3">
                <Loader2 className="w-6 h-6 sm:w-8 sm:h-8 animate-spin text-orange-500" />
                <p className="text-xs sm:text-sm text-gray-500">Loading data...</p>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}
