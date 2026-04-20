import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Bell, ChevronRight } from "lucide-react";
import { motion } from "framer-motion";

interface Notice {
  id: string;
  title: string;
  content: string;
  publish_date: string;
}

export default function NoticesBanner() {
  const [notices, setNotices] = useState<Notice[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchNotices = async () => {
      try {
        const data = await api.get<Notice[]>("/api/notices/public");
        setNotices(data || []);
      } catch (error) {
        console.error("Failed to fetch notices:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchNotices();
  }, []);

  if (loading || notices.length === 0) return null;

  // Create infinite loop by duplicating notices
  const duplicatedNotices = [...notices, ...notices];

  return (
    <div className="w-full bg-gradient-to-r from-primary/10 via-primary/5 to-primary/10 border-y border-primary/20 overflow-hidden">
      <div className="container mx-auto px-4 py-3">
        <div className="flex items-center gap-3 sm:gap-4">
          {/* Bell Icon */}
          <div className="flex-shrink-0 flex items-center gap-2 text-primary font-bold whitespace-nowrap">
            <Bell className="w-5 h-5 sm:w-6 sm:h-6 animate-bounce" />
            <span className="hidden sm:inline text-sm font-semibold">Updates</span>
          </div>

          {/* Scrolling Notices */}
          <div className="flex-1 overflow-hidden">
            <motion.div
              className="flex gap-6 sm:gap-8"
              animate={{ x: ["0%", "-100%"] }}
              transition={{
                duration: Math.max(30, notices.length * 8),
                repeat: Infinity,
                ease: "linear",
              }}
              onHoverCapture={() => {
                // You can add pause on hover here
              }}
            >
              {duplicatedNotices.map((notice, idx) => (
                <div
                  key={`${notice.id}-${idx}`}
                  className="flex-shrink-0 flex items-center gap-2 group cursor-pointer"
                >
                  <ChevronRight className="w-4 h-4 text-primary/60 group-hover:text-primary transition-colors" />
                  <div className="flex flex-col gap-0.5 min-w-max">
                    <p className="text-xs sm:text-sm font-semibold text-foreground group-hover:text-primary transition-colors line-clamp-1">
                      {notice.title}
                    </p>
                    <p className="text-xs text-muted-foreground line-clamp-1">
                      {notice.content}
                    </p>
                  </div>
                </div>
              ))}
            </motion.div>
          </div>
        </div>
      </div>
    </div>
  );
}
