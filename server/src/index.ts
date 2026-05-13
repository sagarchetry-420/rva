import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { studentsRouter } from './routes/students.js';
import { teachersRouter } from './routes/teachers.js';
import { classesRouter } from './routes/classes.js';
import { noticesRouter } from './routes/notices.js';
import { attendanceRouter } from './routes/attendance.js';
import { dashboardRouter } from './routes/dashboard.js';
import { examsRouter } from './routes/exams.js';
import { routinesRouter } from './routes/routines.js';
import { adminRouter } from './routes/admin.js';

import { globalErrorHandler } from './middleware/errorHandler.js';

const currentFile = fileURLToPath(import.meta.url);
const serverRoot = path.resolve(path.dirname(currentFile), '..');
const repoRoot = path.resolve(serverRoot, '..');
const serverEnvPath = path.join(serverRoot, '.env');
const repoEnvPath = path.join(repoRoot, '.env');
dotenv.config({ path: serverEnvPath });
dotenv.config({ path: repoEnvPath });

type RequiredEnvSpec = {
  key: string;
  aliases?: string[];
};

function resolveEnvVar(spec: RequiredEnvSpec): { key: string; value: string } | null {
  const candidates = [spec.key, ...(spec.aliases ?? [])];
  const normalizedCandidates = new Set(candidates.map((candidate) => candidate.trim().toUpperCase()));

  for (const [rawKey, rawValue] of Object.entries(process.env)) {
    if (!normalizedCandidates.has(rawKey.trim().toUpperCase())) {
      continue;
    }
    const value = typeof rawValue === 'string' ? rawValue.trim() : '';
    if (!value) {
      continue;
    }
    return { key: rawKey, value };
  }

  return null;
}

// Validate critical env vars at startup (with common alias recovery)
const requiredEnvSpecs: RequiredEnvSpec[] = [
  { key: 'SUPABASE_URL', aliases: ['SUPABABSE_URL', 'supabase_url'] },
  { key: 'SUPABASE_SERVICE_ROLE_KEY', aliases: ['supabase_service_role_key'] },
];

for (const spec of requiredEnvSpecs) {
  const resolved = resolveEnvVar(spec);
  if (!resolved) {
    const checkedKeys = [spec.key, ...(spec.aliases ?? [])].join(', ');
    console.error(
      `[FATAL] Missing required environment variable: ${spec.key} (checked: ${checkedKeys}). ` +
      `Set it in ${serverEnvPath} (or ${repoEnvPath}).`
    );
    process.exit(1);
  }

  process.env[spec.key] = resolved.value;
  if (resolved.key !== spec.key) {
    console.warn(
      `[WARN] Using ${resolved.key} as ${spec.key}. Rename it to ${spec.key} in your .env file.`
    );
  }
}

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors({ origin: process.env.CLIENT_ORIGIN || 'http://localhost:8080' }));

// Parse JSON only for requests with a body (skip for GET/DELETE)
app.use((req, res, next) => {
  if (req.method === 'GET' || req.method === 'DELETE') {
    return next();
  }
  express.json()(req, res, next);
});

app.get('/api/health', (_req, res) => {
  res.json({ status: 'ok' });
});

app.use('/api/students', studentsRouter);
app.use('/api/teachers', teachersRouter);
app.use('/api/classes', classesRouter);
app.use('/api/notices', noticesRouter);
app.use('/api/attendance', attendanceRouter);
app.use('/api/dashboard', dashboardRouter);
app.use('/api/exams', examsRouter);
app.use('/api/routines', routinesRouter);
app.use('/api/admin', adminRouter);

// Global error handler — must be LAST
app.use(globalErrorHandler);

app.listen(PORT, () => {
  console.log(`[server] running on http://localhost:${PORT}`);
});
