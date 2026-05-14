-- Add resignation_document_url field to teachers table
ALTER TABLE public.teachers 
ADD COLUMN IF NOT EXISTS resignation_document_url VARCHAR;

-- Create the teacher_documents bucket if it doesn't exist
INSERT INTO storage.buckets (id, name, public)
VALUES ('teacher_documents', 'teacher_documents', true)
ON CONFLICT (id) DO NOTHING;

-- Create policy to allow public access to teacher_documents
-- We make it public for simplicity so the frontend can easily read/download it without auth tokens, 
-- but you can restrict it by setting public=false and adding SELECT policies based on auth.uid()
CREATE POLICY "Public Access to Teacher Documents"
ON storage.objects FOR SELECT
USING ( bucket_id = 'teacher_documents' );

-- Create policy to allow authenticated users to upload documents
CREATE POLICY "Authenticated users can upload teacher documents"
ON storage.objects FOR INSERT
WITH CHECK (
    bucket_id = 'teacher_documents' AND
    auth.role() = 'authenticated'
);

-- Allow authenticated users to update/delete their documents
CREATE POLICY "Authenticated users can update teacher documents"
ON storage.objects FOR UPDATE
USING (
    bucket_id = 'teacher_documents' AND
    auth.role() = 'authenticated'
);

CREATE POLICY "Authenticated users can delete teacher documents"
ON storage.objects FOR DELETE
USING (
    bucket_id = 'teacher_documents' AND
    auth.role() = 'authenticated'
);
