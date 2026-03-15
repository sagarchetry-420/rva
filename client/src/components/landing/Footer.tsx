import { GraduationCap } from "lucide-react";

export default function Footer() {
  return (
    
    <footer className="bg-foreground text-background py-12">
      <div className="container mx-auto px-4">
        <div className="grid sm:grid-cols-3 gap-8 mb-8">
          <div>
            <div className="flex items-center gap-2 mb-4">
              <div className="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
                <GraduationCap className="w-5 h-5 text-primary-foreground" />
              </div>
              <span className="font-display font-bold">Rose Valley Academy</span>
            </div>
            <p className="text-sm opacity-70">Nurturing minds, building futures since 1995.</p>
          </div>
          <div>
            <h4 className="font-display font-semibold mb-3">Quick Links</h4>
            <div className="space-y-2 text-sm opacity-70">
              <a href="#about" className="block hover:opacity-100">About Us</a>
              <a href="#courses" className="block hover:opacity-100">Courses</a>
              <a href="#contact" className="block hover:opacity-100">Contact</a>
            </div>
          </div>
          <div>
            <section id="portals">
            <h4 className="font-display font-semibold mb-3">Portal</h4>
            <div className="space-y-2 text-sm opacity-70">
              <a href="/studentlogin" className="block hover:opacity-100">Student Login</a>
              <a href="/teacherlogin" className="block hover:opacity-100">Teacher Login</a>
              <a href="/login" className="block hover:opacity-100">Admin Login</a>
            </div>
            </section>
          </div>
        </div>
        <div className="border-t border-background/20 pt-6 text-center text-sm opacity-60">
          © {new Date().getFullYear()} Rose Valley Academy. All rights reserved.
        </div>
      </div>
    </footer>
    
  );
}
