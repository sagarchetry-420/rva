import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { Menu, X } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

const navLinks = [
  { label: "Home", href: "#home" },
  { label: "Notices", href: "#notices" },
  { label: "About", href: "#about" },
  { label: "Courses", href: "#courses" },
  { label: "Contact", href: "#contact" },
];

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [activeSection, setActiveSection] = useState("#home");

  // Scroll-based active section tracking
  useEffect(() => {
    const handleScroll = () => {
      const scrollY = window.scrollY + 120; // offset for sticky nav height
      for (const link of [...navLinks].reverse()) {
        const sectionId = link.href.replace("#", "");
        const el = document.getElementById(sectionId);
        if (el && el.offsetTop <= scrollY) {
          setActiveSection(link.href);
          break;
        }
      }
    };
    window.addEventListener("scroll", handleScroll, { passive: true });
    handleScroll();
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  return (
    <nav
      className="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-sm py-4"
    >
      <div className="container mx-auto flex items-center justify-between px-6 md:px-12">
        {/* LOGO */}
        <Link to="/" className="flex items-center gap-3 group">
          <img 
            src="/logo/logo.png" 
            alt="Rose Valley Academy" 
            className="w-10 h-10 md:w-12 md:h-12 group-hover:scale-105 transition-all object-contain" 
          />
          <div>
            <span className="font-display font-bold leading-tight block text-slate-900 tracking-tight text-base md:text-lg">Rose Valley Academy</span>
            <span className="text-[9px] md:text-[10px] text-orange-600 font-semibold tracking-widest uppercase block">Excellence Hub</span>
          </div>
        </Link>

        {/* Desktop Menu */}
        <div className="hidden lg:flex items-center gap-1">
          {navLinks.map((l) => (
            <a
              key={l.href}
              href={l.href}
              className={`text-sm font-medium px-4 py-2 rounded-full transition-all relative group ${
                activeSection === l.href
                  ? "text-orange-700 bg-orange-50"
                  : "text-slate-600 hover:text-slate-900 hover:bg-slate-50"
              }`}
            >
              {l.label}
               <span className={`absolute bottom-1 left-1/2 -translate-x-1/2 h-0.5 bg-gradient-to-r from-orange-400 to-amber-500 rounded-full transition-all duration-300 ${
                  activeSection === l.href ? "w-5" : "w-0 group-hover:w-4"
                }`} />
            </a>
          ))}
        </div>

        {/* Desktop CTA */}
        <div className="hidden lg:flex items-center gap-4">
          <a href="#portals">
            <button className="rounded-full border border-slate-300 bg-white text-slate-700 hover:text-orange-600 hover:border-orange-300 hover:bg-orange-50 transition-all font-semibold text-sm px-5 py-2.5">
              Portals
            </button>
          </a>
        </div>

        {/* Mobile toggle */}
        <button
          className="lg:hidden p-2 text-slate-900 hover:text-orange-600 hover:bg-slate-100 rounded-full transition-colors"
          onClick={() => setOpen(!open)}
        >
          {open ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {/* Mobile menu */}
      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: "auto" }}
            exit={{ opacity: 0, height: 0 }}
            className="lg:hidden absolute top-full left-0 right-0 bg-white/95 backdrop-blur-xl border-b border-slate-200 shadow-2xl overflow-hidden"
          >
            <div className="px-4 py-6 flex flex-col gap-4">
              {navLinks.map((l, i) => (
                <motion.a
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: i * 0.05 }}
                  key={l.href}
                  href={l.href}
                  onClick={() => setOpen(false)}
                  className={`text-base font-semibold py-2 transition-colors inline-block ${
                    activeSection === l.href
                      ? "text-orange-700"
                      : "text-slate-600 hover:text-slate-900"
                  }`}
                >
                  {l.label}
                </motion.a>
              ))}
              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: navLinks.length * 0.05 }}
                className="pt-4 border-t border-slate-100 space-y-3"
              >
                <Link to="/studentlogin" onClick={() => setOpen(false)}>
                  <button className="w-full rounded-full py-3 text-base font-semibold border border-slate-300 text-slate-700 hover:text-orange-600 hover:border-orange-300 hover:bg-orange-50 transition-colors">
                    Student Portal
                  </button>
                </Link>
                <Link to="/teacherlogin" onClick={() => setOpen(false)}>
                  <button className="w-full rounded-full py-3 text-base font-semibold border border-slate-300 text-slate-700 hover:text-orange-600 hover:border-orange-300 hover:bg-orange-50 transition-colors">
                    Teacher Portal
                  </button>
                </Link>
                <a href="#portals" onClick={() => setOpen(false)}>
                  <button className="w-full rounded-full py-3 text-base font-semibold bg-orange-500 text-white hover:bg-orange-600 transition-colors">
                    Portals
                  </button>
                </a>
              </motion.div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </nav>
  );
}
