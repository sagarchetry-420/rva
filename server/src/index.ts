import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { studentsRouter } from './routes/students.js';
import { teachersRouter } from './routes/teachers.js';
import { classesRouter } from './routes/classes.js';
import { noticesRouter } from './routes/notices.js';
import { attendanceRouter } from './routes/attendance.js';
import { dashboardRouter } from './routes/dashboard.js';
import { examsRouter } from './routes/exams.js';
import { routinesRouter } from './routes/routines.js';
import { adminRouter } from './routes/admin.js';

dotenv.config();

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

app.listen(PORT, () => {
  console.log(`[server] running on http://localhost:${PORT}`);
});
