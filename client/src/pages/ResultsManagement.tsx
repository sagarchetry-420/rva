import { useState, useEffect } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { FileText, Loader2, Printer, Download, Trophy, XCircle, CheckCircle } from "lucide-react";
import { toast } from "sonner";

interface Exam {
  id: string;
  name: string;
  is_final: boolean;
  class_id: string;
  classes: { id: string; name: string } | null;
  exam_subjects: any[];
}

interface SubjectResult {
  examSubjectId: string;
  subjectName: string;
  subjectCode: string;
  totalMarks: number;
  passingMarks: number;
  marksObtained: number | null;
  isAbsent: boolean;
  passed: boolean;
  hasResult: boolean;
}

interface StudentResult {
  studentId: string;
  rollNumber: string;
  firstName: string;
  lastName: string;
  subjects: SubjectResult[];
  totalMarks: number;
  totalObtained: number;
  percentage: number;
  overallPassed: boolean;
  allResultsEntered: boolean;
}

interface ExamResultData {
  exam: {
    id: string;
    name: string;
    isFinal: boolean;
    className: string;
    classId: string;
  };
  subjects: { id: string; name: string; code: string; totalMarks: number; passingMarks: number }[];
  students: StudentResult[];
}

export default function ResultsManagement() {
  const [exams, setExams] = useState<Exam[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedExamId, setSelectedExamId] = useState("");
  const [resultData, setResultData] = useState<ExamResultData | null>(null);
  const [resultsLoading, setResultsLoading] = useState(false);

  const [selectedYear, setSelectedYear] = useState("");
  const [selectedClass, setSelectedClass] = useState("");

  useEffect(() => { fetchExams(); }, []);

  const fetchExams = async () => {
    setLoading(true);
    try {
      const data = await api.get<Exam[]>("/api/exams");
      setExams(data || []);
    } catch { toast.error("Failed to load exams"); }
    finally { setLoading(false); }
  };

  const fetchResults = async (examId: string) => {
    setResultsLoading(true);
    try {
      const data = await api.get<ExamResultData>(`/api/results/exam/${examId}`);
      setResultData(data);
    } catch (err: any) {
      toast.error("Failed to load results: " + err.message);
    } finally {
      setResultsLoading(false);
    }
  };

  const handleExamSelect = (examId: string) => {
    setSelectedExamId(examId);
    fetchResults(examId);
  };

  // Derive available years from exam_subjects dates
  const availableYears = [...new Set(exams.flatMap(e =>
    (e.exam_subjects || []).map((es: any) => {
      const d = es.exam_date ? new Date(es.exam_date) : null;
      return d ? String(d.getFullYear()) : null;
    }).filter(Boolean)
  ))].sort((a, b) => Number(b) - Number(a));

  // If no year selected but years exist, don't auto-select
  const filteredByYear = selectedYear
    ? exams.filter(e => (e.exam_subjects || []).some((es: any) => es.exam_date && String(new Date(es.exam_date).getFullYear()) === selectedYear))
    : exams;

  // Derive available classes from year-filtered exams
  const availableClasses = [...new Map(
    filteredByYear
      .filter(e => e.classes)
      .map(e => [e.class_id, e.classes!.name])
  ).entries()].sort((a, b) => a[1].localeCompare(b[1]));

  // Filter exams by selected class
  const filteredExams = selectedClass
    ? filteredByYear.filter(e => e.class_id === selectedClass)
    : filteredByYear;

  // Reset downstream when upstream changes
  const handleYearChange = (year: string) => {
    setSelectedYear(year);
    setSelectedClass("");
    setSelectedExamId("");
    setResultData(null);
  };

  const handleClassChange = (classId: string) => {
    setSelectedClass(classId);
    setSelectedExamId("");
    setResultData(null);
  };

  const handleDownload = () => {
    if (!resultData) return;
    const { exam, subjects, students } = resultData;
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const html = `
      <html>
        <head>
          <title>${exam.name} - ${exam.className} Results</title>
          <style>
            body { font-family: system-ui, sans-serif; padding: 1.5rem; color: #333; max-width: 1200px; margin: 0 auto; }
            h1 { text-align: center; color: #111; margin-bottom: 0.5rem; }
            .subtitle { text-align: center; color: #666; margin-bottom: 2rem; font-size: 0.95rem; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.85rem; }
            th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: center; }
            th { background-color: #f8f9fa; font-weight: 600; color: #444; }
            td.name { text-align: left; }
            .pass { color: #16a34a; font-weight: 600; }
            .fail { color: #dc2626; font-weight: 600; }
            .absent { color: #9333ea; font-style: italic; }
            .summary { margin-top: 2rem; display: flex; gap: 2rem; justify-content: center; }
            .summary-item { text-align: center; padding: 1rem; border: 1px solid #eee; border-radius: 8px; min-width: 120px; }
            .summary-item .number { font-size: 1.5rem; font-weight: 700; }
            .footer { margin-top: 2rem; text-align: center; font-size: 0.8rem; color: #999; }
          </style>
        </head>
        <body>
          <h1>${exam.name}</h1>
          <div class="subtitle">${exam.className}${exam.isFinal ? ' · FINAL EXAM' : ''}</div>
          <table>
            <thead>
              <tr>
                <th>Roll No</th>
                <th>Student Name</th>
                ${subjects.map(s => `<th>${s.name}<br/><small>(${s.totalMarks})</small></th>`).join('')}
                <th>Total</th>
                <th>%</th>
                <th>Result</th>
              </tr>
            </thead>
            <tbody>
              ${students.map(st => `
                <tr>
                  <td>${st.rollNumber || '-'}</td>
                  <td class="name">${st.firstName} ${st.lastName}</td>
                  ${st.subjects.map(sub => {
                    if (!sub.hasResult) return '<td>-</td>';
                    if (sub.isAbsent) return '<td class="absent">AB</td>';
                    return `<td class="${sub.passed ? 'pass' : 'fail'}">${sub.marksObtained}</td>`;
                  }).join('')}
                  <td><strong>${st.totalObtained}/${st.totalMarks}</strong></td>
                  <td>${st.percentage}%</td>
                  <td class="${st.allResultsEntered ? (st.overallPassed ? 'pass' : 'fail') : ''}">${st.allResultsEntered ? (st.overallPassed ? 'PASS' : 'FAIL') : 'Pending'}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
          <div class="summary">
            <div class="summary-item"><div class="number">${students.length}</div><div>Total Students</div></div>
            <div class="summary-item"><div class="number pass">${students.filter(s => s.allResultsEntered && s.overallPassed).length}</div><div>Passed</div></div>
            <div class="summary-item"><div class="number fail">${students.filter(s => s.allResultsEntered && !s.overallPassed).length}</div><div>Failed</div></div>
            <div class="summary-item"><div class="number">${students.filter(s => !s.allResultsEntered).length}</div><div>Pending</div></div>
          </div>
          <div class="footer">Generated by RVA Examination System · ${new Date().toLocaleDateString()}</div>
          <script>window.onload = () => { window.print(); }</script>
        </body>
      </html>
    `;
    printWindow.document.write(html);
    printWindow.document.close();
  };

  // Group exams by class for the selector
  const examsByClass = exams.reduce((acc, exam) => {
    const className = exam.classes?.name || 'Other';
    if (!acc[className]) acc[className] = [];
    acc[className].push(exam);
    return acc;
  }, {} as Record<string, Exam[]>);

  const passedStudents = resultData?.students.filter(s => s.allResultsEntered && s.overallPassed) || [];
  const failedStudents = resultData?.students.filter(s => s.allResultsEntered && !s.overallPassed) || [];
  const pendingStudents = resultData?.students.filter(s => !s.allResultsEntered) || [];

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <div className="p-2.5 bg-purple-100 rounded-xl shadow-sm">
              <Trophy className="w-6 h-6 text-purple-600" />
            </div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
              Results Management
            </h1>
          </div>
          <p className="text-gray-500 text-sm sm:text-base ml-[3.25rem]">
            View exam results, download mark sheets, and manage student promotions.
          </p>
        </div>
      </div>

      {/* Exam Selector */}
      <Card className="border-0 shadow-sm rounded-2xl bg-white">
        <CardContent className="p-4 sm:p-6">
          <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-end">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
              <div className="space-y-2">
                <label className="text-sm font-semibold text-gray-700">Year</label>
                <Select value={selectedYear || "all"} onValueChange={(val) => handleYearChange(val === "all" ? "" : val)}>
                  <SelectTrigger className="rounded-xl">
                    <SelectValue placeholder="All Years" />
                  </SelectTrigger>
                  <SelectContent className="max-h-60 overflow-y-auto">
                    <SelectItem value="all">All Years</SelectItem>
                    {availableYears.map(year => (
                      <SelectItem key={year} value={year}>{year}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-semibold text-gray-700">Class</label>
                <Select value={selectedClass || "all"} onValueChange={(val) => handleClassChange(val === "all" ? "" : val)}>
                  <SelectTrigger className="rounded-xl">
                    <SelectValue placeholder="All Classes" />
                  </SelectTrigger>
                  <SelectContent className="max-h-60 overflow-y-auto">
                    <SelectItem value="all">All Classes</SelectItem>
                    {availableClasses.map(([id, name]) => (
                      <SelectItem key={id} value={id}>{name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-semibold text-gray-700">Exam</label>
                <Select value={selectedExamId || "none"} onValueChange={(val) => {
                  if (val !== "none") handleExamSelect(val);
                }}>
                  <SelectTrigger className="rounded-xl">
                    <SelectValue placeholder={loading ? "Loading..." : "Select Exam"} />
                  </SelectTrigger>
                  <SelectContent className="max-h-60 overflow-y-auto">
                    <SelectItem value="none" disabled>Select Exam</SelectItem>
                    {filteredExams.map(exam => (
                      <SelectItem key={exam.id} value={exam.id}>
                        {exam.name} {exam.is_final ? '(FINAL)' : ''} { !selectedClass ? `— ${exam.classes?.name}` : '' }
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            {resultData && (
              <Button onClick={handleDownload} className="gap-2 bg-purple-600 hover:bg-purple-700 rounded-xl shrink-0">
                <Printer className="w-4 h-4" />
                Download Results
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Results */}
      {resultsLoading ? (
        <div className="flex justify-center py-16">
          <Loader2 className="animate-spin w-10 h-10 text-purple-500" />
        </div>
      ) : resultData ? (
        <>
          {/* Stats */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <Card className="bg-blue-50/80 border-0 shadow-sm">
              <CardContent className="p-3 sm:p-4 text-center">
                <p className="text-xs font-semibold text-gray-600 mb-1">Total Students</p>
                <p className="text-2xl font-extrabold text-blue-600">{resultData.students.length}</p>
              </CardContent>
            </Card>
            <Card className="bg-green-50/80 border-0 shadow-sm">
              <CardContent className="p-3 sm:p-4 text-center">
                <p className="text-xs font-semibold text-gray-600 mb-1">Passed</p>
                <p className="text-2xl font-extrabold text-green-600">{passedStudents.length}</p>
              </CardContent>
            </Card>
            <Card className="bg-red-50/80 border-0 shadow-sm">
              <CardContent className="p-3 sm:p-4 text-center">
                <p className="text-xs font-semibold text-gray-600 mb-1">Failed</p>
                <p className="text-2xl font-extrabold text-red-600">{failedStudents.length}</p>
              </CardContent>
            </Card>
            <Card className="bg-amber-50/80 border-0 shadow-sm">
              <CardContent className="p-3 sm:p-4 text-center">
                <p className="text-xs font-semibold text-gray-600 mb-1">Pending</p>
                <p className="text-2xl font-extrabold text-amber-600">{pendingStudents.length}</p>
              </CardContent>
            </Card>
          </div>

          {/* Results Table */}
          <Card className="border-0 shadow-sm rounded-2xl bg-white">
            <CardHeader className="border-b bg-gray-50/50 flex flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <FileText className="w-5 h-5 text-purple-500" />
                {resultData.exam.name} — {resultData.exam.className}
                {resultData.exam.isFinal && <Badge className="bg-red-100 text-red-700 text-[10px]">FINAL</Badge>}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-gray-50/80">
                      <TableHead className="sticky left-0 bg-gray-50 z-10">Roll</TableHead>
                      <TableHead className="sticky left-12 bg-gray-50 z-10 min-w-[140px]">Student Name</TableHead>
                      {resultData.subjects.map(s => (
                        <TableHead key={s.id} className="text-center min-w-[80px]">
                          <div className="text-xs">{s.name}</div>
                          <div className="text-[10px] text-muted-foreground">({s.totalMarks})</div>
                        </TableHead>
                      ))}
                      <TableHead className="text-center">Total</TableHead>
                      <TableHead className="text-center">%</TableHead>
                      <TableHead className="text-center">Result</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {resultData.students.map(student => (
                      <TableRow key={student.studentId} className={!student.allResultsEntered ? 'bg-amber-50/30' : student.overallPassed ? '' : 'bg-red-50/30'}>
                        <TableCell className="sticky left-0 bg-white z-10 font-medium">{student.rollNumber || '-'}</TableCell>
                        <TableCell className="sticky left-12 bg-white z-10 font-medium">{student.firstName} {student.lastName}</TableCell>
                        {student.subjects.map(sub => (
                          <TableCell key={sub.examSubjectId} className="text-center">
                            {!sub.hasResult ? (
                              <span className="text-gray-400">—</span>
                            ) : sub.isAbsent ? (
                              <span className="text-purple-600 font-medium text-xs">AB</span>
                            ) : (
                              <span className={sub.passed ? 'text-green-700 font-medium' : 'text-red-600 font-medium'}>
                                {sub.marksObtained}
                              </span>
                            )}
                          </TableCell>
                        ))}
                        <TableCell className="text-center font-semibold">{student.totalObtained}/{student.totalMarks}</TableCell>
                        <TableCell className="text-center font-medium">{student.percentage}%</TableCell>
                        <TableCell className="text-center">
                          {!student.allResultsEntered ? (
                            <Badge variant="outline" className="text-amber-600 border-amber-300 text-[10px]">Pending</Badge>
                          ) : student.overallPassed ? (
                            <Badge className="bg-green-100 text-green-700 text-[10px]">
                              <CheckCircle className="w-3 h-3 mr-1" /> PASS
                            </Badge>
                          ) : (
                            <Badge className="bg-red-100 text-red-700 text-[10px]">
                              <XCircle className="w-3 h-3 mr-1" /> FAIL
                            </Badge>
                          )}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </>
      ) : selectedExamId ? null : (
        <div className="text-center py-16 border-2 border-dashed rounded-2xl bg-muted/5">
          <Trophy className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
          <p className="text-muted-foreground font-medium">Select an exam to view results</p>
          <p className="text-sm text-muted-foreground mt-1">Choose an exam from the dropdown above.</p>
        </div>
      )}
    </div>
  );
}
