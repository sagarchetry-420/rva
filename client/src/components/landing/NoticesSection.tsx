import { useEffect, useState } from "react";
import { motion } from "framer-motion";
import { Megaphone, Calendar, ArrowRight } from "lucide-react";

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
    <section id="notices" className="py-24 relative overflow-hidden bg-amber-50">
      {/* Decorative shapes - matching hero design */}
      <motion.div 
        animate={{ scale: [1, 1.1, 1] }}
        transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
        className="absolute top-10 right-20 w-40 h-40 rounded-full bg-yellow-300 opacity-25"
      />

      <motion.div 
        animate={{ x: [0, -20, 0] }}
        transition={{ duration: 10, repeat: Infinity, ease: "easeInOut" }}
        className="absolute bottom-20 left-10 w-32 h-32 rounded-full bg-teal-300 opacity-20"
      />

      {/* Dot pattern - top left */}
      <div className="absolute top-20 left-20 opacity-15">
        <div className="grid grid-cols-6 gap-3">
          {Array.from({ length: 18 }).map((_, i) => (
            <div key={i} className="w-1 h-1 rounded-full bg-gray-400" />
          ))}
        </div>
      </div>

      {/* Dot pattern - bottom right */}
      <div className="absolute bottom-10 right-20 opacity-10">
        <div className="grid grid-cols-5 gap-2">
          {Array.from({ length: 15 }).map((_, i) => (
            <div key={i} className="w-1.5 h-1.5 rounded-full bg-gray-300" />
          ))}
        </div>
      </div>

      <div className="container mx-auto px-4 relative z-10">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <motion.div
            className="inline-block mb-4"
            animate={{ rotate: [0, 10, -10, 0] }}
            transition={{ duration: 3, repeat: Infinity, ease: "easeInOut" }}
          >
            <Megaphone className="w-14 h-14 text-orange-500 drop-shadow-lg" />
          </motion.div>
          <h2 className="font-display text-5xl lg:text-6xl font-black mb-4 text-gray-900">
            Notices <span className="text-transparent bg-clip-text bg-gradient-to-r from-teal-500 to-orange-500">&amp; Announcements</span>
          </h2>
          <p className="text-lg text-gray-700 max-w-3xl mx-auto leading-relaxed">
            Stay updated with the latest news, events, and important announcements from Rose Valley Academy.
          </p>
        </motion.div>

        {loading ? (
          <div className="text-center text-gray-600 py-12">Loading notices…</div>
        ) : (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {notices.map((notice, i) => (
              <motion.div
                key={notice.id}
                initial={{ opacity: 0, y: 30 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: i * 0.1, duration: 0.6 }}
                className="group relative bg-white rounded-2xl p-7 border-2 border-amber-200 shadow-lg hover:shadow-2xl hover:border-orange-300 hover:-translate-y-2 transition-all duration-300 overflow-hidden"
              >
                {/* Gradient overlay on hover */}
                <div className="absolute inset-0 bg-gradient-to-br from-orange-50 to-amber-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl" />

                <div className="relative z-10">
                  <div className="flex items-center gap-2 text-sm text-gray-600 mb-3">
                    <div className="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                      <Calendar className="w-4 h-4 text-orange-600" />
                    </div>
                    <time dateTime={notice.publish_date} className="font-medium">
                      {new Date(notice.publish_date).toLocaleDateString("en-IN", {
                        day: "numeric",
                        month: "short",
                        year: "numeric",
                      })}
                    </time>
                  </div>
                  <h3 className="font-display font-bold text-xl text-gray-900 mb-3 group-hover:text-teal-600 transition-colors line-clamp-2">
                    {notice.title}
                  </h3>
                  <p className="text-gray-700 leading-relaxed line-clamp-3 mb-4">
                    {notice.content}
                  </p>
                  <motion.div
                    className="inline-flex items-center gap-2 text-teal-600 font-semibold text-sm group-hover:text-orange-600 transition-colors"
                    whileHover={{ x: 5 }}
                  >
                    Read More <ArrowRight className="w-4 h-4" />
                  </motion.div>
                </div>

                {/* Top accent line */}
                <div className="absolute top-0 left-0 h-1 w-0 bg-gradient-to-r from-teal-500 to-orange-500 group-hover:w-full transition-all duration-500" />
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
