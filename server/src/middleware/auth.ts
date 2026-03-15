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
    return res.status(401).json({ error: 'Missing authorization header' });
  }

  const token = authHeader.split(' ')[1];

  try {
    const supabase = createAdminClient();
    const { data: { user }, error } = await supabase.auth.getUser(token);
    if (error || !user) {
      return res.status(401).json({ error: 'Invalid or expired token' });
    }

    req.accessToken = token;
    req.userId = user.id;
    next();
  } catch {
    return res.status(401).json({ error: 'Authentication failed' });
  }
}
