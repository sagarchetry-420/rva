import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { useAuth } from "@/hooks/useAuth";
import { supabase } from "@/integrations/supabase/client";
import { compressFile } from "@/lib/fileCompression";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { ArrowLeft, Megaphone, Loader2, Paperclip, X, FileText } from "lucide-react";

export default function CreateNotice() {
  const [title, setTitle] = useState("");
  const [content, setContent] = useState("");
  const [audience, setAudience] = useState("All");
  const [documentFile, setDocumentFile] = useState<File | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const { user } = useAuth();
  const { toast } = useToast();
  const navigate = useNavigate();

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0] || null;
    if (file) {
      // 10MB limit
      if (file.size > 10 * 1024 * 1024) {
        toast({
          title: "File too large",
          description: "Maximum file size is 10MB.",
          variant: "destructive",
        });
        return;
      }
      setDocumentFile(file);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!user) {
      toast({ title: "Error", description: "You must be logged in to post notices.", variant: "destructive" });
      return;
    }

    setIsSubmitting(true);

    try {
      let documentUrl: string | null = null;

      // Upload document to Supabase Storage if attached
      if (documentFile) {
        const fileToUpload = await compressFile(documentFile);
        const fileExt = documentFile.name.split('.').pop()?.toLowerCase();
        const sanitizedName = documentFile.name.replace(/[^a-zA-Z0-9.-]/g, '_');
        const fileName = `${Date.now()}-${sanitizedName}`;

        const { error: uploadError } = await supabase.storage
          .from('notice_documents')
          .upload(fileName, fileToUpload);

        if (uploadError) {
          throw new Error("Failed to upload document: " + uploadError.message);
        }

        const { data: { publicUrl } } = supabase.storage
          .from('notice_documents')
          .getPublicUrl(fileName);

        documentUrl = publicUrl;
      }

      await api.post('/api/notices', {
        title,
        content,
        targetAudience: audience,
        documentUrl,
      });

      toast({ title: "Success", description: "Notice published successfully!" });
      
      // Clear form or navigate back
      setTitle("");
      setContent("");
      setDocumentFile(null);
      navigate("/dashboard/notices");

    } catch (error: any) {
      toast({ 
        title: "Publication Failed", 
        description: error.message, 
        variant: "destructive" 
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="container mx-auto py-10 px-4">
      <Button 
        variant="ghost" 
        onClick={() => navigate(-1)} 
        className="mb-6 gap-2"
      >
        <ArrowLeft className="w-4 h-4" /> Back
      </Button>

      <Card className="max-w-2xl mx-auto shadow-lg border-t-4 border-t-primary">
        <CardHeader>
          <div className="flex items-center gap-3">
            <div className="p-2 bg-primary/10 rounded-lg">
              <Megaphone className="w-6 h-6 text-primary" />
            </div>
            <div>
              <CardTitle className="text-2xl">Create New Notice</CardTitle>
              <CardDescription>Broadcast information to students or staff.</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="title">Notice Title</Label>
              <Input 
                id="title"
                placeholder="e.g. School Holiday Announcement"
                value={title} 
                onChange={(e) => setTitle(e.target.value)} 
                required 
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="audience">Target Audience</Label>
              <Select onValueChange={setAudience} defaultValue="All">
                <SelectTrigger id="audience">
                  <SelectValue placeholder="Select who can see this" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="All">Everyone</SelectItem>
                  <SelectItem value="Staff">Staff Only</SelectItem>
                  <SelectItem value="Students">Students Only</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="content">Notice Content</Label>
              <Textarea 
                id="content"
                placeholder="Write your announcement details here..."
                value={content} 
                onChange={(e) => setContent(e.target.value)} 
                rows={6} 
                required 
              />
            </div>

            {/* Document Attachment */}
            <div className="space-y-2">
              <Label>Attach Document (Optional)</Label>
              {!documentFile ? (
                <div className="relative">
                  <Input
                    id="document"
                    type="file"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp"
                    onChange={handleFileChange}
                    className="cursor-pointer"
                  />
                  <p className="text-xs text-muted-foreground mt-1">
                    Supported: PDF, Word, Excel, PowerPoint, Images. Max 10MB.
                  </p>
                </div>
              ) : (
                <div className="flex items-center gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                  <div className="p-2 bg-emerald-100 rounded-md">
                    <FileText className="w-4 h-4 text-emerald-600" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-emerald-800 truncate">
                      {documentFile.name}
                    </p>
                    <p className="text-xs text-emerald-600">
                      {(documentFile.size / 1024).toFixed(1)} KB
                    </p>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="shrink-0 text-emerald-600 hover:text-red-600 hover:bg-red-50"
                    onClick={() => setDocumentFile(null)}
                  >
                    <X className="w-4 h-4" />
                  </Button>
                </div>
              )}
            </div>

            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin mr-2" />
                  {documentFile ? "Uploading & Publishing..." : "Publishing..."}
                </>
              ) : (
                <span className="flex items-center gap-2">
                  {documentFile && <Paperclip className="w-4 h-4" />}
                  Publish Notice
                </span>
              )}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}