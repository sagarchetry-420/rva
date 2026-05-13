import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';

export const adminRouter = Router();

// Helper middleware to check if user is admin
async function requireAdmin(req: any, res: any, next: any) {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }
    next();
  } catch (err) {
    res.status(500).json({ error: 'Authorization check failed' });
  }
}

// GET /api/admin/admins — list all admins
adminRouter.get('/admins', requireAuth, requireAdmin, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('user_roles')
      .select(`
        user_id,
        role,
        created_at,
        profiles (
          first_name,
          last_name
        )
      `)
      .eq('role', 'admin');

    if (error) return res.status(400).json({ error: error.message });

    // Get email for each admin user
    const adminsWithEmail = await Promise.all(
      (data || []).map(async (admin: any) => {
        const { data: authUser } = await supabase.auth.admin.getUserById(admin.user_id);
        return {
          userId: admin.user_id,
          email: authUser?.user?.email || '',
          firstName: admin.profiles?.first_name || '',
          lastName: admin.profiles?.last_name || '',
          createdAt: admin.created_at,
        };
      })
    );

    res.json(adminsWithEmail);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch admins' });
  }
});

// POST /api/admin/promote — promote a user to admin
adminRouter.post('/promote', requireAuth, requireAdmin, async (req, res) => {
  try {
    const { userId } = req.body;

    if (!userId) {
      return res.status(400).json({ error: 'userId is required' });
    }

    const supabase = createAdminClient();

    // Check if user exists
    const { data: authUser, error: authError } = await supabase.auth.admin.getUserById(userId);
    if (authError || !authUser?.user) {
      return res.status(404).json({ error: 'User not found' });
    }

    // Check if already admin
    const { data: existingRole } = await supabase
      .from('user_roles')
      .select('role')
      .eq('user_id', userId)
      .maybeSingle();

    if (existingRole?.role === 'admin') {
      return res.status(400).json({ error: 'User is already an admin' });
    }

    // Update or insert role
    const { error: roleError } = await supabase
      .from('user_roles')
      .upsert({
        user_id: userId,
        role: 'admin',
        created_at: new Date().toISOString(),
      });

    if (roleError) return res.status(400).json({ error: roleError.message });

    res.json({ success: true, message: 'User promoted to admin' });
  } catch (err) {
    res.status(500).json({ error: 'Failed to promote user' });
  }
});

// POST /api/admin/demote — demote an admin to regular user
adminRouter.post('/demote', requireAuth, requireAdmin, async (req, res) => {
  try {
    const { userId } = req.body;

    if (!userId) {
      return res.status(400).json({ error: 'userId is required' });
    }

    const supabase = createAdminClient();

    // Check if trying to demote themselves
    if (userId === req.userId) {
      return res.status(400).json({ error: 'Cannot demote yourself' });
    }

    // Remove admin role
    const { error } = await supabase
      .from('user_roles')
      .delete()
      .eq('user_id', userId)
      .eq('role', 'admin');

    if (error) return res.status(400).json({ error: error.message });

    res.json({ success: true, message: 'User demoted from admin' });
  } catch (err) {
    res.status(500).json({ error: 'Failed to demote user' });
  }
});

// POST /api/admin/create — create a new admin user
adminRouter.post('/create', requireAuth, requireAdmin, async (req, res) => {
  try {
    const { email, password, firstName, lastName } = req.body;

    if (!email || !password) {
      return res.status(400).json({ error: 'Email and password are required' });
    }

    const supabase = createAdminClient();

    // Create auth user
    const { data: userData, error: authError } = await supabase.auth.admin.createUser({
      email,
      password,
      email_confirm: true,
    });

    if (authError) return res.status(400).json({ error: authError.message });

    const userId = userData.user.id;

    // Create profile
    await supabase.from('profiles').insert({
      user_id: userId,
      first_name: firstName || '',
      last_name: lastName || '',
    }).maybeSingle();

    // Create admin role
    const { error: roleError } = await supabase
      .from('user_roles')
      .insert({
        user_id: userId,
        role: 'admin',
      });

    if (roleError) return res.status(400).json({ error: roleError.message });

    res.json({
      success: true,
      admin: {
        userId,
        email,
        firstName: firstName || '',
        lastName: lastName || '',
      }
    });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create admin' });
  }
});

// Helper function to generate random password
function generateRandomPassword(length: number = 8): string {
  const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
  let password = '';
  for (let i = 0; i < length; i++) {
    password += charset.charAt(Math.floor(Math.random() * charset.length));
  }
  return password;
}

// GET /api/admin/me — get current admin's info
adminRouter.get('/me', requireAuth, requireAdmin, async (req, res) => {
  try {
    const supabase = createAdminClient();

    const { data: authUser, error: authError } = await supabase.auth.admin.getUserById(req.userId!);
    if (authError) return res.status(400).json({ error: authError.message });

    const { data: profileData } = await supabase
      .from('profiles')
      .select('first_name, last_name, avatar_url')
      .eq('user_id', req.userId)
      .maybeSingle();

    res.json({
      userId: req.userId,
      email: authUser?.user?.email,
      firstName: profileData?.first_name || '',
      lastName: profileData?.last_name || '',
      avatarUrl: profileData?.avatar_url,
    });
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch admin info' });
  }
});
