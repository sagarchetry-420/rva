import { useState, useEffect, useRef } from "react";
import * as XLSX from "xlsx";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { useToast } from "@/hooks/use-toast";
import {
  Download,
  Upload,
  FileSpreadsheet,
  Loader2,
  CheckCircle,
  XCircle,
  AlertTriangle,
  X,
  Mail,
  MailX,
} from "lucide-react";

type ParsedStudent = {
  rowNumber: number;
  firstName: string;
  lastName: string;
  email: string;
  className: string;
  classId: string | null;
  dob: string;
  errors: string[];
};

type ParseResponse = {
  totalRows: number;
  validRows: number;
  students: ParsedStudent[];
};

type BulkResult = {
  rowNumber: number;
  email: string;
  firstName: string;
  lastName: string;
  success: boolean;
  error?: string;
  emailSent?: boolean;
  emailError?: string | null;
};

type BulkResponse = {
  totalProcessed: number;
  successCount: number;
  failCount: number;
  emailsSent: number;
  results: BulkResult[];
};

type Step = "upload" | "preview" | "enrolling" | "results";

export default function BulkStudentUpload() {
  const [step, setStep] = useState<Step>("upload");
  const [file, setFile] = useState<File | null>(null);
  const [parsing, setParsing] = useState(false);
  const [parsedStudents, setParsedStudents] = useState<ParsedStudent[]>([]);
  const [validCount, setValidCount] = useState(0);
  const [sendEmails, setSendEmails] = useState(false);
  const [enrolling, setEnrolling] = useState(false);
  const [enrollProgress, setEnrollProgress] = useState(0);
  const [results, setResults] = useState<BulkResponse | null>(null);
  const [dragOver, setDragOver] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const { toast } = useToast();

  // Download Excel template
  const handleDownloadTemplate = () => {
    const templateData = [
      {
        "First Name": "John",
        "Last Name": "Doe",
        Email: "john.doe@school.com",
        Class: "Class 5",
        "Date of Birth": "2014-03-15",
      },
      {
        "First Name": "Jane",
        "Last Name": "Smith",
        Email: "jane.smith@school.com",
        Class: "Class 5",
        "Date of Birth": "2013-11-22",
      },
    ];

    const ws = XLSX.utils.json_to_sheet(templateData);
    // Set column widths
    ws["!cols"] = [
      { wch: 15 },
      { wch: 15 },
      { wch: 28 },
      { wch: 12 },
      { wch: 15 },
    ];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Students");
    XLSX.writeFile(wb, "student_bulk_upload_template.xlsx");

    toast({
      title: "Template Downloaded",
      description:
        "Fill in the template and upload it to enroll students in bulk.",
    });
  };

  // Handle file selection
  const handleFileSelect = async (selectedFile: File) => {
    if (
      !selectedFile.name.match(/\.(xlsx|xls|csv)$/i)
    ) {
      toast({
        title: "Invalid File",
        description: "Please upload an .xlsx, .xls, or .csv file.",
        variant: "destructive",
      });
      return;
    }

    setFile(selectedFile);
    setParsing(true);

    try {
      const formData = new FormData();
      formData.append("file", selectedFile);

      const response = await api.upload<ParseResponse>(
        "/api/students/parse-bulk",
        formData
      );

      setParsedStudents(response.students);
      setValidCount(response.validRows);
      setStep("preview");

      if (response.validRows === 0) {
        toast({
          title: "No Valid Rows",
          description:
            "All rows have errors. Please fix the file and re-upload.",
          variant: "destructive",
        });
      } else if (response.validRows < response.totalRows) {
        toast({
          title: "Some Rows Have Errors",
          description: `${response.validRows} of ${response.totalRows} rows are valid. Invalid rows will be skipped.`,
        });
      }
    } catch (error: any) {
      toast({
        title: "Parse Error",
        description: error.message || "Failed to parse the file.",
        variant: "destructive",
      });
      setFile(null);
    } finally {
      setParsing(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);
    const droppedFile = e.dataTransfer.files[0];
    if (droppedFile) handleFileSelect(droppedFile);
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(true);
  };

  // Enroll valid students
  const handleEnroll = async () => {
    const validStudents = parsedStudents.filter((s) => s.errors.length === 0);
    if (validStudents.length === 0) return;

    setStep("enrolling");
    setEnrolling(true);
    setEnrollProgress(0);

    try {
      // Simulate progress — the actual call processes all at once server-side
      const progressInterval = setInterval(() => {
        setEnrollProgress((prev) => {
          if (prev >= 90) {
            clearInterval(progressInterval);
            return 90;
          }
          return prev + Math.random() * 15;
        });
      }, 800);

      const response = await api.post<BulkResponse>("/api/students/bulk", {
        students: validStudents,
        sendEmails,
      });

      clearInterval(progressInterval);
      setEnrollProgress(100);
      setResults(response);

      // Short delay then show results
      setTimeout(() => {
        setStep("results");
      }, 500);

      toast({
        title: "Bulk Enrollment Complete",
        description: `${response.successCount} of ${response.totalProcessed} students enrolled successfully.`,
      });
    } catch (error: any) {
      toast({
        title: "Enrollment Failed",
        description: error.message || "An error occurred during bulk enrollment.",
        variant: "destructive",
      });
      setStep("preview");
    } finally {
      setEnrolling(false);
    }
  };

  // Reset to start over
  const handleReset = () => {
    setStep("upload");
    setFile(null);
    setParsedStudents([]);
    setValidCount(0);
    setResults(null);
    setEnrollProgress(0);
    if (fileInputRef.current) fileInputRef.current.value = "";
  };

  // ─── UPLOAD STEP ───
  if (step === "upload") {
    return (
      <div className="space-y-6">
        {/* Download template */}
        <div className="flex items-center justify-between p-4 rounded-xl border border-dashed border-primary/30 bg-primary/5">
          <div className="flex items-center gap-3">
            <FileSpreadsheet className="w-8 h-8 text-primary" />
            <div>
              <p className="font-medium text-sm">Step 1: Download Template</p>
              <p className="text-xs text-muted-foreground">
                Get the Excel template with required columns
              </p>
            </div>
          </div>
          <Button
            variant="outline"
            size="sm"
            onClick={handleDownloadTemplate}
            className="gap-2"
          >
            <Download className="w-4 h-4" /> Download .xlsx
          </Button>
        </div>

        {/* Upload zone */}
        <div
          className={`relative flex flex-col items-center justify-center p-10 rounded-xl border-2 border-dashed transition-all cursor-pointer ${
            dragOver
              ? "border-primary bg-primary/10 scale-[1.01]"
              : "border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/50"
          }`}
          onDrop={handleDrop}
          onDragOver={handleDragOver}
          onDragLeave={() => setDragOver(false)}
          onClick={() => fileInputRef.current?.click()}
        >
          <input
            ref={fileInputRef}
            type="file"
            accept=".xlsx,.xls,.csv"
            className="hidden"
            onChange={(e) => {
              const f = e.target.files?.[0];
              if (f) handleFileSelect(f);
            }}
          />

          {parsing ? (
            <div className="flex flex-col items-center gap-3">
              <Loader2 className="w-10 h-10 text-primary animate-spin" />
              <p className="text-sm font-medium">Parsing file...</p>
            </div>
          ) : (
            <>
              <Upload className="w-10 h-10 text-muted-foreground mb-3" />
              <p className="font-medium text-sm">
                Step 2: Upload filled template
              </p>
              <p className="text-xs text-muted-foreground mt-1">
                Drag & drop or click to browse • .xlsx, .xls, .csv
              </p>
            </>
          )}
        </div>

        {/* Instructions */}
        <div className="text-xs text-muted-foreground space-y-1 px-1">
          <p>
            <strong>Required columns:</strong> First Name, Last Name, Email
          </p>
          <p>
            <strong>Optional columns:</strong> Class (must match existing class
            names), Date of Birth
          </p>
          <p>
            <strong>Limit:</strong> Maximum 200 students per upload
          </p>
          <p>
            <strong>Passwords:</strong> Auto-generated securely by the server
            and sent directly to students via email
          </p>
        </div>
      </div>
    );
  }

  // ─── PREVIEW STEP ───
  if (step === "preview") {
    const invalidStudents = parsedStudents.filter(
      (s) => s.errors.length > 0
    );

    return (
      <div className="space-y-4">
        {/* Summary bar */}
        <div className="flex items-center justify-between px-3 py-2 rounded-lg bg-muted/60 text-sm">
          <div className="flex items-center gap-4">
            <span className="flex items-center gap-1.5">
              <FileSpreadsheet className="w-4 h-4 text-muted-foreground" />
              {file?.name}
            </span>
            <span className="text-green-600 font-medium">
              {validCount} valid
            </span>
            {invalidStudents.length > 0 && (
              <span className="text-red-500 font-medium">
                {invalidStudents.length} errors
              </span>
            )}
          </div>
          <Button variant="ghost" size="sm" onClick={handleReset}>
            <X className="w-4 h-4 mr-1" /> Clear
          </Button>
        </div>

        {/* Preview table */}
        <div className="border rounded-lg overflow-hidden">
          <div className="max-h-[350px] overflow-auto">
            <table className="w-full text-sm">
              <thead className="bg-muted/80 sticky top-0">
                <tr>
                  <th className="text-left px-3 py-2 font-medium">Row</th>
                  <th className="text-left px-3 py-2 font-medium">
                    First Name
                  </th>
                  <th className="text-left px-3 py-2 font-medium">
                    Last Name
                  </th>
                  <th className="text-left px-3 py-2 font-medium">Email</th>
                  <th className="text-left px-3 py-2 font-medium">Class</th>
                  <th className="text-left px-3 py-2 font-medium">Status</th>
                </tr>
              </thead>
              <tbody>
                {parsedStudents.map((student, idx) => (
                  <tr
                    key={idx}
                    className={`border-t ${
                      student.errors.length > 0
                        ? "bg-red-50 dark:bg-red-950/20"
                        : ""
                    }`}
                  >
                    <td className="px-3 py-2 text-muted-foreground">
                      {student.rowNumber}
                    </td>
                    <td className="px-3 py-2">{student.firstName || "—"}</td>
                    <td className="px-3 py-2">{student.lastName || "—"}</td>
                    <td className="px-3 py-2 text-xs">{student.email || "—"}</td>
                    <td className="px-3 py-2">{student.className || "—"}</td>
                    <td className="px-3 py-2">
                      {student.errors.length > 0 ? (
                        <span
                          className="text-red-500 text-xs"
                          title={student.errors.join(", ")}
                        >
                          <XCircle className="w-3.5 h-3.5 inline mr-1" />
                          {student.errors[0]}
                        </span>
                      ) : (
                        <span className="text-green-600 text-xs">
                          <CheckCircle className="w-3.5 h-3.5 inline mr-1" />
                          Valid
                        </span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Email toggle */}
        <div className="flex items-center justify-between p-3 rounded-lg border bg-muted/30">
          <div className="flex items-center gap-2">
            <Mail className="w-4 h-4 text-muted-foreground" />
            <div>
              <p className="text-sm font-medium">Send credential emails</p>
              <p className="text-xs text-muted-foreground">
                Sends login credentials to each student's email. Takes ~1s per
                student to avoid rate limits.
              </p>
            </div>
          </div>
          <Switch checked={sendEmails} onCheckedChange={setSendEmails} />
        </div>

        {/* Action buttons */}
        <div className="flex gap-3">
          <Button variant="outline" onClick={handleReset} className="flex-1">
            Cancel
          </Button>
          <Button
            onClick={handleEnroll}
            className="flex-1"
            disabled={validCount === 0}
          >
            Enroll {validCount} Student{validCount !== 1 ? "s" : ""}
          </Button>
        </div>
      </div>
    );
  }

  // ─── ENROLLING STEP ───
  if (step === "enrolling") {
    return (
      <div className="flex flex-col items-center justify-center py-12 space-y-6">
        <Loader2 className="w-12 h-12 text-primary animate-spin" />
        <div className="text-center">
          <h3 className="text-lg font-semibold">Enrolling Students...</h3>
          <p className="text-sm text-muted-foreground mt-1">
            Processing {validCount} students. This may take a few minutes
            {sendEmails ? " (sending emails)" : ""}.
          </p>
        </div>
        {/* Progress bar */}
        <div className="w-full max-w-xs">
          <div className="w-full bg-muted rounded-full h-2.5 overflow-hidden">
            <div
              className="bg-primary h-2.5 rounded-full transition-all duration-500"
              style={{ width: `${Math.min(enrollProgress, 100)}%` }}
            />
          </div>
          <p className="text-xs text-center text-muted-foreground mt-2">
            {Math.round(enrollProgress)}%
          </p>
        </div>
      </div>
    );
  }

  // ─── RESULTS STEP ───
  if (step === "results" && results) {
    return (
      <div className="space-y-4">
        {/* Summary cards */}
        <div className="grid grid-cols-3 gap-3">
          <Card className="border-green-200 bg-green-50 dark:bg-green-950/30">
            <CardContent className="p-4 text-center">
              <CheckCircle className="w-6 h-6 text-green-600 mx-auto mb-1" />
              <p className="text-2xl font-bold text-green-700">
                {results.successCount}
              </p>
              <p className="text-xs text-green-600">Enrolled</p>
            </CardContent>
          </Card>
          {results.failCount > 0 && (
            <Card className="border-red-200 bg-red-50 dark:bg-red-950/30">
              <CardContent className="p-4 text-center">
                <XCircle className="w-6 h-6 text-red-500 mx-auto mb-1" />
                <p className="text-2xl font-bold text-red-600">
                  {results.failCount}
                </p>
                <p className="text-xs text-red-500">Failed</p>
              </CardContent>
            </Card>
          )}
          <Card className="border-blue-200 bg-blue-50 dark:bg-blue-950/30">
            <CardContent className="p-4 text-center">
              {results.emailsSent > 0 ? (
                <Mail className="w-6 h-6 text-blue-600 mx-auto mb-1" />
              ) : (
                <MailX className="w-6 h-6 text-blue-400 mx-auto mb-1" />
              )}
              <p className="text-2xl font-bold text-blue-700">
                {results.emailsSent}
              </p>
              <p className="text-xs text-blue-600">Emails Sent</p>
            </CardContent>
          </Card>
        </div>

        {/* Results table */}
        <div className="border rounded-lg overflow-hidden">
          <div className="max-h-[280px] overflow-auto">
            <table className="w-full text-sm">
              <thead className="bg-muted/80 sticky top-0">
                <tr>
                  <th className="text-left px-3 py-2 font-medium">Name</th>
                  <th className="text-left px-3 py-2 font-medium">Email</th>
                  <th className="text-left px-3 py-2 font-medium">Status</th>
                  <th className="text-left px-3 py-2 font-medium">Email</th>
                </tr>
              </thead>
              <tbody>
                {results.results.map((r, idx) => (
                  <tr key={idx} className="border-t">
                    <td className="px-3 py-2">
                      {r.firstName} {r.lastName}
                    </td>
                    <td className="px-3 py-2 text-xs">{r.email}</td>
                    <td className="px-3 py-2">
                      {r.success ? (
                        <span className="inline-flex items-center gap-1 text-green-600 text-xs">
                          <CheckCircle className="w-3.5 h-3.5" /> Enrolled
                        </span>
                      ) : (
                        <span
                          className="inline-flex items-center gap-1 text-red-500 text-xs"
                          title={r.error}
                        >
                          <XCircle className="w-3.5 h-3.5" />{" "}
                          {r.error?.substring(0, 40)}
                        </span>
                      )}
                    </td>
                    <td className="px-3 py-2">
                      {r.emailSent ? (
                        <span className="text-green-600 text-xs">
                          <Mail className="w-3.5 h-3.5 inline" /> Sent
                        </span>
                      ) : r.success && sendEmails ? (
                        <span
                          className="text-orange-500 text-xs"
                          title={r.emailError || ""}
                        >
                          <AlertTriangle className="w-3.5 h-3.5 inline" /> Failed
                        </span>
                      ) : (
                        <span className="text-muted-foreground text-xs">—</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Actions */}
        <div className="flex gap-3">
          <Button variant="outline" onClick={handleReset} className="flex-1">
            Upload More
          </Button>
        </div>
      </div>
    );
  }

  return null;
}
