import { createClient } from '@supabase/supabase-js';
import dotenv from 'dotenv';
dotenv.config();

const supabaseUrl = process.env.SUPABASE_URL || '';
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_KEY || '';

const supabase = createClient(supabaseUrl, supabaseKey);

async function check() {
  const today = new Date().toISOString().split('T')[0];
  console.log('Today:', today);
  
  const { data, error } = await supabase
    .from('exam_subjects')
    .select(`
      id,
      exam_date,
      start_time,
      end_time,
      total_marks,
      passing_marks,
      subjects(id, name, code),
      exams!inner(id, name, class_id, classes(id, name))
    `)
    .gte('exam_date', today)
    .order('exam_date', { ascending: true });
    
  if (error) console.error('Error:', error);
  else console.log('Data:', JSON.stringify(data, null, 2));
}

check();
