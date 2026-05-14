import imageCompression from "browser-image-compression";
import { PDFDocument } from "pdf-lib";

export async function compressFile(file: File): Promise<File | Blob> {
  const fileExt = file.name.split('.').pop()?.toLowerCase();
  
  // Handle Images
  if (fileExt && ['jpg', 'jpeg', 'png', 'webp'].includes(fileExt)) {
    const options = {
      maxSizeMB: 1,
      maxWidthOrHeight: 1920,
      useWebWorker: true
    };
    try {
      console.log(`[Compression] Compressing image: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)}MB)`);
      const compressedBlob = await imageCompression(file, options);
      console.log(`[Compression] Image compressed: ${(compressedBlob.size / 1024 / 1024).toFixed(2)}MB`);
      return compressedBlob;
    } catch (error) {
      console.error("[Compression] Image compression error:", error);
      return file;
    }
  }

  // Handle PDFs
  if (fileExt === 'pdf') {
    try {
      console.log(`[Compression] Optimizing PDF: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)}MB)`);
      const arrayBuffer = await file.arrayBuffer();
      const pdfDoc = await PDFDocument.load(arrayBuffer);
      
      // Basic optimization: rewrite the PDF structure
      // Note: This doesn't dramatically shrink PDFs with images unless we resize them manually,
      // but it helps clean up metadata and internal streams.
      const compressedPdfBytes = await pdfDoc.save({ 
        useObjectStreams: true,
        addDefaultFonts: false,
        updateFieldAppearances: false
      });
      
      const compressedBlob = new Blob([compressedPdfBytes], { type: 'application/pdf' });
      console.log(`[Compression] PDF optimized: ${(compressedBlob.size / 1024 / 1024).toFixed(2)}MB`);
      
      // Only return compressed if it's actually smaller
      return compressedBlob.size < file.size ? compressedBlob : file;
    } catch (error) {
      console.error("[Compression] PDF optimization error:", error);
      return file;
    }
  }

  return file;
}
