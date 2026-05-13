-- Make department nullable in teachers table
ALTER TABLE public.teachers ALTER COLUMN department DROP NOT NULL;
