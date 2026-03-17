import { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import { api } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import {
  ArrowLeft,
  Bell,
  Loader2,
  Megaphone,
  PlusCircle,
  Trash2,
  RefreshCw,
  Calendar,
  Users,
} from "lucide-react";

interface Notice {
  id: string;
  title: string;
  content: string;
  target_audience: string;
  publish_date: string;
  created_at: string;
}

export default function NoticeManagement() {
  const [notices, setNotices] = useState<Notice[]>([]);
  const [loading, setLoading] = useState(true);
  const [deletingId, setDeletingId] = useState<string | null>(null);
  const navigate = useNavigate();
  const { toast } = useToast();

  useEffect(() => {
    fetchNotices();
  }, []);

  const fetchNotices = async () => {
    setLoading(true);
    try {
      const data = await api.get<Notice[]>("/api/notices");
      setNotices(data || []);
    } catch (error: any) {
      toast({
        title: "Error",
        description: "Failed to load notices.",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm("Are you sure you want to delete this notice?")) return;

    setDeletingId(id);
    try {
      await api.delete(`/api/notices/${id}`);
      setNotices(notices.filter((n) => n.id !== id));
      toast({ title: "Deleted", description: "Notice removed successfully." });
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message,
        variant: "destructive",
      });
    } finally {
      setDeletingId(null);
    }
  };

  const audienceBadge = (audience: string) => {
    switch (audience) {
      case "All":
        return "bg-blue-100 text-blue-700 border-blue-200";
      case "Staff":
        return "bg-purple-100 text-purple-700 border-purple-200";
      case "Students":
        return "bg-amber-100 text-amber-700 border-amber-200";
      default:
        return "bg-slate-100 text-slate-700";
    }
  };

  return (
    <div className="p-4 md:p-8 max-w-5xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <Button
            variant="ghost"
            onClick={() => navigate("/dashboard")}
            className="mb-2 p-0 h-auto hover:bg-transparent text-slate-500 hover:text-primary"
          >
            <ArrowLeft className="w-4 h-4 mr-1" /> Back to Dashboard
          </Button>
          <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 flex items-center gap-3">
            <div className="p-2 bg-rose-50 rounded-xl">
              <Megaphone className="w-6 h-6 text-rose-600" />
            </div>
            Notice Management
          </h1>
          <p className="text-slate-500 mt-1 ml-[52px]">
            Create, view, and manage all school announcements.
          </p>
        </div>

        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={fetchNotices}
            disabled={loading}
            className="gap-2"
          >
            <RefreshCw
              className={`w-4 h-4 ${loading ? "animate-spin" : ""}`}
            />
            Refresh
          </Button>
          <Button size="sm" className="gap-2" asChild>
            <Link to="/dashboard/notices/create">
              <PlusCircle className="w-4 h-4" /> New Notice
            </Link>
          </Button>
        </div>
      </div>

      {/* Notices List */}
      {loading ? (
        <div className="flex justify-center py-20">
          <Loader2 className="animate-spin w-8 h-8 text-slate-400" />
        </div>
      ) : notices.length === 0 ? (
        <Card className="border-slate-200">
          <CardContent className="text-center py-16">
            <Bell className="w-16 h-16 text-slate-200 mx-auto mb-4" />
            <h3 className="text-lg font-semibold text-slate-700 mb-1">
              No notices yet
            </h3>
            <p className="text-slate-500 mb-6">
              Create your first announcement to share with the school.
            </p>
            <Button asChild>
              <Link to="/dashboard/notices/create" className="gap-2">
                <PlusCircle className="w-4 h-4" /> Create First Notice
              </Link>
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          <p className="text-sm text-slate-500">
            Showing{" "}
            <span className="font-semibold text-slate-700">
              {notices.length}
            </span>{" "}
            {notices.length === 1 ? "notice" : "notices"}
          </p>

          {notices.map((notice) => (
            <Card
              key={notice.id}
              className="border-slate-200 hover:shadow-md transition-shadow bg-white"
            >
              <CardContent className="p-5">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-3 mb-2">
                      <h3 className="font-semibold text-slate-900 text-lg truncate">
                        {notice.title}
                      </h3>
                      <Badge
                        variant="outline"
                        className={`shrink-0 ${audienceBadge(notice.target_audience)}`}
                      >
                        <Users className="w-3 h-3 mr-1" />
                        {notice.target_audience}
                      </Badge>
                    </div>
                    <p className="text-slate-600 text-sm leading-relaxed line-clamp-2">
                      {notice.content}
                    </p>
                    <p className="text-xs text-slate-400 mt-3 flex items-center gap-1">
                      <Calendar className="w-3 h-3" />
                      Published{" "}
                      {new Date(
                        notice.publish_date || notice.created_at
                      ).toLocaleDateString("en-US", {
                        month: "long",
                        day: "numeric",
                        year: "numeric",
                      })}
                    </p>
                  </div>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="text-slate-400 hover:text-red-600 hover:bg-red-50 shrink-0"
                    onClick={() => handleDelete(notice.id)}
                    disabled={deletingId === notice.id}
                  >
                    {deletingId === notice.id ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <Trash2 className="w-4 h-4" />
                    )}
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
