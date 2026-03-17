import { useEffect, useState } from "react";
import { motion } from "framer-motion";
import { Megaphone, Calendar } from "lucide-react";

interface Notice {
  id: string;
  title: string;
  content: string;
  publish_date: string;
}

const API_URL = import.meta.env.VITE_API_URL || "";

export default function NoticesSection() {
  const [notices, setNotices] = useState<Notice[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`${API_URL}/api/notices/public`)
      .then((res) => res.json())
      .then((data) => {
        if (Array.isArray(data)) setNotices(data);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  if (!loading && notices.length === 0) return null;

  return (
    <section id="notices" className="py-24 bg-muted/30">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <Megaphone className="w-12 h-12 mx-auto mb-4 text-primary" />
          <h2 className="font-display text-4xl font-bold mb-4 text-foreground">
            Notices &amp; Announcements
          </h2>
          <div className="w-16 h-1 bg-primary rounded mx-auto mb-4" />
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Stay updated with the latest announcements from our school.
          </p>
        </motion.div>

        {loading ? (
          <div className="text-center text-muted-foreground">Loading notices…</div>
        ) : (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {notices.map((notice, i) => (
              <motion.div
                key={notice.id}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: i * 0.05 }}
                className="bg-card border border-border rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow"
              >
                <div className="flex items-center gap-2 text-xs text-muted-foreground mb-3">
                  <Calendar className="w-3.5 h-3.5" />
                  <time dateTime={notice.publish_date}>
                    {new Date(notice.publish_date).toLocaleDateString("en-IN", {
                      day: "numeric",
                      month: "short",
                      year: "numeric",
                    })}
                  </time>
                </div>
                <h3 className="font-display font-bold text-lg text-card-foreground mb-2">
                  {notice.title}
                </h3>
                <p className="text-sm text-muted-foreground leading-relaxed line-clamp-3">
                  {notice.content}
                </p>
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
