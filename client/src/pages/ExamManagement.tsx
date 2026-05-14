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
  FileText, Plus, Loader2, Trash2, Calendar, Clock,
  BookOpen, School, ChevronDown, ChevronRight, GraduationCap, CheckCircle
} from "lucide-react";
import { toast } from "sonner";

interface ClassItem { id: string; name: string; }
interface Subject { id: string; name: string; code: string; class_id?: string; }

interface SubjectSchedule {
  subjectId: string;
  subjectName: string;
  subjectCode: string;
  examDate: string;
  startTime: string;
  endTime: string;
  totalMarks: string;
  passingMarks: string;
}

interface ExamSubject {
  id: string;
  subject_id: string;
  exam_date: string;
  start_time: string;
  end_time: string;
  total_marks: number;
  passing_marks: number;
  subjects: { id: string; name: string; code: string } | null;
}

interface Exam {
  id: string;
  name: string;
  description: string | null;
  class_id: string;
  created_at: string;
  classes: { id: string; name: string } | null;
  exam_subjects: ExamSubject[];
}

export default function ExamManagement() {
  const [exams, setExams] = useState<Exam[]>([]);
  const [classes, setClasses] = useState<ClassItem[]>([]);
  const [allSubjects, setAllSubjects] = useState<Subject[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [showPastExams, setShowPastExams] = useState(false);
  const [expandedExams, setExpandedExams] = useState<Set<string>>(new Set());

  const [examName, setExamName] = useState("");
  const [selectedClassId, setSelectedClassId] = useState("");
  const [description, setDescription] = useState("");
  const [subjectSchedules, setSubjectSchedules] = useState<SubjectSchedule[]>([]);

  useEffect(() => { fetchData(); }, []);

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
      setAllSubjects(subjectsData);
      const allIds = new Set(examsData.map((e: Exam) => e.id));
      setExpandedExams(allIds);
    } catch { toast.error("Failed to load data"); }
    finally { setLoading(false); }
  };

  const handleClassSelect = (classId: string) => {
    setSelectedClassId(classId);
    const classSubjects = allSubjects.filter(s => s.class_id === classId);
    setSubjectSchedules(classSubjects.map(s => ({
      subjectId: s.id,
      subjectName: s.name,
      subjectCode: s.code,
      examDate: "",
      startTime: "",
      endTime: "",
      totalMarks: "100",
      passingMarks: "33"
    })));
  };

  const updateSchedule = (index: number, field: keyof SubjectSchedule, value: string) => {
    const updated = [...subjectSchedules];
    updated[index] = { ...updated[index], [field]: value };
    setSubjectSchedules(updated);
  };

  const applyToAll = (field: 'examDate' | 'startTime' | 'endTime' | 'totalMarks' | 'passingMarks', value: string) => {
    if (!value) return;
    setSubjectSchedules(prev => prev.map(s => ({ ...s, [field]: value })));
    toast.success(`Applied to all subjects`);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!examName || !selectedClassId || subjectSchedules.length === 0) {
      toast.error("Please fill exam name and select a class"); return;
    }
    const incomplete = subjectSchedules.find(s => !s.examDate || !s.startTime || !s.endTime);
    if (incomplete) {
      toast.error(`Please fill all schedule fields for ${incomplete.subjectName}`); return;
    }
    setIsSubmitting(true);
    try {
      await api.post('/api/exams', {
        name: examName,
        classId: selectedClassId,
        description: description || null,
        subjects: subjectSchedules.map(s => ({
          subjectId: s.subjectId,
          examDate: s.examDate,
          startTime: s.startTime,
          endTime: s.endTime,
          totalMarks: parseInt(s.totalMarks) || 100,
          passingMarks: parseInt(s.passingMarks) || 33
        }))
      });
      toast.success("Exam scheduled successfully!");
      setExamName(""); setSelectedClassId(""); setDescription("");
      setSubjectSchedules([]); setShowForm(false);
      fetchData();
    } catch (error: any) { toast.error("Failed to create exam: " + error.message); }
    finally { setIsSubmitting(false); }
  };

  const handleDelete = async (examId: string) => {
    if (!confirm("Delete this exam and all its subject schedules?")) return;
    try {
      await api.delete(`/api/exams/${examId}`);
      toast.success("Exam deleted");
      setExams(exams.filter(e => e.id !== examId));
    } catch { toast.error("Failed to delete exam"); }
  };

  const toggleExam = (id: string) => {
    const s = new Set(expandedExams);
    s.has(id) ? s.delete(id) : s.add(id);
    setExpandedExams(s);
  };

  const formatDate = (d: string) => new Date(d).toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
  const formatTime = (t: string) => { const [h, m] = t.split(':'); const hr = parseInt(h); return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`; };

  const activeExams = showPastExams ? exams : exams.filter(e => {
    if (!e.exam_subjects || e.exam_subjects.length === 0) return true;
    return e.exam_subjects.some(s => new Date(s.exam_date) >= new Date(new Date().setHours(0,0,0,0)));
  });

  const examsByClass = classes.map(cls => ({
    class: cls,
    exams: activeExams.filter(e => e.class_id === cls.id)
  })).filter(g => g.exams.length > 0);

  const totalExams = exams.length;
  const totalSubjectExams = exams.reduce((acc, e) => acc + (e.exam_subjects?.length || 0), 0);
  const upcomingCount = exams.reduce((acc, e) => acc + (e.exam_subjects?.filter(s => new Date(s.exam_date) >= new Date()).length || 0), 0);

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <div className="p-2.5 bg-orange-100 rounded-xl shadow-sm">
              <FileText className="w-6 h-6 text-orange-600" />
            </div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
              Exam Management
            </h1>
          </div>
          <p className="text-gray-500 text-sm sm:text-base ml-[3.25rem]">
            Schedule and manage examinations with all subjects for each class.
          </p>
        </div>
        <div className="flex flex-col sm:flex-row gap-3 sm:items-center">
          <Button onClick={() => setShowForm(!showForm)} className="gap-2 bg-orange-600 hover:bg-orange-700 shadow-sm rounded-xl h-10 sm:h-11 w-full sm:w-auto">
            <Plus className="w-4 h-4" /> {showForm ? 'Hide Form' : 'Schedule Exam'}
          </Button>
          <Button variant="outline" onClick={() => setShowPastExams(!showPastExams)} className="h-10 sm:h-11 rounded-xl">
            {showPastExams ? 'Hide Finished Exams' : 'Show Finished Exams'}
          </Button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <Card className="bg-orange-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Total Exams</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-orange-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : totalExams}
              </p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-orange-100 flex items-center justify-center">
              <FileText className="w-5 h-5 sm:w-6 sm:h-6 text-orange-500" />
            </div>
          </CardContent>
        </Card>
        <Card className="bg-amber-50/80 border border-black/5 hover:shadow-md transition-all">
          <CardContent className="p-3 sm:p-4 md:p-5 flex items-center justify-between">
            <div>
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Upcoming</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-amber-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : upcomingCount}
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
              <p className="text-xs sm:text-sm font-semibold text-gray-600 mb-1">Subject Exams</p>
              <p className="text-xl sm:text-2xl md:text-3xl font-extrabold text-green-600">
                {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : totalSubjectExams}
              </p>
            </div>
            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-green-100 flex items-center justify-center">
              <BookOpen className="w-5 h-5 sm:w-6 sm:h-6 text-green-500" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Schedule Form */}
      {showForm && (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardHeader className="pb-4">
            <CardTitle className="flex items-center gap-2 text-lg">
              <Plus className="w-5 h-5" /> Schedule New Exam
            </CardTitle>
            <CardDescription>Select a class to auto-populate all its subjects. Fill schedule for each.</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-5">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Exam Name *</Label>
                  <Input placeholder="e.g. Mid-Term Examination" value={examName} onChange={e => setExamName(e.target.value)} className="rounded-xl" />
                </div>
                <div className="space-y-2">
                  <Label>Class *</Label>
                  <Select value={selectedClassId} onValueChange={handleClassSelect}>
                    <SelectTrigger className="rounded-xl"><SelectValue placeholder="Select class" /></SelectTrigger>
                    <SelectContent>
                      {classes.map(c => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2 sm:col-span-2">
                  <Label>Description (Optional)</Label>
                  <Textarea placeholder="Additional notes..." value={description} onChange={e => setDescription(e.target.value)} className="rounded-xl min-h-[60px]" />
                </div>
              </div>

              {/* Subject Schedules */}
              {subjectSchedules.length > 0 && (
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <h3 className="font-semibold text-gray-800 flex items-center gap-2">
                      <BookOpen className="w-4 h-4 text-orange-500" />
                      Subject Schedules ({subjectSchedules.length} subjects)
                    </h3>
                  </div>

                  {/* Apply-to-all row */}
                  <div className="p-3 bg-orange-50 rounded-xl border border-orange-100">
                    <p className="text-xs font-semibold text-orange-700 mb-2">Quick Fill — Apply to all subjects:</p>
                    <div className="grid grid-cols-2 sm:grid-cols-5 gap-2">
                      <div>
                        <Label className="text-xs text-gray-500">Date</Label>
                        <Input type="date" className="rounded-lg text-sm h-9" onChange={e => applyToAll('examDate', e.target.value)} />
                      </div>
                      <div>
                        <Label className="text-xs text-gray-500">Start</Label>
                        <Input type="time" className="rounded-lg text-sm h-9" onChange={e => applyToAll('startTime', e.target.value)} />
                      </div>
                      <div>
                        <Label className="text-xs text-gray-500">End</Label>
                        <Input type="time" className="rounded-lg text-sm h-9" onChange={e => applyToAll('endTime', e.target.value)} />
                      </div>
                      <div>
                        <Label className="text-xs text-gray-500">Total</Label>
                        <Input type="number" placeholder="100" className="rounded-lg text-sm h-9" onChange={e => applyToAll('totalMarks', e.target.value)} />
                      </div>
                      <div>
                        <Label className="text-xs text-gray-500">Pass</Label>
                        <Input type="number" placeholder="33" className="rounded-lg text-sm h-9" onChange={e => applyToAll('passingMarks', e.target.value)} />
                      </div>
                    </div>
                  </div>

                  {/* Individual subject rows */}
                  <div className="space-y-2">
                    {subjectSchedules.map((s, i) => (
                      <div key={s.subjectId} className="p-3 border rounded-xl bg-gray-50/50 hover:bg-gray-50 transition-colors">
                        <div className="flex items-center gap-2 mb-2">
                          <Badge variant="outline" className="text-xs font-semibold">{s.subjectCode}</Badge>
                          <span className="font-medium text-sm text-gray-800">{s.subjectName}</span>
                        </div>
                        <div className="grid grid-cols-2 sm:grid-cols-5 gap-2">
                          <Input type="date" value={s.examDate} onChange={e => updateSchedule(i, 'examDate', e.target.value)} className="rounded-lg text-sm h-9" />
                          <Input type="time" value={s.startTime} onChange={e => updateSchedule(i, 'startTime', e.target.value)} className="rounded-lg text-sm h-9" />
                          <Input type="time" value={s.endTime} onChange={e => updateSchedule(i, 'endTime', e.target.value)} className="rounded-lg text-sm h-9" />
                          <Input type="number" value={s.totalMarks} onChange={e => updateSchedule(i, 'totalMarks', e.target.value)} placeholder="Total" className="rounded-lg text-sm h-9" />
                          <Input type="number" value={s.passingMarks} onChange={e => updateSchedule(i, 'passingMarks', e.target.value)} placeholder="Pass" className="rounded-lg text-sm h-9" />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {selectedClassId && subjectSchedules.length === 0 && (
                <div className="text-center py-6 border-2 border-dashed rounded-xl">
                  <BookOpen className="w-8 h-8 text-gray-300 mx-auto mb-2" />
                  <p className="text-sm text-gray-500">No subjects found for this class. Add subjects first.</p>
                </div>
              )}

              <div className="flex justify-end gap-3 pt-2">
                <Button type="button" variant="outline" onClick={() => setShowForm(false)} className="rounded-xl">Cancel</Button>
                <Button type="submit" disabled={isSubmitting || subjectSchedules.length === 0} className="rounded-xl bg-orange-600 hover:bg-orange-700">
                  {isSubmitting ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <CheckCircle className="w-4 h-4 mr-2" />}
                  Schedule Exam
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {/* Exams List */}
      {loading ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 flex flex-col items-center justify-center">
            <Loader2 className="w-8 h-8 animate-spin text-orange-500 mb-4" />
            <p className="text-gray-500">Loading exams...</p>
          </CardContent>
        </Card>
      ) : examsByClass.length === 0 ? (
        <Card className="border-0 shadow-sm rounded-2xl bg-white">
          <CardContent className="p-8 flex flex-col items-center justify-center">
            <FileText className="w-12 h-12 text-gray-200 mb-4" />
            <p className="text-lg font-semibold text-gray-700">No exams scheduled</p>
            <p className="text-gray-500 mt-1 text-center">Click "Schedule Exam" to create your first examination.</p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {examsByClass.map(({ class: cls, exams: classExams }) => (
            <div key={cls.id} className="space-y-3">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                  <School className="w-4 h-4 text-orange-600" />
                </div>
                <h3 className="text-lg font-bold text-gray-800">{cls.name}</h3>
                <Badge className="bg-orange-50 text-orange-700">{classExams.length} exam{classExams.length !== 1 ? 's' : ''}</Badge>
              </div>

              {classExams.map(exam => {
                const isExpanded = expandedExams.has(exam.id);
                const subjects = exam.exam_subjects || [];
                const upcoming = subjects.filter(s => new Date(s.exam_date) >= new Date()).length;

                return (
                  <Card key={exam.id} className="border-0 shadow-sm rounded-2xl bg-white overflow-hidden hover:shadow-md transition-shadow">
                    <button
                      onClick={() => toggleExam(exam.id)}
                      className="w-full px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between bg-gray-50/50 border-b border-gray-100 hover:bg-gray-50 transition-colors"
                    >
                      <div className="text-left">
                        <h4 className="text-base font-bold text-gray-800">{exam.name}</h4>
                        <p className="text-xs text-gray-500">
                          {subjects.length} subject{subjects.length !== 1 ? 's' : ''} · {upcoming} upcoming
                          {exam.description && ` · ${exam.description}`}
                        </p>
                      </div>
                      <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" className="text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl shrink-0"
                          onClick={e => { e.stopPropagation(); handleDelete(exam.id); }}>
                          <Trash2 className="w-4 h-4" />
                        </Button>
                        {isExpanded ? <ChevronDown className="w-5 h-5 text-gray-400" /> : <ChevronRight className="w-5 h-5 text-gray-400" />}
                      </div>
                    </button>

                    {isExpanded && (
                      <CardContent className="p-0">
                        {/* Desktop table */}
                        <div className="hidden sm:block">
                          <table className="w-full text-sm">
                            <thead>
                              <tr className="border-b bg-gray-50/80 text-gray-500 text-xs uppercase">
                                <th className="px-4 py-2.5 text-left font-semibold">Subject</th>
                                <th className="px-4 py-2.5 text-left font-semibold">Date</th>
                                <th className="px-4 py-2.5 text-left font-semibold">Time</th>
                                <th className="px-4 py-2.5 text-center font-semibold">Total</th>
                                <th className="px-4 py-2.5 text-center font-semibold">Pass</th>
                              </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                              {subjects.map(s => {
                                const isPast = new Date(s.exam_date) < new Date();
                                return (
                                  <tr key={s.id} className={`hover:bg-gray-50/50 ${isPast ? 'opacity-60' : ''}`}>
                                    <td className="px-4 py-3">
                                      <div className="flex items-center gap-2">
                                        <BookOpen className="w-3.5 h-3.5 text-orange-400" />
                                        <span className="font-medium text-gray-800">{s.subjects?.name || 'Unknown'}</span>
                                        <Badge variant="outline" className="text-[10px]">{s.subjects?.code}</Badge>
                                      </div>
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                      <span className="flex items-center gap-1"><Calendar className="w-3 h-3" />{formatDate(s.exam_date)}</span>
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                      <span className="flex items-center gap-1"><Clock className="w-3 h-3" />{formatTime(s.start_time)} - {formatTime(s.end_time)}</span>
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                      <Badge className="bg-blue-50 text-blue-700">{s.total_marks}</Badge>
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                      <Badge className="bg-green-50 text-green-700">{s.passing_marks}</Badge>
                                    </td>
                                  </tr>
                                );
                              })}
                            </tbody>
                          </table>
                        </div>
                        {/* Mobile cards */}
                        <div className="sm:hidden divide-y divide-gray-50">
                          {subjects.map(s => (
                            <div key={s.id} className="px-4 py-3 space-y-1">
                              <div className="flex items-center gap-2">
                                <BookOpen className="w-3.5 h-3.5 text-orange-400" />
                                <span className="font-medium text-sm">{s.subjects?.name}</span>
                                <Badge variant="outline" className="text-[10px]">{s.subjects?.code}</Badge>
                              </div>
                              <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span className="flex items-center gap-1"><Calendar className="w-3 h-3" />{formatDate(s.exam_date)}</span>
                                <span className="flex items-center gap-1"><Clock className="w-3 h-3" />{formatTime(s.start_time)} - {formatTime(s.end_time)}</span>
                              </div>
                              <div className="flex gap-2 mt-1">
                                <Badge className="bg-blue-50 text-blue-700 text-[10px]">Total: {s.total_marks}</Badge>
                                <Badge className="bg-green-50 text-green-700 text-[10px]">Pass: {s.passing_marks}</Badge>
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
          ))}
        </div>
      )}

      {!loading && totalExams > 0 && (
        <div className="text-center text-xs sm:text-sm text-gray-500 py-3">
          Showing {totalExams} exam{totalExams !== 1 ? 's' : ''} across {examsByClass.length} class{examsByClass.length !== 1 ? 'es' : ''}
        </div>
      )}
    </div>
  );
}
