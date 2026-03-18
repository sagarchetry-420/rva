import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import {
  FileText,
  Plus,
  Loader2,
  Trash2,
  Calendar,
  Clock,
  BookOpen,
  School,
  ChevronDown,
  ChevronRight,
  GraduationCap
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

interface Exam {
  id: string;
  name: string;
  exam_date: string;
  start_time: string;
  end_time: string;
  total_marks: number;
  description: string | null;
  class_id: string;
  subject_id: string;
  classes: { id: string; name: string } | null;
  subjects: { id: string; name: string; code: string } | null;
}

export default function ExamManagement() {
  const [exams, setExams] = useState<Exam[]>([]);
  const [classes, setClasses] = useState<ClassItem[]>([]);
  const [subjects, setSubjects] = useState<Subject[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [expandedClasses, setExpandedClasses] = useState<Set<string>>(new Set());

  const [newExam, setNewExam] = useState({
    name: "",
    classId: "",
    subjectId: "",
    examDate: "",
    startTime: "",
    endTime: "",
    totalMarks: "100",
    description: ""
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [examsData, classesData, subjectsData] = await Promise.all([
        api.get<Exam[]>('/api/exams'),
        api.get<ClassItem[]>('/api/classes/simple'),
        api.get<Subject[]>('/api/classes/subjects')
      ]);
      setExams(examsData);
      setClasses(classesData);
      setSubjects(subjectsData);
      // Expand all classes by default
      const allClassIds = new Set(classesData.map((c: ClassItem) => c.id));
      setExpandedClasses(allClassIds);
    } catch (error: any) {
      toast.error("Failed to load data");
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!newExam.name || !newExam.classId || !newExam.subjectId || !newExam.examDate || !newExam.startTime || !newExam.endTime) {
      toast.error("Please fill all required fields");
      return;
    }

    setIsSubmitting(true);
    try {
      await api.post('/api/exams', {
        name: newExam.name,
        classId: newExam.classId,
        subjectId: newExam.subjectId,
        examDate: newExam.examDate,
        startTime: newExam.startTime,
        endTime: newExam.endTime,
        totalMarks: parseInt(newExam.totalMarks) || 100,
        description: newExam.description || null
      });

      toast.success("Exam created successfully");
      setNewExam({
        name: "",
        classId: "",
        subjectId: "",
        examDate: "",
        startTime: "",
        endTime: "",
        totalMarks: "100",
        description: ""
      });
      setShowForm(false);
      fetchData();
    } catch (error: any) {
      toast.error("Failed to create exam: " + error.message);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (examId: string) => {
    if (!confirm("Are you sure you want to delete this exam?")) return;

    try {
      await api.delete(`/api/exams/${examId}`);
      toast.success("Exam deleted successfully");
      setExams(exams.filter(e => e.id !== examId));
    } catch (error: any) {
      toast.error("Failed to delete exam");
    }
  };

  const toggleClass = (classId: string) => {
    const newExpanded = new Set(expandedClasses);
    if (newExpanded.has(classId)) {
      newExpanded.delete(classId);
    } else {
      newExpanded.add(classId);
    }
    setExpandedClasses(newExpanded);
  };

  // Group exams by class
  const examsByClass = classes.map(cls => ({
    class: cls,
    exams: exams.filter(exam => exam.class_id === cls.id)
  })).filter(group => group.exams.length > 0);

  const upcomingExams = exams.filter(exam => new Date(exam.exam_date) >= new Date()).length;
  const totalExams = exams.length;

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString(undefined, {
      weekday: 'short',
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const formatTime = (timeStr: string) => {
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
  };

  const getColorForIndex = (index: number) => {
    const colors = [
      { bg: 'bg-violet-100', text: 'text-violet-600', badge: 'bg-violet-50 text-violet-700' },
      { bg: 'bg-amber-100', text: 'text-amber-600', badge: 'bg-amber-50 text-amber-700' },
      { bg: 'bg-green-100', text: 'text-green-600', badge: 'bg-green-50 text-green-700' }
    ];
    return colors[index % 3];
  };

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header Section */}
      <div className="flex flex-col gap-4">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <div className="p-2.5 bg-violet-100 rounded-xl shadow-sm">
              <FileText className="w-6 h-6 text-violet-600" />
            </div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
              Exam Management
            </h1>
          </div>
          <p className="text-gray-500 text-sm sm:text-base ml-[3.25rem]">
            Schedule and manage examinations for all classes.
          </p>
        </div>

        <Button
          onClick={() => setShowForm(!showForm)}
          className="gap-2 bg-violet-600 hover:bg-violet-700 shadow-sm rounded-xl h-10 sm:h-11 w-full sm:w-auto sm:self-start"
        >
          <Plus className="w-4 h-4" /> {showForm ? 'Hide Form' : 'Schedule Exam'}
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <Card className="bg-violet-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Exams</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-violet-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : totalExams}
              </p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-violet-100 flex items-center justify-center">
              <FileText className="w-5 h-5 sm:w-6 sm:h-6 text-violet-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-amber-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Upcoming</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-amber-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : upcomingExams}
              </p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-amber-100 flex items-center justify-center">
              <Calendar className="w-5 h-5 sm:w-6 sm:h-6 text-amber-500" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-green-50/80 border border-black/5 hover:shadow-md transition-all col-span-2 sm:col-span-1">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Classes</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-green-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : examsByClass.length}
              </p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-green-100 flex items-center justify-center">
              <School className="w-5 h-5 sm:w-6 sm:h-6 text-green-500" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Add Exam Form */}
      {showForm && (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardHeader className="pb-4">
            <CardTitle className="flex items-center gap-2 text-lg">
              <Plus className="w-5 h-5" /> Schedule New Exam
            </CardTitle>
            <CardDescription>Fill in the details to schedule a new examination</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Exam Name *</Label>
                  <Input
                    placeholder="e.g. Mid-Term Examination"
                    value={newExam.name}
                    onChange={(e) => setNewExam({ ...newExam, name: e.target.value })}
                    className="rounded-xl"
                  />
                </div>

                <div className="space-y-2">
                  <Label>Class *</Label>
                  <Select
                    value={newExam.classId}
                    onValueChange={(val) => setNewExam({ ...newExam, classId: val })}
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
                    value={newExam.subjectId}
                    onValueChange={(val) => setNewExam({ ...newExam, subjectId: val })}
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
                  <Label>Exam Date *</Label>
                  <Input
                    type="date"
                    value={newExam.examDate}
                    onChange={(e) => setNewExam({ ...newExam, examDate: e.target.value })}
                    className="rounded-xl"
                  />
                </div>

                <div className="space-y-2">
                  <Label>Start Time *</Label>
                  <Input
                    type="time"
                    value={newExam.startTime}
                    onChange={(e) => setNewExam({ ...newExam, startTime: e.target.value })}
                    className="rounded-xl"
                  />
                </div>

                <div className="space-y-2">
                  <Label>End Time *</Label>
                  <Input
                    type="time"
                    value={newExam.endTime}
                    onChange={(e) => setNewExam({ ...newExam, endTime: e.target.value })}
                    className="rounded-xl"
                  />
                </div>

                <div className="space-y-2">
                  <Label>Total Marks</Label>
                  <Input
                    type="number"
                    placeholder="100"
                    value={newExam.totalMarks}
                    onChange={(e) => setNewExam({ ...newExam, totalMarks: e.target.value })}
                    className="rounded-xl"
                  />
                </div>

                <div className="space-y-2 sm:col-span-2">
                  <Label>Description (Optional)</Label>
                  <Textarea
                    placeholder="Additional notes about the exam..."
                    value={newExam.description}
                    onChange={(e) => setNewExam({ ...newExam, description: e.target.value })}
                    className="rounded-xl min-h-[80px]"
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
                  Schedule Exam
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {/* Exams List by Class */}
      {loading ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 flex flex-col items-center justify-center">
            <Loader2 className="w-8 h-8 animate-spin text-violet-500 mb-4" />
            <p className="text-gray-500">Loading exams...</p>
          </CardContent>
        </Card>
      ) : examsByClass.length === 0 ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 flex flex-col items-center justify-center">
            <FileText className="w-12 h-12 text-gray-200 mb-4" />
            <p className="text-lg font-semibold text-gray-700">No exams scheduled</p>
            <p className="text-gray-500 mt-1 text-center">
              Click "Schedule Exam" to create your first examination.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-3 sm:space-y-4">
          {examsByClass.map(({ class: cls, exams: classExams }, index) => {
            const colors = getColorForIndex(index);
            const isExpanded = expandedClasses.has(cls.id);

            return (
              <Card
                key={cls.id}
                className="border-0 shadow-sm rounded-2xl bg-white overflow-hidden hover:shadow-md transition-shadow"
              >
                {/* Class Header */}
                <button
                  onClick={() => toggleClass(cls.id)}
                  className="w-full px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between bg-gray-50/50 border-b border-gray-100 hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-center gap-3 sm:gap-4">
                    <div className={`w-10 h-10 sm:w-12 sm:h-12 rounded-xl ${colors.bg} flex items-center justify-center`}>
                      <School className={`w-5 h-5 sm:w-6 sm:h-6 ${colors.text}`} />
                    </div>
                    <div className="text-left">
                      <h3 className="text-base sm:text-lg font-bold text-gray-800">{cls.name}</h3>
                      <p className="text-xs sm:text-sm text-gray-500">
                        {classExams.length} exam{classExams.length !== 1 ? 's' : ''}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 sm:gap-3">
                    <Badge className={colors.badge}>
                      {classExams.length}
                    </Badge>
                    {isExpanded ? (
                      <ChevronDown className="w-4 h-4 sm:w-5 sm:h-5 text-gray-400" />
                    ) : (
                      <ChevronRight className="w-4 h-4 sm:w-5 sm:h-5 text-gray-400" />
                    )}
                  </div>
                </button>

                {/* Exams List */}
                {isExpanded && (
                  <CardContent className="p-0">
                    <div className="divide-y divide-gray-50">
                      {classExams.map((exam) => (
                        <div
                          key={exam.id}
                          className="px-4 sm:px-6 py-4 hover:bg-gray-50/50 transition-colors"
                        >
                          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div className="flex-1 min-w-0">
                              <div className="flex items-center gap-2 mb-1">
                                <h4 className="font-semibold text-gray-800 truncate">{exam.name}</h4>
                                <Badge variant="outline" className="shrink-0 text-xs">
                                  {exam.total_marks} marks
                                </Badge>
                              </div>
                              <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                                <span className="flex items-center gap-1">
                                  <BookOpen className="w-3 h-3" />
                                  {exam.subjects?.name || 'Unknown Subject'}
                                </span>
                                <span className="flex items-center gap-1">
                                  <Calendar className="w-3 h-3" />
                                  {formatDate(exam.exam_date)}
                                </span>
                                <span className="flex items-center gap-1">
                                  <Clock className="w-3 h-3" />
                                  {formatTime(exam.start_time)} - {formatTime(exam.end_time)}
                                </span>
                              </div>
                              {exam.description && (
                                <p className="text-xs text-gray-400 mt-1 line-clamp-1">{exam.description}</p>
                              )}
                            </div>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl shrink-0"
                              onClick={() => handleDelete(exam.id)}
                            >
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                )}
              </Card>
            );
          })}
        </div>
      )}

      {/* Footer */}
      {!loading && totalExams > 0 && (
        <div className="text-center text-xs sm:text-sm text-gray-500 py-3">
          Showing {totalExams} exam{totalExams !== 1 ? 's' : ''} across {examsByClass.length} class{examsByClass.length !== 1 ? 'es' : ''}
        </div>
      )}
    </div>
  );
}
