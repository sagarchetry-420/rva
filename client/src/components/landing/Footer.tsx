import { Facebook, Twitter, Instagram } from "lucide-react";
import { Link } from "react-router-dom";

export default function Footer() {
  return (
    <footer className="bg-white text-slate-900 pt-16 pb-6 relative overflow-hidden border-t border-slate-200">
      <div className="container mx-auto px-4">
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8 mb-16">
          <div className="lg:col-span-1">
            <Link to="/" className="flex items-center gap-3 mb-6 inline-block">
              <img src="/logo/logo.png" alt="Rose Valley Academy" className="w-10 h-10 object-contain" />
              <span className="font-display font-bold text-xl text-slate-900 tracking-tight">Rose Valley Academy</span>
            </Link>
            <p className="text-sm text-slate-600 leading-relaxed font-medium mb-6 pe-4">
              Cultivating dynamic minds since 2008. Education that prepares you for the world, grounded in traditional values.
            </p>
            <div className="flex items-center gap-4">
              <a href="#" className="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-orange-500 hover:text-white hover:border-orange-500 transition-all">
                <Facebook className="w-4 h-4" />
              </a>
              <a href="#" className="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-orange-500 hover:text-white hover:border-orange-500 transition-all">
                <Twitter className="w-4 h-4" />
              </a>
              <a href="#" className="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-orange-500 hover:text-white hover:border-orange-500 transition-all">
                <Instagram className="w-4 h-4" />
              </a>
            </div>
          </div>

          <div>
            <h4 className="font-sans font-bold text-lg mb-6 text-slate-900">Quick Links</h4>
            <div className="space-y-4">
              {["Home", "About Us", "Courses", "Contact"].map((item) => (
                <a key={item} href={`#${item.toLowerCase().replace(" ", "")}`} className="block text-sm font-medium text-slate-600 hover:text-orange-500 transition-colors flex items-center gap-2 group">
                  <span className="w-1 h-1 rounded-full bg-orange-500/0 group-hover:bg-orange-500 transition-all" />
                  {item}
                </a>
              ))}
            </div>
          </div>

          <div>
            <h4 className="font-sans font-bold text-lg mb-6 text-slate-900">Academics</h4>
            <div className="space-y-4">
              {["Notice Board"].map((item) => (
                <a key={item} href="#" className="block text-sm font-medium text-slate-600 hover:text-orange-500 transition-colors flex items-center gap-2 group">
                  <span className="w-1 h-1 rounded-full bg-orange-500/0 group-hover:bg-orange-500 transition-all" />
                  {item}
                </a>
              ))}
            </div>
          </div>

          <div id="portals">
            <h4 className="font-sans font-bold text-lg mb-6 text-slate-900">Portals</h4>
            <div className="space-y-4 mb-8 text-sm font-medium">
              <Link to="/studentlogin" className="block text-slate-600 hover:text-orange-500 transition-colors">• Student Portal</Link>
              <Link to="/teacherlogin" className="block text-slate-600 hover:text-orange-500 transition-colors">• Teacher Portal</Link>
              <Link to="/login" className="block text-slate-600 hover:text-orange-500 transition-colors">• Admin Portal</Link>
            </div>
          </div>
        </div>

        <div className="pt-8 flex flex-col md:flex-row items-center justify-between border-t border-slate-200 gap-4">
          <p className="text-sm font-medium text-slate-500">
            © {new Date().getFullYear()} Rose Valley Academy. All rights reserved.
          </p>
          <div className="flex gap-6 text-sm font-medium text-slate-500">
            <a href="#" className="hover:text-orange-500 transition-colors">Privacy Policy</a>
            <a href="#" className="hover:text-orange-500 transition-colors">Terms of Service</a>
          </div>
        </div>
      </div>
    </footer>
  );
}
