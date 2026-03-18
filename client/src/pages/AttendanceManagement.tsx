import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import {
  ClipboardCheck,
  Calendar,
  Loader2,
  Users,
  CheckCircle2,
  XCircle,
  Clock,
  Save,
  ChevronDown,
  ChevronRight
} from "lucide-react";
import { toast } from "sonner";

interface ClassItem {
  id: string;
  name: string;
}

interface Student {
  id: string;
  user_id: string;
  profiles: {
    first_name: string;
    last_name: string;
  };
}

interface AttendanceRecord {
  studentId: string;
  status: 'Present' | 'Absent' | 'Late';
}

type AttendanceStatus = 'Present' | 'Absent' | 'Late';

export default function AttendanceManagement() {
  const [classes, setClasses] = useState<ClassItem[]>([]);
  const [selectedClassId, setSelectedClassId] = useState<string>("");
  const [students, setStudents] = useState<Student[]>([]);
  const [attendance, setAttendance] = useState<Record<string, AttendanceStatus>>({});
  const [selectedDate, setSelectedDate] = useState<string>(new Date().toISOString().split('T')[0]);
  const [loading, setLoading] = useState(true);
  const [studentsLoading, setStudentsLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [showStudents, setShowStudents] = useState(true);

  useEffect(() => {
    fetchClasses();
  }, []);

  useEffect(() => {
    if (selectedClassId) {
      fetchStudents(selectedClassId);
    }
  }, [selectedClassId]);

  const fetchClasses = async () => {
    try {
      const data = await api.get<ClassItem[]>('/api/classes/simple');
      setClasses(data);
      if (data.length > 0) {
        setSelectedClassId(data[0].id);
      }
    } catch (error: any) {
      toast.error("Failed to load classes");
    } finally {
      setLoading(false);
    }
  };

  const fetchStudents = async (classId: string) => {
    setStudentsLoading(true);
    try {
      const data = await api.get<Student[]>(`/api/attendance/students?class_id=${classId}`);
      setStudents(data);
      // Initialize attendance with Present for all
      const initialAttendance: Record<string, AttendanceStatus> = {};
      data.forEach(student => {
        initialAttendance[student.id] = 'Present';
      });
      setAttendance(initialAttendance);
    } catch (error: any) {
      toast.error("Failed to load students");
    } finally {
      setStudentsLoading(false);
    }
  };

  const handleStatusChange = (studentId: string, status: AttendanceStatus) => {
    setAttendance(prev => ({
      ...prev,
      [studentId]: status
    }));
  };

  const handleMarkAll = (status: AttendanceStatus) => {
    const newAttendance: Record<string, AttendanceStatus> = {};
    students.forEach(student => {
      newAttendance[student.id] = status;
    });
    setAttendance(newAttendance);
  };

  const handleSave = async () => {
    if (!selectedClassId || students.length === 0) {
      toast.error("Please select a class with students");
      return;
    }

    setSaving(true);
    try {
      const records: AttendanceRecord[] = Object.entries(attendance).map(([studentId, status]) => ({
        studentId,
        status
      }));

      await api.post('/api/attendance', {
        records,
        date: selectedDate
      });

      toast.success("Attendance saved successfully");
    } catch (error: any) {
      toast.error("Failed to save attendance: " + error.message);
    } finally {
      setSaving(false);
    }
  };

  const getStatusIcon = (status: AttendanceStatus) => {
    switch (status) {
      case 'Present': return <CheckCircle2 className="w-4 h-4 text-green-500" />;
      case 'Absent': return <XCircle className="w-4 h-4 text-red-500" />;
      case 'Late': return <Clock className="w-4 h-4 text-amber-500" />;
    }
  };

  const getStatusCounts = () => {
    const counts = { Present: 0, Absent: 0, Late: 0 };
    Object.values(attendance).forEach(status => {
      counts[status]++;
    });
    return counts;
  };

  const counts = getStatusCounts();
  const selectedClass = classes.find(c => c.id === selectedClassId);

  const getInitials = (firstName: string, lastName: string) => {
    return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase() || '?';
  };

  const getAvatarColor = (index: number) => {
    const colors = ['bg-violet-500', 'bg-amber-500', 'bg-green-500', 'bg-blue-500', 'bg-pink-500'];
    return colors[index % 5];
  };

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header Section */}
      <div className="flex flex-col gap-4">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <div className="p-2.5 bg-violet-100 rounded-xl shadow-sm">
              <ClipboardCheck className="w-6 h-6 text-violet-600" />
            </div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
              Attendance Management
            </h1>
          </div>
          <p className="text-gray-500 text-sm sm:text-base ml-[3.25rem]">
            Mark and manage daily student attendance by class.
          </p>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-3 gap-3 sm:gap-4">
        <Card className="bg-green-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Present</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-green-600">{counts.Present}</p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-green-100 flex items-center justify-center">
              <CheckCircle2 className="w-5 h-5 sm:w-6 sm:h-6 text-green-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-red-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Absent</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-red-600">{counts.Absent}</p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-red-100 flex items-center justify-center">
              <XCircle className="w-5 h-5 sm:w-6 sm:h-6 text-red-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-amber-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Late</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-amber-600">{counts.Late}</p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-amber-100 flex items-center justify-center">
              <Clock className="w-5 h-5 sm:w-6 sm:h-6 text-amber-500" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Controls Card */}
      <Card className="border-0 shadow-sm rounded-2xl bg-white">
        <CardContent className="p-4 sm:p-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Class Select */}
            <div className="space-y-2">
              <Label className="text-sm font-medium">Select Class</Label>
              <Select value={selectedClassId} onValueChange={setSelectedClassId} disabled={loading}>
                <SelectTrigger className="bg-gray-50 rounded-xl h-11">
                  <SelectValue placeholder="Select a class" />
                </SelectTrigger>
                <SelectContent>
                  {classes.map((cls) => (
                    <SelectItem key={cls.id} value={cls.id}>{cls.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Date Select */}
            <div className="space-y-2">
              <Label className="text-sm font-medium">Date</Label>
              <div className="relative">
                <input
                  type="date"
                  value={selectedDate}
                  onChange={(e) => setSelectedDate(e.target.value)}
                  className="w-full h-11 px-3 bg-gray-50 border border-input rounded-xl text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500"
                />
              </div>
            </div>

            {/* Quick Actions */}
            <div className="space-y-2 sm:col-span-2">
              <Label className="text-sm font-medium">Quick Actions</Label>
              <div className="flex flex-wrap gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleMarkAll('Present')}
                  className="rounded-xl border-green-200 text-green-600 hover:bg-green-50"
                  disabled={students.length === 0}
                >
                  <CheckCircle2 className="w-4 h-4 mr-1" /> All Present
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleMarkAll('Absent')}
                  className="rounded-xl border-red-200 text-red-600 hover:bg-red-50"
                  disabled={students.length === 0}
                >
                  <XCircle className="w-4 h-4 mr-1" /> All Absent
                </Button>
                <Button
                  onClick={handleSave}
                  disabled={saving || students.length === 0}
                  className="rounded-xl bg-violet-600 hover:bg-violet-700 ml-auto"
                >
                  {saving ? (
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  ) : (
                    <Save className="w-4 h-4 mr-2" />
                  )}
                  Save Attendance
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Students List */}
      <Card className="border-0 shadow-sm rounded-2xl bg-white overflow-hidden">
        <button
          onClick={() => setShowStudents(!showStudents)}
          className="w-full px-4 sm:px-6 py-4 flex items-center justify-between bg-gray-50/50 border-b border-gray-100 hover:bg-gray-50 transition-colors"
        >
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-violet-100 flex items-center justify-center">
              <Users className="w-5 h-5 sm:w-6 sm:h-6 text-violet-600" />
            </div>
            <div className="text-left">
              <h3 className="text-base sm:text-lg font-bold text-gray-800">
                {selectedClass?.name || 'Select a class'}
              </h3>
              <p className="text-xs sm:text-sm text-gray-500">
                {students.length} student{students.length !== 1 ? 's' : ''}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="secondary" className="bg-violet-100 text-violet-700">
              {selectedDate}
            </Badge>
            {showStudents ? (
              <ChevronDown className="w-5 h-5 text-gray-400" />
            ) : (
              <ChevronRight className="w-5 h-5 text-gray-400" />
            )}
          </div>
        </button>

        {showStudents && (
          <CardContent className="p-0">
            {loading || studentsLoading ? (
              <div className="p-8 flex flex-col items-center justify-center">
                <Loader2 className="w-8 h-8 animate-spin text-violet-500 mb-4" />
                <p className="text-gray-500">Loading students...</p>
              </div>
            ) : students.length === 0 ? (
              <div className="p-8 flex flex-col items-center justify-center">
                <Users className="w-12 h-12 text-gray-200 mb-4" />
                <p className="text-gray-500">No students found in this class</p>
              </div>
            ) : (
              <div className="divide-y divide-gray-50">
                {students.map((student, index) => {
                  const status = attendance[student.id] || 'Present';
                  return (
                    <div
                      key={student.id}
                      className="px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between hover:bg-gray-50/50 transition-colors"
                    >
                      <div className="flex items-center gap-3 min-w-0 flex-1">
                        <div className={`w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold text-white shadow-sm shrink-0 ${getAvatarColor(index)}`}>
                          {getInitials(student.profiles.first_name, student.profiles.last_name)}
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="font-semibold text-gray-800 text-sm sm:text-base truncate">
                            {student.profiles.first_name} {student.profiles.last_name}
                          </p>
                          <div className="flex items-center gap-1 mt-0.5">
                            {getStatusIcon(status)}
                            <span className={`text-xs font-medium ${
                              status === 'Present' ? 'text-green-600' :
                              status === 'Absent' ? 'text-red-600' : 'text-amber-600'
                            }`}>
                              {status}
                            </span>
                          </div>
                        </div>
                      </div>

                      <div className="flex items-center gap-1 sm:gap-2">
                        <Button
                          variant={status === 'Present' ? 'default' : 'outline'}
                          size="sm"
                          onClick={() => handleStatusChange(student.id, 'Present')}
                          className={`rounded-lg w-8 h-8 sm:w-9 sm:h-9 p-0 ${
                            status === 'Present'
                              ? 'bg-green-500 hover:bg-green-600 border-green-500'
                              : 'border-gray-200 hover:border-green-300 hover:bg-green-50'
                          }`}
                        >
                          <CheckCircle2 className={`w-4 h-4 ${status === 'Present' ? 'text-white' : 'text-green-500'}`} />
                        </Button>
                        <Button
                          variant={status === 'Absent' ? 'default' : 'outline'}
                          size="sm"
                          onClick={() => handleStatusChange(student.id, 'Absent')}
                          className={`rounded-lg w-8 h-8 sm:w-9 sm:h-9 p-0 ${
                            status === 'Absent'
                              ? 'bg-red-500 hover:bg-red-600 border-red-500'
                              : 'border-gray-200 hover:border-red-300 hover:bg-red-50'
                          }`}
                        >
                          <XCircle className={`w-4 h-4 ${status === 'Absent' ? 'text-white' : 'text-red-500'}`} />
                        </Button>
                        <Button
                          variant={status === 'Late' ? 'default' : 'outline'}
                          size="sm"
                          onClick={() => handleStatusChange(student.id, 'Late')}
                          className={`rounded-lg w-8 h-8 sm:w-9 sm:h-9 p-0 ${
                            status === 'Late'
                              ? 'bg-amber-500 hover:bg-amber-600 border-amber-500'
                              : 'border-gray-200 hover:border-amber-300 hover:bg-amber-50'
                          }`}
                        >
                          <Clock className={`w-4 h-4 ${status === 'Late' ? 'text-white' : 'text-amber-500'}`} />
                        </Button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        )}
      </Card>

      {/* Footer */}
      {!loading && students.length > 0 && (
        <div className="text-center text-xs sm:text-sm text-gray-500 py-3">
          Total: {students.length} students | Present: {counts.Present} | Absent: {counts.Absent} | Late: {counts.Late}
        </div>
      )}
    </div>
  );
}
