import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { Menu, X, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { motion, AnimatePresence } from "framer-motion";

const navLinks = [
  { label: "Home", href: "#home" },
  { label: "Notices", href: "#notices" },
  { label: "About", href: "#about" },
  { label: "Courses", href: "#courses" },
  { label: "Contact", href: "#contact" },
  { label: "Portals", href: "#portals" },
];

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setScrolled(window.scrollY > 20);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  return (
    <nav
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        scrolled
          ? "bg-[#0A0A0A]/80 backdrop-blur-xl border-b border-white/10 shadow-sm py-3"
          : "bg-transparent py-5"
      }`}
    >
      <div className="container mx-auto flex items-center justify-between px-4">
        {/* LOGO */}
        <Link to="/" className="flex items-center gap-3 group">
          <img src="/logo/logo.png" alt="Rose Valley Academy" className="w-12 h-12 group-hover:scale-105 transition-transform object-contain" />
          <div>
            <span className="font-display font-bold text-lg leading-tight block text-slate-900 tracking-tight">Rose Valley Academy</span>
            <span className="text-[10px] text-amber-600 font-semibold tracking-widest uppercase">Excellence Hub</span>
          </div>
        </Link>

        {/* Desktop Menu */}
        <div className="hidden lg:flex items-center gap-1 px-2 py-1.5 rounded-full bg-slate-900/5 backdrop-blur-sm border border-slate-900/10">
          {navLinks.map((l) => (
            <a
              key={l.href}
              href={l.href}
              className="text-sm font-medium text-slate-700 hover:text-slate-900 px-4 py-2 rounded-full hover:bg-slate-900/10 transition-all relative group"
            >
              {l.label}
              <span className="absolute bottom-1 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-gradient-to-r from-orange-500 to-amber-500 rounded-full group-hover:w-4 transition-all duration-300" />
            </a>
          ))}
        </div>

        {/* Desktop CTA */}
        <div className="hidden lg:flex items-center gap-4">
          <a href="#portals">
            <Button className="rounded-full bg-slate-900 text-white hover:bg-slate-800 shadow-lg hover:shadow-slate-900/20 transition-all group font-semibold">
              Contact us
              <ChevronRight className="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
            </Button>
          </a>
        </div>

        {/* Mobile toggle */}
        <button
          className="lg:hidden p-2 text-slate-900 hover:text-amber-600 hover:bg-slate-100 rounded-full transition-colors"
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
                  className="text-base font-semibold text-slate-600 hover:text-slate-900 p-2 hover:bg-slate-50 rounded-xl transition-colors"
                >
                  {l.label}
                </motion.a>
              ))}
              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: navLinks.length * 0.05 }}
                className="pt-4 border-t border-slate-100"
              >
                <a href="#portals" onClick={() => setOpen(false)}>
                  <Button className="w-full rounded-full py-6 text-lg font-semibold bg-slate-900 text-white shadow-lg hover:bg-slate-800 transition-colors">
                    Contact us
                  </Button>
                </a>
              </motion.div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </nav>
  );
}
