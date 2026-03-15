import { motion } from "framer-motion";
import { ArrowRight, BookOpen, Users, Award } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";

export default function HeroSection() {
  return (
    <section id="home" className="relative min-h-screen flex items-center pt-16 overflow-hidden">
      {/* Existing Background pattern */}
      <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-background to-secondary/10" />
      
      {/* NEW: Styled Background Image */}
      <div 
        className="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-20 mix-blend-luminosity"
        style={{ backgroundImage: "url('/school-images/rva-image.jpg')" }}
      />

      <div className="absolute top-20 right-0 w-96 h-96 bg-secondary/20 rounded-full blur-3xl" />
      <div className="absolute bottom-0 left-0 w-80 h-80 bg-accent/10 rounded-full blur-3xl" />

      <div className="container mx-auto px-4 relative z-10">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.7 }}
          >
            <div className="inline-flex items-center gap-2 bg-secondary/30 text-secondary-foreground px-4 py-1.5 rounded-full text-sm font-medium mb-6">
              <Award className="w-4 h-4" />
              Excellence in Education Since 1995
            </div>
            <h1 className="font-display text-5xl lg:text-7xl font-extrabold leading-tight mb-6">
              Welcome to{" "}
              <span className="text-primary">Rose Valley</span>{" "}
              <span className="text-accent">Academy</span>
            </h1>
            <p className="text-lg text-muted-foreground max-w-lg mb-8">
              Nurturing minds, building futures. We provide world-class education that empowers students to excel in academics, sports, and life.
            </p>
            <div className="flex flex-wrap gap-4">
              <a href="#about">
                <Button variant="outline" size="lg">Learn More</Button>
              </a>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.7, delay: 0.2 }}
            className="hidden lg:grid grid-cols-2 gap-4"
          >
            {[
              { icon: BookOpen, label: "20+ Courses", color: "bg-primary text-primary-foreground" },
              { icon: Users, label: "1500+ Students", color: "bg-accent text-accent-foreground" },
              { icon: Award, label: "100% Results", color: "bg-secondary text-secondary-foreground" },
              { icon: BookOpen, label: "Expert Faculty", color: "bg-destructive text-destructive-foreground" },
            ].map((item, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: 0.3 + i * 0.1 }}
                className="bg-card border border-border rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow"
              >
                <div className={`w-12 h-12 rounded-xl ${item.color} flex items-center justify-center mb-4`}>
                  <item.icon className="w-6 h-6" />
                </div>
                <p className="font-display font-bold text-xl text-card-foreground">{item.label}</p>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </div>
    </section>
  );
}