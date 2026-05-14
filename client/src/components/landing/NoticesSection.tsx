import { useEffect, useState } from "react";
import { motion } from "framer-motion";
import { Megaphone, Calendar, ArrowRight, FileText } from "lucide-react";

interface Notice {
  id: string;
  title: string;
  content: string;
  publish_date: string;
  document_url: string | null;
}

const API_URL = import.meta.env.VITE_API_URL || "";

export default function NoticesSection() {
  const [notices, setNotices] = useState<Notice[]>([]);
  const [loading, setLoading] = useState(true);
  const [expandedId, setExpandedId] = useState<string | null>(null);

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
    <section id="notices" className="py-20 relative overflow-hidden bg-white text-slate-900">
      {/* Decorative shapes - subtle */}
      <motion.div 
        animate={{ scale: [1, 1.1, 1] }}
        transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
        className="absolute top-10 right-20 w-40 h-40 rounded-full bg-slate-100 opacity-50"
      />

      <motion.div 
        animate={{ x: [0, -20, 0] }}
        transition={{ duration: 10, repeat: Infinity, ease: "easeInOut" }}
        className="absolute bottom-20 left-10 w-32 h-32 rounded-full bg-slate-50 opacity-50"
      />

      <div className="container mx-auto px-6 md:px-12 relative z-10">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-12"
        >
          <motion.div
            className="inline-flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-6"
            initial={{ opacity: 0, scale: 0.8 }}
            whileInView={{ opacity: 1, scale: 1 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5, ease: "easeOut" }}
          >
            <Megaphone className="w-4 h-4" />
            Latest Updates
          </motion.div>
          <h2 className="font-sans font-extrabold tracking-tight text-4xl lg:text-5xl mb-4 text-slate-900">
            Notices <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500">&amp; Announcements</span>
          </h2>
          <p className="text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
            Stay updated with the latest news, events, and important announcements from Rose Valley Academy.
          </p>
        </motion.div>

        {loading ? (
          <div className="text-center text-slate-500 py-12 text-sm font-medium">Loading notices…</div>
        ) : (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {notices.map((notice, i) => (
              <motion.div
                key={notice.id}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: i * 0.1, duration: 0.5 }}
                className="group relative bg-white rounded-xl p-5 border border-slate-200 shadow-sm hover:shadow-xl hover:border-orange-200 hover:-translate-y-1 transition-all duration-300 overflow-hidden"
              >
                {/* Gradient overlay on hover */}
                <div className="absolute inset-0 bg-gradient-to-br from-orange-50/50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl pointer-events-none" />

                {notice.document_url && (
                  <a
                    href={notice.document_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="absolute inset-0 z-10"
                    aria-label={`View document for ${notice.title}`}
                  />
                )}

                <div className="relative z-0">
                  <div className="flex items-center gap-2 text-xs text-slate-500 mb-3">
                    <div className="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center">
                      <Calendar className="w-3 h-3 text-slate-600" />
                    </div>
                    <time dateTime={notice.publish_date} className="font-semibold tracking-widest uppercase text-[10px]">
                      {new Date(notice.publish_date).toLocaleDateString("en-IN", {
                        day: "numeric",
                        month: "short",
                        year: "numeric",
                      })}
                    </time>
                  </div>
                  <h3 className="font-sans font-bold text-lg leading-tight tracking-tight text-slate-900 mb-2 group-hover:text-orange-600 transition-colors">
                    {notice.title}
                  </h3>
                  <p className={`text-slate-600 text-sm leading-relaxed mb-4 transition-all duration-300 ${expandedId === notice.id ? "" : "line-clamp-3"}`}>
                    {notice.content}
                  </p>
                  <button
                    onClick={() => setExpandedId(expandedId === notice.id ? null : notice.id)}
                    className="inline-flex items-center gap-1.5 text-orange-500 font-bold text-xs tracking-wide uppercase hover:text-orange-600 transition-colors focus:outline-none relative z-20"
                  >
                    {expandedId === notice.id ? "Show Less" : "Read More"} 
                    <ArrowRight className={`w-3.5 h-3.5 transition-transform duration-300 ${expandedId === notice.id ? "-rotate-90" : "group-hover:translate-x-1"}`} />
                  </button>
                </div>

                {/* Top accent line */}
                <div className="absolute top-0 left-0 h-[2px] w-0 bg-gradient-to-r from-orange-400 to-amber-500 group-hover:w-full transition-all duration-500 z-0" />
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
