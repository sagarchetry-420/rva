import { Request, Response, NextFunction } from 'express';
import { createAdminClient } from '../lib/supabase.js';

declare global {
  namespace Express {
    interface Request {
      accessToken?: string;
      userId?: string;
    }
  }
}

export async function requireAuth(req: Request, res: Response, next: NextFunction) {
  const authHeader = req.headers.authorization;
  if (!authHeader?.startsWith('Bearer ')) {
    console.error('[Auth] Missing authorization header');
    return res.status(401).json({ error: 'Missing authorization header' });
  }

  const token = authHeader.split(' ')[1];

  try {
    const supabase = createAdminClient();
    const { data: { user }, error } = await supabase.auth.getUser(token);

    if (error) {
      console.error('[Auth] Token validation error:', error.message);
      return res.status(401).json({ error: 'Invalid or expired token' });
    }

    if (!user) {
      console.error('[Auth] No user found for token');
      return res.status(401).json({ error: 'User not found' });
    }

    req.accessToken = token;
    req.userId = user.id;
    next();
  } catch (err: any) {
    console.error('[Auth] Authentication error:', err.message);
    return res.status(401).json({ error: 'Authentication failed' });
  }
}
