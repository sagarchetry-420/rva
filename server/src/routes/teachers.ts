import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';

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

    console.log('[Teachers POST] Request body:', JSON.stringify(req.body, null, 2));
    console.log('[Teachers POST] Creating user:', { email, firstName, lastName, hireDate });
    console.log('[Teachers POST] Assignments:', assignments);

    // Validate required fields
    if (!email || !password || !firstName || !lastName) {
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
      email,
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
      await new Promise(resolve => setTimeout(resolve, 400));
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

    res.json({ user: userData.user });
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
