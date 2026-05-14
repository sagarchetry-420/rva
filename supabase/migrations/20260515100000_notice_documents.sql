-- Add document_url column to notices table for file attachments
ALTER TABLE public.notices 
ADD COLUMN IF NOT EXISTS document_url VARCHAR;

-- Create the notice_documents storage bucket
INSERT INTO storage.buckets (id, name, public)
VALUES ('notice_documents', 'notice_documents', true)
ON CONFLICT (id) DO NOTHING;

-- Allow public read access to notice documents
CREATE POLICY "Public Access to Notice Documents"
ON storage.objects FOR SELECT
USING ( bucket_id = 'notice_documents' );

-- Allow authenticated users to upload notice documents
CREATE POLICY "Authenticated users can upload notice documents"
ON storage.objects FOR INSERT
WITH CHECK (
    bucket_id = 'notice_documents' AND
    auth.role() = 'authenticated'
);

-- Allow authenticated users to update notice documents
CREATE POLICY "Authenticated users can update notice documents"
ON storage.objects FOR UPDATE
USING (
    bucket_id = 'notice_documents' AND
    auth.role() = 'authenticated'
);

-- Allow authenticated users to delete notice documents
CREATE POLICY "Authenticated users can delete notice documents"
ON storage.objects FOR DELETE
USING (
    bucket_id = 'notice_documents' AND
    auth.role() = 'authenticated'
);
