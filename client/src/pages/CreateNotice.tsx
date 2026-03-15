import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { useAuth } from "@/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label"; // FIXED: Correct import from UI components
import { ArrowLeft, Megaphone, Loader2 } from "lucide-react";

export default function CreateNotice() {
  const [title, setTitle] = useState("");
  const [content, setContent] = useState("");
  const [audience, setAudience] = useState("All");
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const { user } = useAuth();
  const { toast } = useToast();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!user) {
      toast({ title: "Error", description: "You must be logged in to post notices.", variant: "destructive" });
      return;
    }

    setIsSubmitting(true);

    try {
      await api.post('/api/notices', {
        title,
        content,
        targetAudience: audience,
      });

      toast({ title: "Success", description: "Notice published successfully!" });
      
      // Clear form or navigate back
      setTitle("");
      setContent("");
      navigate("/dashboard"); // Or wherever your notice board lives

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

            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin mr-2" />
                  Publishing...
                </>
              ) : (
                "Publish Notice"
              )}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}