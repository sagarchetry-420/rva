-- Add class_id column to subjects table to associate subjects with classes
ALTER TABLE public.subjects
ADD COLUMN class_id UUID REFERENCES public.classes(id) ON DELETE CASCADE;

-- Create index for faster lookups
CREATE INDEX idx_subjects_class_id ON public.subjects(class_id);
