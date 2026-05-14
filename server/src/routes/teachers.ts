import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';
import { sendTeacherEnrollmentEmail, isEmailConfigured, sendTeacherNoticeEmail } from '../lib/resend.js';

export const teachersRouter = Router();

// GET /api/teachers (admin only)
teachersRouter.get('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();

    // Get teachers with their profiles and class/subject assignments
    const { data, error } = await supabase
      .from('teachers')
      .select(`
        id,
        user_id,
        hire_date,
        status,
        notice_start_date,
        last_working_date,
        resignation_document_url,
        profiles (
          first_name,
          last_name
        ),
        teacher_subjects (
          classes (
            id,
            name
          ),
          subjects (
            id,
            name
          )
        )
      `)
      .order('hire_date', { ascending: false });

    console.log('[GET /api/teachers] Teachers with assignments:', data);

    if (error) {
      console.error('[GET /api/teachers] Error fetching teachers:', error);
      return res.status(400).json({ error: error.message });
    }

    // Fetch emails for all teachers
    const teachersWithEmail = await Promise.all(
      (data || []).map(async (teacher: any) => {
        try {
          const { data: authUser } = await supabase.auth.admin.getUserById(teacher.user_id);
          return {
            ...teacher,
            email: authUser?.user?.email || 'N/A'
          };
        } catch (err) {
          console.error(`Failed to fetch email for teacher ${teacher.id}:`, err);
          return {
            ...teacher,
            email: 'N/A'
          };
        }
      })
    );

    res.json(teachersWithEmail || []);
  } catch (err: any) {
    console.error('[GET /api/teachers] Unexpected error:', err);
    res.status(500).json({ error: 'Failed to fetch teachers: ' + err.message });
  }
});

// POST /api/teachers — admin creates a teacher account
teachersRouter.post('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const admin = createAdminClient();
    const { email, password, firstName, lastName, hireDate, assignments } = req.body;
    const normalizedEmail = typeof email === 'string' ? email.trim() : '';

    console.log('[Teachers POST] Request body:', JSON.stringify(req.body, null, 2));
    console.log('[Teachers POST] Creating user:', { email: normalizedEmail, firstName, lastName, hireDate });
    console.log('[Teachers POST] Assignments:', assignments);

    // Validate required fields
    if (!normalizedEmail || !password || !firstName || !lastName) {
      return res.status(400).json({
        error: 'Missing required fields: email, password, firstName, lastName'
      });
    }

    if (!hireDate) {
      return res.status(400).json({
        error: 'Missing required field: hireDate'
      });
    }

    if (!Array.isArray(assignments) || assignments.length === 0) {
      return res.status(400).json({
        error: 'At least one assignment is required'
      });
    }

    // Create auth user (simpler metadata without teacher-specific data)
    const { data: userData, error: authError } = await admin.auth.admin.createUser({
      email: normalizedEmail,
      password,
      email_confirm: true,
      user_metadata: {
        first_name: firstName,
        last_name: lastName,
        role: 'teacher'
      }
    });

    if (authError) {
      console.error('[Teachers POST] Auth error details:', {
        message: authError.message,
        status: authError.status,
        code: (authError as any).code,
        fullError: JSON.stringify(authError, null, 2)
      });
      return res.status(400).json({ error: 'Failed to create auth user: ' + authError.message });
    }

    console.log('[Teachers POST] Auth user created:', userData.user.id);

    // Wait for DB trigger to create profile and user_role (retry-based, not setTimeout)
    const maxRetries = 5;
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      const { data: profileCheck } = await admin
        .from('profiles')
        .select('user_id')
        .eq('user_id', userData.user.id)
        .maybeSingle();
      if (profileCheck) break;
      if (attempt === maxRetries) {
        console.warn('[Teachers POST] Profile trigger did not fire after retries — proceeding anyway');
      }
      await new Promise(resolve => setTimeout(resolve, 200));
    }

    // Now manually create the teacher record
    const { data: teacherData, error: teacherError } = await admin
      .from('teachers')
      .insert([{
        user_id: userData.user.id,
        hire_date: hireDate
      }])
      .select()
      .single();

    if (teacherError) {
      console.error('[Teachers POST] Teacher creation error:', teacherError);
      // Clean up auth user if teacher creation fails
      await admin.auth.admin.deleteUser(userData.user.id);
      return res.status(400).json({ error: 'Failed to create teacher record: ' + teacherError.message });
    }

    console.log('[Teachers POST] Teacher created:', teacherData.id);

    // Insert teacher subject assignments
    if (assignments && Array.isArray(assignments) && assignments.length > 0) {
      const assignmentData = assignments.map((a: any) => ({
        teacher_id: teacherData.id,
        class_id: a.classId,
        subject_id: a.subjectId
      }));

      console.log('[Teachers POST] Adding assignments:', JSON.stringify(assignmentData, null, 2));

      const { error: assignmentError } = await admin
        .from('teacher_subjects')
        .insert(assignmentData);

      if (assignmentError) {
        console.error('[Teachers POST] Assignment error:', assignmentError);
        return res.status(400).json({ error: 'Failed to create assignments: ' + assignmentError.message });
      }

      console.log('[Teachers POST] Assignments created successfully');
    }

    const emailReady = isEmailConfigured();
    let emailSent = false;
    let emailError: string | null = emailReady ? null : 'Email service is not configured on the server.';

    if (emailReady) {
      try {
        const result = await sendTeacherEnrollmentEmail({
          to: normalizedEmail,
          firstName,
          lastName,
          loginEmail: normalizedEmail,
          temporaryPassword: password,
        });
        
        emailSent = result.sent;
        if (!result.sent) {
          emailError = result.error || 'Failed to send email';
          console.error('[Teachers POST] Enrollment email failed:', result.error);
        } else {
          console.log('[Teachers POST] Enrollment email sent:', result.messageId);
        }
      } catch (err: any) {
        console.error('[Teachers POST] Enrollment email error:', err);
        emailError = err.message || 'Unknown email error';
      }
    }

    // Respond AFTER email finishes, because Vercel/serverless will kill the process
    // if we respond first.
    res.json({
      user: userData.user,
      emailSent,
      emailError,
    });
  } catch (err: any) {
    console.error('[Teachers POST] Unexpected error:', err);
    res.status(500).json({ error: 'Failed to create teacher: ' + err.message });
  }
});

// GET /api/teachers/:id (admin only) - Get single teacher details
teachersRouter.get('/:id', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();

    // Get teacher with profiles and assignments
    const { data, error } = await supabase
      .from('teachers')
      .select(`
        id,
        user_id,
        hire_date,
        status,
        notice_start_date,
        last_working_date,
        resignation_document_url,
        profiles (
          first_name,
          last_name
        ),
        teacher_subjects (
          classes (
            id,
            name
          ),
          subjects (
            id,
            name
          )
        )
      `)
      .eq('id', req.params.id)
      .single();

    if (error) {
      console.error('[GET /api/teachers/:id] Error:', error);
      return res.status(400).json({ error: error.message });
    }

    if (!data) {
      return res.status(404).json({ error: 'Teacher not found' });
    }

    // Get teacher's email from auth.users
    const { data: authUser, error: authError } = await supabase.auth.admin.getUserById(data.user_id);

    const response = {
      ...data,
      email: authUser?.user?.email || 'N/A'
    };

    res.json(response);
  } catch (err: any) {
    console.error('[GET /api/teachers/:id] Unexpected error:', err);
    res.status(500).json({ error: 'Failed to fetch teacher details: ' + err.message });
  }
});

// DELETE /api/teachers/:id (admin only)
teachersRouter.delete('/:id', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { error } = await supabase.from('teachers').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete teacher' });
  }
});
// PATCH /api/teachers/:id/status (admin only)
teachersRouter.patch('/:id/status', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { status, noticeStartDate, lastWorkingDate, resignationDocumentUrl } = req.body;

    if (!['active', 'on_notice', 'left'].includes(status)) {
      return res.status(400).json({ error: 'Invalid status' });
    }

    if (status === 'on_notice') {
      if (!noticeStartDate || !lastWorkingDate) {
        return res.status(400).json({ error: 'noticeStartDate and lastWorkingDate are required for on_notice status' });
      }
    }

    const updateData: any = { status };
    
    if (status === 'on_notice') {
      updateData.notice_start_date = noticeStartDate;
      updateData.last_working_date = lastWorkingDate;
      if (resignationDocumentUrl !== undefined) {
        updateData.resignation_document_url = resignationDocumentUrl;
      }
    } else if (status === 'active') {
      updateData.notice_start_date = null;
      updateData.last_working_date = null;
      updateData.resignation_document_url = null;
    } else if (status === 'left') {
      // Keep dates and docs if they exist
    }

    // Update teacher record
    const { error: updateError } = await supabase
      .from('teachers')
      .update(updateData)
      .eq('id', req.params.id);

    if (updateError) {
      console.error('[PATCH /api/teachers/:id/status] Error:', updateError);
      return res.status(400).json({ error: updateError.message });
    }

    // Send email notification to the teacher if status changed to on_notice
    if (status === 'on_notice' && isEmailConfigured()) {
      const { data: teacherRecord } = await supabase
        .from('teachers')
        .select('user_id, profiles(first_name, last_name)')
        .eq('id', req.params.id)
        .single();
        
      if (teacherRecord) {
        const { data: authUser } = await supabase.auth.admin.getUserById(teacherRecord.user_id);
        if (authUser?.user?.email) {
          const p: any = teacherRecord.profiles || {};
          await sendTeacherNoticeEmail({
            to: authUser.user.email,
            firstName: p.first_name || 'Teacher',
            lastName: p.last_name || '',
            noticeStartDate,
            lastWorkingDate
          }).catch(err => console.error("Admin notice email failed to send:", err));
        }
      }
    }

    // If teacher is marked as left, remove all their class assignments
    if (status === 'left') {
      const { error: deleteError } = await supabase
        .from('teacher_subjects')
        .delete()
        .eq('teacher_id', req.params.id);
        
      if (deleteError) {
        console.error('[PATCH /api/teachers/:id/status] Error deleting assignments:', deleteError);
        // Don't fail the request if assignments fail to delete, but log it
      }
    }

    res.json({ success: true, status });
  } catch (err: any) {
    console.error('[PATCH /api/teachers/:id/status] Unexpected error:', err);
    res.status(500).json({ error: 'Failed to update teacher status: ' + err.message });
  }
});

// POST /api/teachers/resign (teacher only) - Submit resignation
teachersRouter.post('/resign', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { noticeStartDate, lastWorkingDate, resignationDocumentUrl } = req.body;

    if (!noticeStartDate || !lastWorkingDate) {
      return res.status(400).json({ error: 'noticeStartDate and lastWorkingDate are required' });
    }

    // Get the teacher record for the current user
    const { data: teacher, error: fetchError } = await supabase
      .from('teachers')
      .select('id, profiles(first_name, last_name)')
      .eq('user_id', req.userId)
      .single();

    if (fetchError || !teacher) {
      return res.status(404).json({ error: 'Teacher record not found' });
    }

    const updateData: any = {
      status: 'active', // Leave as active until admin approves
      notice_start_date: noticeStartDate,
      last_working_date: lastWorkingDate,
      resignation_document_url: resignationDocumentUrl || null
    };

    const { error: updateError } = await supabase
      .from('teachers')
      .update(updateData)
      .eq('id', teacher.id);

    if (updateError) {
      return res.status(400).json({ error: updateError.message });
    }

    // Email notification is now handled by the admin approval route (PATCH /api/teachers/:id/status)

    res.json({ success: true });
  } catch (err: any) {
    console.error('[POST /api/teachers/resign] Error:', err);
    res.status(500).json({ error: 'Failed to submit resignation: ' + err.message });
  }
});
