import { Facebook, Twitter, Instagram } from "lucide-react";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";

export default function Footer() {
  return (
    <footer className="bg-gray-900 text-white pt-20 pb-6 relative overflow-hidden">
      {/* Decorative gradient blur in footer */}
      <motion.div 
        animate={{ scale: [1, 1.1, 1] }}
        transition={{ duration: 10, repeat: Infinity, ease: "easeInOut" }}
        className="absolute top-0 right-1/4 w-[300px] h-[300px] bg-orange-500/10 rounded-full blur-[100px] pointer-events-none" 
      />
      
      <div className="container mx-auto px-4 relative z-10">
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8 mb-16">
          
          <div className="lg:col-span-1">
            <Link to="/" className="flex items-center gap-3 mb-6 inline-block">
              <img src="/logo/logo.png" alt="Rose Valley Academy" className="w-10 h-10 object-contain" />
              <span className="font-display font-bold text-xl text-white tracking-tight">Rose Valley Academy</span>
            </Link>
            <p className="text-sm text-gray-400 leading-relaxed font-medium mb-6 pe-4">
              Cultivating dynamic minds since 2008. Education that prepares you for the world, grounded in traditional values.
            </p>
            <div className="flex items-center gap-4">
              <a href="#" className="w-10 h-10 rounded-full bg-orange-500/20 border border-orange-500/40 flex items-center justify-center text-orange-300 hover:bg-orange-500 hover:text-white hover:border-orange-500 transition-all transform hover:-translate-y-1">
                <Facebook className="w-4 h-4" />
              </a>
              <a href="#" className="w-10 h-10 rounded-full bg-orange-500/20 border border-orange-500/40 flex items-center justify-center text-orange-300 hover:bg-orange-500 hover:text-white hover:border-orange-500 transition-all transform hover:-translate-y-1">
                <Twitter className="w-4 h-4" />
              </a>
              <a href="#" className="w-10 h-10 rounded-full bg-orange-500/20 border border-orange-500/40 flex items-center justify-center text-orange-300 hover:bg-orange-500 hover:text-white hover:border-orange-500 transition-all transform hover:-translate-y-1">
                <Instagram className="w-4 h-4" />
              </a>
            </div>
          </div>

          <div>
            <h4 className="font-display font-bold text-lg mb-6 text-white uppercase tracking-wider">Quick Links</h4>
            <div className="space-y-4">
              {['Home', 'About Us', 'Courses', 'Admissions', 'Contact'].map((item) => (
                <a key={item} href={`#${item.toLowerCase().replace(' ', '')}`} className="block text-sm font-medium text-gray-400 hover:text-orange-400 transition-colors flex items-center gap-2 group">
                  <span className="w-1 h-1 rounded-full bg-orange-500/0 group-hover:bg-orange-500 transition-all" />
                  {item}
                </a>
              ))}
            </div>
          </div>

          <div>
            <h4 className="font-display font-bold text-lg mb-6 text-white uppercase tracking-wider">Academics</h4>
            <div className="space-y-4">
              {['Notice Board', 'School Calendar', 'Examination', 'Faculty', 'Library'].map((item) => (
                <a key={item} href="#" className="block text-sm font-medium text-gray-400 hover:text-orange-400 transition-colors flex items-center gap-2 group">
                  <span className="w-1 h-1 rounded-full bg-orange-500/0 group-hover:bg-orange-500 transition-all" />
                  {item}
                </a>
              ))}
            </div>
          </div>

          <div id="portals">
            <h4 className="font-display font-bold text-lg mb-6 text-white uppercase tracking-wider">Portals</h4>
            <div className="space-y-4 mb-8 text-sm font-medium">
              <Link to="/studentlogin" className="block text-gray-400 hover:text-orange-400 transition-colors">• Student Portal</Link>
              <Link to="/teacherlogin" className="block text-gray-400 hover:text-orange-400 transition-colors">• Teacher Portal</Link>
              <Link to="/login" className="block text-gray-400 hover:text-orange-400 transition-colors">• Admin Portal</Link>
            </div>
          </div>

        </div>

        <div className="pt-8 flex flex-col md:flex-row items-center justify-between border-t border-gray-800 gap-4">
          <p className="text-sm font-medium text-gray-500">
            © {new Date().getFullYear()} Rose Valley Academy. All rights reserved.
          </p>
          <div className="flex gap-6 text-sm font-medium text-gray-500">
            <a href="#" className="hover:text-orange-400 transition-colors">Privacy Policy</a>
            <a href="#" className="hover:text-orange-400 transition-colors">Terms of Service</a>
          </div>
        </div>
      </div>
    </footer>
  );
}
