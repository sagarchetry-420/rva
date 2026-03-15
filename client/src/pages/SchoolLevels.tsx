import React, { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useToast } from "@/hooks/use-toast";
import { Loader2, Plus, Trash2, Layers } from "lucide-react";

interface SchoolLevel {
  id: string;
  name: string;
  description: string | null;
  created_at: string;
}

const SchoolLevelManagement: React.FC = () => {
  const [levels, setLevels] = useState<SchoolLevel[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { toast } = useToast();

  const [formData, setFormData] = useState({
    name: "",
    description: "",
  });

  useEffect(() => {
    fetchLevels();
  }, []);

  const fetchLevels = async () => {
    setLoading(true);
    try {
      const data = await api.get<SchoolLevel[]>('/api/classes/school-levels');
      setLevels(data);
    } catch (error: any) {
      toast({ title: "Error", description: error.message, variant: "destructive" });
    }
    setLoading(false);
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name) return;

    setIsSubmitting(true);
    try {
      await api.post('/api/classes/school-levels', { name: formData.name, description: formData.description });
      toast({ title: "Success", description: "School level added." });
      setFormData({ name: "", description: "" });
      fetchLevels();
    } catch (error: any) {
      toast({ title: "Failed to create", description: error.message, variant: "destructive" });
    }
    setIsSubmitting(false);
  };

  const handleDelete = async (id: string) => {
    if (!confirm("Warning: Deleting a level may affect classes linked to it. Continue?")) return;

    try {
      await api.delete(`/api/classes/school-levels/${id}`);
      setLevels(levels.filter(l => l.id !== id));
    } catch (error: any) {
      toast({
        title: "Delete failed",
        description: "Check if classes are still linked to this level.",
        variant: "destructive"
      });
    }
  };

  return (
    <div className="container mx-auto p-6 max-w-5xl">
      <div className="flex items-center gap-3 mb-8">
        <div className="p-2 bg-primary/10 rounded-lg">
          <Layers className="w-6 h-6 text-primary" />
        </div>
        <div>
          <h1 className="text-2xl font-bold">School Levels</h1>
          <p className="text-sm text-muted-foreground">Define your school's academic stages</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
        {/* Create Form */}
        <Card className="md:col-span-4 h-fit">
          <CardHeader>
            <CardTitle>Add Level</CardTitle>
            <CardDescription>e.g. Primary, Secondary, Vocational</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleCreate} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="levelName">Name</Label>
                <Input
                  id="levelName"
                  placeholder="Enter level name"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="desc">Description (Optional)</Label>
                <Textarea
                  id="desc"
                  placeholder="Brief details..."
                  className="resize-none"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                />
              </div>
              <Button type="submit" className="w-full" disabled={isSubmitting}>
                {isSubmitting ? <Loader2 className="animate-spin" /> : <Plus className="mr-2 h-4 w-4" />}
                Add Level
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* Levels Table */}
        <Card className="md:col-span-8">
          <CardHeader>
            <CardTitle>Configured Levels</CardTitle>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex justify-center p-8"><Loader2 className="animate-spin text-primary" /></div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Level Name</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead className="w-[80px]"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {levels.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={3} className="text-center py-10 text-muted-foreground">
                        No levels defined yet.
                      </TableCell>
                    </TableRow>
                  ) : (
                    levels.map((level) => (
                      <TableRow key={level.id}>
                        <TableCell className="font-semibold">{level.name}</TableCell>
                        <TableCell className="text-sm text-muted-foreground">
                          {level.description || "—"}
                        </TableCell>
                        <TableCell>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDelete(level.id)}
                            className="text-muted-foreground hover:text-destructive"
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default SchoolLevelManagement;