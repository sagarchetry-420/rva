import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { ChevronLeft, ChevronRight, Bell } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

interface Notice {
  id: string;
  title: string;
  content: string;
  publish_date: string;
  document_url?: string | null;
}

export default function NoticesCarousel() {
  const [notices, setNotices] = useState<Notice[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentIndex, setCurrentIndex] = useState(0);

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

  // Auto-advance carousel every 5 seconds
  useEffect(() => {
    if (notices.length === 0) return;
    
    const timer = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % notices.length);
    }, 5000);

    return () => clearInterval(timer);
  }, [notices.length]);

  const goToPrevious = () => {
    setCurrentIndex((prev) => (prev - 1 + notices.length) % notices.length);
  };

  const goToNext = () => {
    setCurrentIndex((prev) => (prev + 1) % notices.length);
  };

  if (loading || notices.length === 0) return null;

  const currentNotice = notices[currentIndex];

  return (
    <div className="w-full bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-sm sticky top-[72px] md:top-[80px] z-40">
      <div className="container mx-auto px-6 md:px-12 py-3">
        <div className="flex items-center gap-2 md:gap-4">
          {/* Bell Icon and Title */}
          <div className="flex-shrink-0 flex items-center gap-2">
            <div className="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
              <Bell className="w-4 h-4 text-orange-600" />
            </div>
            <div className="hidden sm:block">
              <p className="text-xs font-semibold text-slate-600 uppercase tracking-wide leading-none">Latest</p>
            </div>
          </div>

          {/* Carousel Content */}
          <div className="flex-1 min-w-0">
            <AnimatePresence mode="wait">
              <motion.div
                key={currentIndex}
                initial={{ opacity: 0, y: 5 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -5 }}
                transition={{ duration: 0.3 }}
                 className="cursor-pointer group relative"
               >
                 {currentNotice.document_url && (
                   <a
                     href={currentNotice.document_url}
                     target="_blank"
                     rel="noopener noreferrer"
                     className="absolute inset-0 z-10"
                     aria-label={`View document for ${currentNotice.title}`}
                   />
                 )}
                 <div className="flex flex-col gap-0.5 relative z-0">
                   <p className="text-xs md:text-sm font-bold text-slate-900 group-hover:text-orange-600 transition-colors line-clamp-1">
                     {currentNotice.title}
                   </p>
                   <p className="text-xs text-slate-600 line-clamp-1">
                     {currentNotice.content}
                   </p>
                 </div>
               </motion.div>
            </AnimatePresence>
          </div>

          {/* Navigation Buttons */}
          <div className="flex-shrink-0 flex items-center gap-1">
            <button
              onClick={goToPrevious}
               className="p-1.5 rounded-md bg-slate-100 hover:bg-orange-50 text-slate-700 hover:text-orange-600 transition-all"
               aria-label="Previous notice"
              >
              <ChevronLeft className="w-4 h-4" />
            </button>
            
             <div className="hidden sm:flex items-center gap-1 px-2 py-1 bg-slate-100 rounded-md">
               {notices.map((_, idx) => (
                 <button
                  key={idx}
                  onClick={() => setCurrentIndex(idx)}
                  className={`w-1.5 h-1.5 rounded-full transition-all ${
                     idx === currentIndex
                        ? "bg-orange-500 w-4"
                      : "bg-slate-300 hover:bg-slate-400"
                     }`}
                  aria-label={`Go to notice ${idx + 1}`}
                />
              ))}
            </div>

            <button
              onClick={goToNext}
               className="p-1.5 rounded-md bg-slate-100 hover:bg-orange-50 text-slate-700 hover:text-orange-600 transition-all"
               aria-label="Next notice"
              >
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>

          {/* Counter */}
           <div className="hidden md:block px-2 py-1 bg-slate-100 rounded-md text-xs font-semibold text-slate-600">
             {currentIndex + 1}/{notices.length}
           </div>
        </div>
      </div>
    </div>
  );
}
