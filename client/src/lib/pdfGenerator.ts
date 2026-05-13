import jsPDF from 'jspdf';

interface StudentDetail {
  id: string;
  userId: string;
  firstName: string;
  lastName: string;
  email: string;
  avatarUrl?: string;
  dob?: string;
  className: string;
  classId: string | null;
  rollNumber?: string;
  enrollmentDate: string;
  attendance: {
    totalDays: number;
    presentDays: number;
    absentDays: number;
    lateDays: number;
    attendancePercentage: number;
  };
}

interface Student {
  id: string;
  user_id: string;
  enrollment_date: string;
  roll_number?: string | number;
  email?: string;
  profiles?: {
    first_name: string;
    last_name: string;
    avatar_url?: string;
  };
  classes?: {
    id: string;
    name: string;
  };
}

// Generate temporary password based on student info
function generateTemporaryPassword(firstName: string, enrollmentDate: string): string {
  const year = new Date(enrollmentDate).getFullYear();
  const initials = firstName.substring(0, 2).toUpperCase();
  const randomNum = Math.floor(Math.random() * 9000) + 1000;
  return `${initials}@${year}${randomNum}`;
}

export function generateStudentPDF(student: StudentDetail, password?: string): void {
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  });

  // Set fonts
  const primaryColor = [139, 92, 246]; // Violet
  const accentColor = [234, 179, 8]; // Amber
  const successColor = [22, 163, 74]; // Green
  const textColor = [31, 41, 55]; // Dark gray

  let yPosition = 15;

  // Header
  doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
  doc.rect(0, 0, 210, 25, 'F');

  doc.setTextColor(255, 255, 255);
  doc.setFontSize(18);
  doc.setFont('helvetica', 'bold');
  doc.text('STUDENT DETAILS REPORT', 105, 12, { align: 'center' });

  // Reset text color
  doc.setTextColor(textColor[0], textColor[1], textColor[2]);
  yPosition = 35;

  // Student Name Section
  doc.setFontSize(14);
  doc.setFont('helvetica', 'bold');
  doc.text(`${student.firstName} ${student.lastName}`, 15, yPosition);
  yPosition += 8;

  // Email and Class on same line
  doc.setFontSize(11);
  doc.setFont('helvetica', 'normal');
  doc.text(`Email: ${student.email}`, 15, yPosition);
  yPosition += 6;
  doc.text(`Class: ${student.className}`, 15, yPosition);
  yPosition += 10;

  // Academic Information Section
  doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
  doc.rect(15, yPosition - 5, 180, 6, 'F');

  doc.setTextColor(31, 41, 55);
  doc.setFontSize(12);
  doc.setFont('helvetica', 'bold');
  doc.text('Academic Information', 20, yPosition - 1);
  yPosition += 10;

  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');

  const academicData = [
    ['Roll Number:', student.rollNumber || 'N/A', 'Enrollment Date:', new Date(student.enrollmentDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })],
  ];

  academicData.forEach((row) => {
    doc.text(`${row[0]} ${row[1]}`, 15, yPosition);
    doc.text(`${row[2]} ${row[3]}`, 110, yPosition);
    yPosition += 6;
  });

  yPosition += 4;

  // Login Credentials Section
  doc.setFillColor(50, 100, 200); // Blue for credentials
  doc.rect(15, yPosition - 5, 180, 6, 'F');

  doc.setTextColor(255, 255, 255);
  doc.setFontSize(12);
  doc.setFont('helvetica', 'bold');
  doc.text('Login Credentials', 20, yPosition - 1);
  yPosition += 10;

  doc.setTextColor(31, 41, 55);
  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');

  doc.text(`Login Email: ${student.email}`, 15, yPosition);
  yPosition += 6;

  // Generate temporary password if not provided
  if (password && password.trim()) {
    const tempPassword = password;
    doc.setFont('helvetica', 'bold');
    doc.text(`Temporary Password: ${tempPassword}`, 15, yPosition);
    doc.setFont('helvetica', 'normal');
    yPosition += 6;

    doc.setFont('helvetica', 'italic');
    doc.setFontSize(8);
    doc.text('Note: Student should change password after first login.', 15, yPosition);
  } else {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.text('Password: Request student to use password reset if needed', 15, yPosition);
    yPosition += 6;
    doc.setFontSize(8);
    doc.text('Original credentials were provided at enrollment time.', 15, yPosition);
  }
  yPosition += 8;

  // Attendance Section
  if (student.attendance.totalDays > 0) {
    doc.setFillColor(successColor[0], successColor[1], successColor[2]);
    doc.rect(15, yPosition - 5, 180, 6, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('Attendance Overview', 20, yPosition - 1);
    yPosition += 10;

    doc.setTextColor(31, 41, 55);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');

    const attendanceLabels = ['Total Days', 'Present', 'Absent', 'Late', 'Attendance %'];
    const attendanceValues = [
      student.attendance.totalDays.toString(),
      student.attendance.presentDays.toString(),
      student.attendance.absentDays.toString(),
      student.attendance.lateDays.toString(),
      `${student.attendance.attendancePercentage}%`,
    ];

    // Create attendance table
    const colWidth = 35;
    const startX = 15;

    // Header row
    doc.setFont('helvetica', 'bold');
    attendanceLabels.forEach((label, index) => {
      doc.text(label, startX + index * colWidth + 5, yPosition, { maxWidth: colWidth - 10 });
    });
    yPosition += 8;

    // Data row
    doc.setFont('helvetica', 'normal');
    attendanceValues.forEach((value, index) => {
      doc.text(value, startX + index * colWidth + 5, yPosition, { align: 'center', maxWidth: colWidth - 10 });
    });
    yPosition += 12;

    // Attendance Percentage Bar (visual representation)
    const percentage = student.attendance.attendancePercentage;
    const barWidth = 150;
    const barX = 15;

    doc.setDrawColor(200, 200, 200);
    doc.rect(barX, yPosition, barWidth, 4);

    // Determine color based on percentage
    let barColor = primaryColor;
    if (percentage >= 80) {
      barColor = successColor;
    } else if (percentage >= 60) {
      barColor = accentColor;
    } else {
      barColor = [239, 68, 68]; // Red
    }

    doc.setFillColor(barColor[0], barColor[1], barColor[2]);
    doc.rect(barX, yPosition, (barWidth * percentage) / 100, 4, 'F');

    doc.setFontSize(9);
    doc.setFont('helvetica', 'bold');
    doc.text(`${percentage}%`, barX + barWidth + 5, yPosition + 3);

    yPosition += 15;
  }

  // Footer
  const pageHeight = doc.internal.pageSize.getHeight();
  doc.setFontSize(8);
  doc.setTextColor(150, 150, 150);
  doc.text(
    `Generated on ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`,
    105,
    pageHeight - 8,
    { align: 'center' }
  );

  // Download PDF
  const fileName = `Student_${student.firstName}_${student.lastName}_${new Date().toISOString().split('T')[0]}.pdf`;
  doc.save(fileName);
}

export function generateClassPDF(className: string, students: Student[]): void {
  try {
    console.log('[generateClassPDF] Starting with', students.length, 'students');

    if (!students || students.length === 0) {
      console.error('[generateClassPDF] No students provided');
      throw new Error('No students to generate PDF');
    }

    const doc = new jsPDF({
      orientation: 'landscape',
      unit: 'mm',
      format: 'a4',
    });

    const primaryColor = [139, 92, 246];
    const accentColor = [234, 179, 8];
    const textColor = [31, 41, 55];
    const lightGray = [245, 245, 245];

    let yPosition = 15;

    // Header
    doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
    doc.rect(0, 0, 297, 25, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(18);
    doc.setFont('helvetica', 'bold');
    doc.text(`CLASS ${className} - COMPLETE STUDENT ROSTER`, 148, 12, { align: 'center' });

    doc.setTextColor(textColor[0], textColor[1], textColor[2]);
    yPosition = 35;

    // Summary
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text(`Total Students: ${students.length}`, 15, yPosition);
    doc.text(`Generated: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}`, 150, yPosition);
    yPosition += 12;

    // Table Headers
    doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
    doc.rect(15, yPosition - 5, 267, 6, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'bold');

    const headers = ['S.No', 'First Name', 'Last Name', 'Enrollment Date', 'Roll Number', 'Status'];
    const colWidths = [15, 60, 60, 50, 50, 32];
    let xPos = 15;

    headers.forEach((header, idx) => {
      doc.text(header, xPos + colWidths[idx] / 2, yPosition - 1, { align: 'center' });
      xPos += colWidths[idx];
    });

    yPosition += 8;

    // Table Data
    doc.setTextColor(textColor[0], textColor[1], textColor[2]);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');

    students.forEach((student, index) => {
      // Check if new page needed
      if (yPosition > 270) {
        doc.addPage();
        yPosition = 15;

        // Re-add header on new page
        doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
        doc.rect(15, yPosition - 5, 267, 6, 'F');

        doc.setTextColor(255, 255, 255);
        doc.setFontSize(10);
        doc.setFont('helvetica', 'bold');

        xPos = 15;
        headers.forEach((header, idx) => {
          doc.text(header, xPos + colWidths[idx] / 2, yPosition - 1, { align: 'center' });
          xPos += colWidths[idx];
        });

        yPosition += 8;
        doc.setTextColor(textColor[0], textColor[1], textColor[2]);
        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
      }

      // Row background
      if (index % 2 === 0) {
        doc.setFillColor(lightGray[0], lightGray[1], lightGray[2]);
        doc.rect(15, yPosition - 4, 267, 5, 'F');
      }

      xPos = 15;
      const rowData = [
        (index + 1).toString(),
        student.profiles?.first_name || '',
        student.profiles?.last_name || '',
        new Date(student.enrollment_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }),
        (student.roll_number || 'N/A').toString(),
        'Active'
      ];

      doc.setTextColor(textColor[0], textColor[1], textColor[2]);
      rowData.forEach((data, idx) => {
        if (idx === 1 || idx === 2) {
          doc.text(data, xPos + 2, yPosition, { maxWidth: colWidths[idx] - 4 });
        } else {
          doc.text(data, xPos + colWidths[idx] / 2, yPosition, { align: 'center', maxWidth: colWidths[idx] - 2 });
        }
        xPos += colWidths[idx];
      });

      yPosition += 6;
    });

    // Add footer
    yPosition = 270;
    doc.setTextColor(100, 100, 100);
    doc.setFontSize(7);
    doc.setFont('helvetica', 'italic');
    doc.text('Note: For login credentials and password reset, students should contact the admin portal or use the password reset feature.', 15, yPosition);

    // Page numbers and footer
    const pageHeight = doc.internal.pageSize.getHeight();
    doc.setTextColor(150, 150, 150);
    doc.setFontSize(8);
    doc.setFont('helvetica', 'normal');

    for (let i = 1; i <= doc.getNumberOfPages(); i++) {
      doc.setPage(i);
      doc.text(`Page ${i} of ${doc.getNumberOfPages()}`, 148, pageHeight - 8, { align: 'center' });
    }

    const fileName = `Class_${className}_Roster_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(fileName);
    console.log('[generateClassPDF] PDF saved successfully');
  } catch (error) {
    console.error('[generateClassPDF] Error:', error);
    throw error;
  }
}

export function generateTeacherPDF(teacher: any, password: string): void {
  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  });

  // Set colors
  const primaryColor = [139, 92, 246]; // Violet
  const accentColor = [234, 179, 8]; // Amber
  const successColor = [22, 163, 74]; // Green
  const textColor = [31, 41, 55]; // Dark gray

  let yPosition = 15;

  // Header
  doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
  doc.rect(0, 0, 210, 25, 'F');

  doc.setTextColor(255, 255, 255);
  doc.setFontSize(18);
  doc.setFont('helvetica', 'bold');
  doc.text('TEACHER DETAILS REPORT', 105, 12, { align: 'center' });

  // Reset text color
  doc.setTextColor(textColor[0], textColor[1], textColor[2]);
  yPosition = 35;

  // Teacher Name Section
  doc.setFontSize(14);
  doc.setFont('helvetica', 'bold');
  const firstName = teacher.profiles?.first_name || 'N/A';
  const lastName = teacher.profiles?.last_name || 'N/A';
  doc.text(`${firstName} ${lastName}`, 15, yPosition);
  yPosition += 8;

  // Hire Date
  doc.setFontSize(11);
  doc.setFont('helvetica', 'normal');
  const hireDate = teacher.hire_date
    ? new Date(teacher.hire_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
    : 'N/A';
  doc.text(`Hire Date: ${hireDate}`, 15, yPosition);
  yPosition += 10;

  // Classes & Subjects Section
  doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
  doc.rect(15, yPosition - 5, 180, 6, 'F');

  doc.setTextColor(31, 41, 55);
  doc.setFontSize(12);
  doc.setFont('helvetica', 'bold');
  doc.text('Classes & Subjects', 20, yPosition - 1);
  yPosition += 10;

  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');

  if (teacher.teacher_subjects && teacher.teacher_subjects.length > 0) {
    teacher.teacher_subjects.forEach((assignment: any, idx: number) => {
      const className = assignment.classes?.name || 'Unknown Class';
      const subjectName = assignment.subjects?.name || 'Unknown Subject';
      doc.text(`${idx + 1}. ${className} - ${subjectName}`, 15, yPosition);
      yPosition += 6;
    });
  } else {
    doc.text('No class assignments', 15, yPosition);
    yPosition += 6;
  }

  yPosition += 4;

  // Login Credentials Section
  doc.setFillColor(50, 100, 200); // Blue for credentials
  doc.rect(15, yPosition - 5, 180, 6, 'F');

  doc.setTextColor(255, 255, 255);
  doc.setFontSize(12);
  doc.setFont('helvetica', 'bold');
  doc.text('Login Credentials', 20, yPosition - 1);
  yPosition += 10;

  doc.setTextColor(31, 41, 55);
  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');

  // Email
  doc.text(`Login Email: ${teacher.email || 'N/A'}`, 15, yPosition);
  yPosition += 6;

  // Password (if provided)
  if (password && password.trim()) {
    doc.setFont('helvetica', 'bold');
    doc.text(`Temporary Password: ${password}`, 15, yPosition);
    doc.setFont('helvetica', 'normal');
    yPosition += 6;

    doc.setFont('helvetica', 'italic');
    doc.setFontSize(8);
    doc.text('Note: Teacher should change password after first login.', 15, yPosition);
  } else {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.text('Password: Request teacher to use password reset if needed', 15, yPosition);
    yPosition += 6;
    doc.setFontSize(8);
    doc.text('Original credentials were provided at enrollment time.', 15, yPosition);
  }
  yPosition += 8;

  // Footer
  const pageHeight = doc.internal.pageSize.getHeight();
  doc.setFontSize(8);
  doc.setTextColor(150, 150, 150);
  doc.text(
    `Generated on ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`,
    105,
    pageHeight - 8,
    { align: 'center' }
  );

  // Download PDF
  const fileName = `Teacher_${firstName}_${lastName}_${new Date().toISOString().split('T')[0]}.pdf`;
  doc.save(fileName);
}

export function generateAllTeachersPDF(teachers: any[]): void {
  if (!teachers || teachers.length === 0) {
    console.error('No teachers provided');
    throw new Error('No teachers to generate PDF');
  }

  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  });

  const primaryColor = [139, 92, 246];
  const accentColor = [234, 179, 8];
  const textColor = [31, 41, 55];
  const pageHeight = doc.internal.pageSize.getHeight();
  const pageWidth = doc.internal.pageSize.getWidth();

  let yPosition = 15;
  let isFirstPage = true;

  // Helper function to add header
  const addHeader = () => {
    doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
    doc.rect(0, 0, pageWidth, 25, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('ALL TEACHERS DETAILS REPORT', pageWidth / 2, 12, { align: 'center' });

    doc.setTextColor(textColor[0], textColor[1], textColor[2]);
    return 35;
  };

  yPosition = addHeader();

  // Summary
  doc.setFontSize(11);
  doc.setFont('helvetica', 'normal');
  doc.text(`Total Teachers: ${teachers.length}`, 15, yPosition);
  doc.text(`Generated: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}`, 15, yPosition + 6);
  yPosition += 16;

  // Process each teacher
  teachers.forEach((teacher, teacherIndex) => {
    // Check if new page needed
    if (yPosition > 240 && teacherIndex > 0) {
      doc.addPage();
      yPosition = addHeader();
    }

    // Teacher section header
    doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
    doc.rect(15, yPosition - 5, 180, 6, 'F');

    doc.setTextColor(31, 41, 55);
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    const firstName = teacher.profiles?.first_name || 'N/A';
    const lastName = teacher.profiles?.last_name || 'N/A';
    doc.text(`${teacherIndex + 1}. ${firstName} ${lastName}`, 20, yPosition - 1);
    yPosition += 8;

    // Teacher details
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');

    // Hire Date
    const hireDate = teacher.hire_date
      ? new Date(teacher.hire_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
      : 'N/A';
    doc.text(`Hire Date: ${hireDate}`, 20, yPosition);
    yPosition += 5;

    // Email
    doc.text(`Email: ${teacher.email || 'N/A'}`, 20, yPosition);
    yPosition += 5;

    // Classes & Subjects
    if (teacher.teacher_subjects && teacher.teacher_subjects.length > 0) {
      doc.text('Classes & Subjects:', 20, yPosition);
      yPosition += 4;

      teacher.teacher_subjects.forEach((assignment: any) => {
        const className = assignment.classes?.name || 'Unknown';
        const subjectName = assignment.subjects?.name || 'Unknown';
        doc.text(`  • ${className} - ${subjectName}`, 25, yPosition);
        yPosition += 4;
      });
    } else {
      doc.text('Classes & Subjects: No assignments', 20, yPosition);
      yPosition += 4;
    }

    yPosition += 3;
  });

  // Footer with page numbers
  const totalPages = doc.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setTextColor(150, 150, 150);
    doc.setFontSize(8);
    doc.setFont('helvetica', 'normal');
    doc.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 8, { align: 'center' });
  }

  // Download PDF
  const fileName = `All_Teachers_${new Date().toISOString().split('T')[0]}.pdf`;
  doc.save(fileName);
}

export function generateBatchPDF(batchName: string, className: string, students: Student[]): void {
  try {
    console.log('[generateBatchPDF] Starting with batch:', batchName, 'students:', students.length);

    if (!students || students.length === 0) {
      console.error('[generateBatchPDF] No students provided');
      throw new Error('No students in batch');
    }

    const doc = new jsPDF({
      orientation: 'landscape',
      unit: 'mm',
      format: 'a4',
    });

  const primaryColor = [139, 92, 246];
  const accentColor = [234, 179, 8];
  const textColor = [31, 41, 55];
  const lightGray = [245, 245, 245];

  let yPosition = 15;
  let pageCount = 1;

  // Helper to add header on new pages
  const addHeader = () => {
    doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
    doc.rect(0, 0, 297, 25, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text(`${batchName} - ${className}`, 148, 12, { align: 'center' });

    doc.setTextColor(textColor[0], textColor[1], textColor[2]);
    return 35;
  };

  yPosition = addHeader();

  // Summary
  doc.setFontSize(11);
  doc.setFont('helvetica', 'normal');
  doc.text(`Total Students: ${students.length}`, 15, yPosition);
  doc.text(`Generated: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}`, 15, yPosition + 6);
  yPosition += 14;

  // Column headers
  doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
  doc.rect(10, yPosition - 5, 277, 6, 'F');

  doc.setTextColor(255, 255, 255);
  doc.setFontSize(8);
  doc.setFont('helvetica', 'bold');

  const colWidths = [10, 30, 30, 50, 30, 50, 35];
  const headers = ['S.No', 'First Name', 'Last Name', 'Email', 'Roll #', 'Enrollment', 'Status'];
  let xPos = 10;

  headers.forEach((header, idx) => {
    doc.text(header, xPos + colWidths[idx] / 2, yPosition - 1, { align: 'center', maxWidth: colWidths[idx] - 1 });
    xPos += colWidths[idx];
  });

  yPosition += 8;

  // Student rows
  doc.setTextColor(textColor[0], textColor[1], textColor[2]);
  doc.setFontSize(7);
  doc.setFont('helvetica', 'normal');

  students.forEach((student, index) => {
    // Check if new page needed
    if (yPosition > 270) {
      doc.addPage();
      yPosition = addHeader();
      pageCount++;

      // Re-add column headers on new page
      doc.setFillColor(accentColor[0], accentColor[1], accentColor[2]);
      doc.rect(10, yPosition - 5, 277, 6, 'F');

      doc.setTextColor(255, 255, 255);
      doc.setFontSize(8);
      doc.setFont('helvetica', 'bold');

      xPos = 10;
      headers.forEach((header, idx) => {
        doc.text(header, xPos + colWidths[idx] / 2, yPosition - 1, { align: 'center', maxWidth: colWidths[idx] - 1 });
        xPos += colWidths[idx];
      });

      yPosition += 8;
      doc.setTextColor(textColor[0], textColor[1], textColor[2]);
      doc.setFontSize(7);
      doc.setFont('helvetica', 'normal');
    }

    xPos = 10;
    const firstName = student.profiles?.first_name || '';
    const lastName = student.profiles?.last_name || '';
    const email = student.email || 'N/A';
    const enrollDate = new Date(student.enrollment_date).toLocaleDateString('en-US', { year: '2-digit', month: 'numeric', day: 'numeric' });
    const rollNumber = student.roll_number || 'N/A';

    // Generate password based on student info
    const tempPassword = generateTemporaryPassword(firstName, student.enrollment_date);

    // Row background alternation
    if (index % 2 === 0) {
      doc.setFillColor(lightGray[0], lightGray[1], lightGray[2]);
      doc.rect(10, yPosition - 4, 277, 5, 'F');
    }

    doc.setTextColor(textColor[0], textColor[1], textColor[2]);

    // S.No
    doc.text((index + 1).toString(), xPos + colWidths[0] / 2, yPosition, { align: 'center' });
    xPos += colWidths[0];

    // First Name
    doc.text(firstName, xPos + 1, yPosition, { maxWidth: colWidths[1] - 2 });
    xPos += colWidths[1];

    // Last Name
    doc.text(lastName, xPos + 1, yPosition, { maxWidth: colWidths[2] - 2 });
    xPos += colWidths[2];

    // Email
    doc.text(email, xPos + 1, yPosition, { maxWidth: colWidths[3] - 2 });
    xPos += colWidths[3];

    // Roll Number
    doc.text(rollNumber.toString(), xPos + 1, yPosition, { maxWidth: colWidths[4] - 2 });
    xPos += colWidths[4];

    // Enrollment Date
    doc.text(enrollDate, xPos + colWidths[5] / 2, yPosition, { align: 'center', maxWidth: colWidths[5] - 1 });
    xPos += colWidths[5];

    // Status
    doc.text('Active', xPos + colWidths[6] / 2, yPosition, { align: 'center' });

    yPosition += 5.5;
  });

  // Add footer with instructions
  yPosition = 270;
  doc.setTextColor(100, 100, 100);
  doc.setFontSize(7);
  doc.setFont('helvetica', 'italic');
  doc.text('Note: Students should use their enrolled email and password to login. Password must be changed after first login.', 15, yPosition);

  // Page numbers
  const pageHeight = doc.internal.pageSize.getHeight();
  doc.setTextColor(150, 150, 150);
  doc.setFontSize(8);
  doc.setFont('helvetica', 'normal');

  for (let i = 1; i <= doc.getNumberOfPages(); i++) {
    doc.setPage(i);
    doc.text(`Page ${i} of ${doc.getNumberOfPages()}`, 148, pageHeight - 8, { align: 'center' });
  }

  const fileName = `${batchName.replace(' ', '_')}_${className.replace(' ', '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(fileName);
    console.log('[generateBatchPDF] PDF saved successfully');
  } catch (error) {
    console.error('[generateBatchPDF] Error:', error);
    throw error;
  }
}
