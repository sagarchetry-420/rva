import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { studentsRouter } from './routes/students.js';
import { teachersRouter } from './routes/teachers.js';
import { classesRouter } from './routes/classes.js';
import { noticesRouter } from './routes/notices.js';
import { attendanceRouter } from './routes/attendance.js';
import { dashboardRouter } from './routes/dashboard.js';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors({ origin: process.env.CLIENT_ORIGIN || 'http://localhost:8080' }));
app.use(express.json());

app.get('/api/health', (_req, res) => {
  res.json({ status: 'ok' });
});

app.use('/api/students', studentsRouter);
app.use('/api/teachers', teachersRouter);
app.use('/api/classes', classesRouter);
app.use('/api/notices', noticesRouter);
app.use('/api/attendance', attendanceRouter);
app.use('/api/dashboard', dashboardRouter);

app.listen(PORT, () => {
  console.log(`[server] running on http://localhost:${PORT}`);
});
