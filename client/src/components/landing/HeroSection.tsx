import { motion } from "framer-motion";
import { ArrowRight, BookOpen, Users, Award, PlayCircle } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";

export default function HeroSection() {
  return (
    <section id="home" className="relative min-h-[100vh] flex items-center pt-24 overflow-hidden bg-background">
      {/* Dynamic Background Elements */}
      <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-background to-secondary/10" />
      
      {/* Sophisticated Pattern */}
      <div className="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]" style={{ backgroundImage: 'radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0)', backgroundSize: '32px 32px' }}></div>

      {/* Hero Image / Overlay */}
      <div 
        className="absolute right-0 top-0 w-full lg:w-[60%] h-full bg-cover bg-center bg-no-repeat opacity-[0.05] dark:opacity-10 lg:mask-image-hero transition-all"
        style={{ backgroundImage: "url('/school-images/rva-image.jpg')", maskImage: 'linear-gradient(to right, transparent, black)' }}
      />

      {/* Abstract Glowing Orbs */}
      <motion.div 
        animate={{ scale: [1, 1.1, 1], opacity: [0.3, 0.5, 0.3] }}
        transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
        className="absolute top-[-10%] sm:top-[20%] right-[-5%] sm:right-[10%] w-[300px] sm:w-[500px] h-[300px] sm:h-[500px] bg-primary/20 rounded-full blur-[80px] sm:blur-[120px] pointer-events-none" 
      />
      <motion.div 
        animate={{ scale: [1, 1.2, 1], opacity: [0.2, 0.4, 0.2] }}
        transition={{ duration: 10, repeat: Infinity, ease: "easeInOut", delay: 1 }}
        className="absolute bottom-[-10%] sm:bottom-[10%] left-[-5%] sm:left-[5%] w-[250px] sm:w-[400px] h-[250px] sm:h-[400px] bg-secondary/20 rounded-full blur-[80px] sm:blur-[100px] pointer-events-none" 
      />

      <div className="container mx-auto px-4 relative z-10 flex flex-col justify-center h-full">
        <div className="grid lg:grid-cols-12 gap-12 items-center">
          
          {/* Main Hero Content */}
          <motion.div
            initial={{ opacity: 0, y: 40 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="lg:col-span-7 flex flex-col gap-6 sm:gap-8 pt-10 sm:pt-0"
          >
            <h1 className="font-display text-5xl sm:text-6xl lg:text-[5.5rem] font-extrabold leading-[1.1] tracking-tight text-foreground">
              Experience <br />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary to-primary/60">
                Limitless
              </span>{" "}
              Learning.
            </h1>
            
            <p className="text-lg sm:text-xl text-muted-foreground/90 max-w-xl leading-relaxed font-medium">
              We cultivate a dynamic environment where passion meets purpose. Join Rose Valley Academy to discover your true potential and shape tomorrow.
            </p>
            
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.4, duration: 0.5 }}
              className="flex flex-wrap gap-4 sm:gap-6 items-center mt-2"
            >
              <a href="#about">
                <Button size="lg" className="rounded-full h-14 px-8 text-base font-semibold shadow-xl shadow-primary/25 hover:shadow-2xl hover:shadow-primary/40 transition-all hover:-translate-y-1">
                  Discover RVA
                  <ArrowRight className="ml-2 w-5 h-5" />
                </Button>
              </a>
              <a href="#courses" className="flex items-center gap-2.5 text-foreground/80 hover:text-primary font-semibold transition-colors group">
                <div className="w-12 h-12 rounded-full bg-secondary/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <PlayCircle className="w-6 h-6 text-secondary" />
                </div>
                Take a Tour
              </a>
            </motion.div>

            {/* Quick Stats below CTA */}
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 0.6, duration: 0.5 }}
              className="flex items-center gap-8 sm:gap-12 mt-8 pt-8 border-t border-border/50"
            >
              <div>
                <p className="font-display text-3xl font-bold text-foreground">98%</p>
                <p className="text-sm font-medium text-muted-foreground">College Acceptance</p>
              </div>
              <div className="w-px h-12 bg-border/50"></div>
              <div>
                <p className="font-display text-3xl font-bold text-foreground">15:1</p>
                <p className="text-sm font-medium text-muted-foreground">Student-Teacher Ratio</p>
              </div>
            </motion.div>
          </motion.div>

          {/* Floating Stat Cards (Desktop Right Side) */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 1, delay: 0.3 }}
            className="hidden lg:block lg:col-span-5 relative h-[600px]"
          >
            {[
              { icon: BookOpen, title: "Modern Curriculum", desc: "Future-ready skills.", color: "text-primary", bg: "bg-primary/10", border: "border-primary/20", pos: "top-10 right-0", delay: 0 },
              { icon: Users, title: "Expert Faculty", desc: "Learn from the best.", color: "text-secondary", bg: "bg-secondary/10", border: "border-secondary/20", pos: "top-1/2 -left-10 transform -translate-y-1/2", delay: 0.2 },
              { icon: Award, title: "Global Recognition", desc: "Award-winning acadmics.", color: "text-accent", bg: "bg-accent/10", border: "border-accent/20", pos: "bottom-10 right-10", delay: 0.4 },
            ].map((stat, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 50, scale: 0.9 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                transition={{ duration: 0.7, delay: 0.6 + stat.delay, type: "spring", stiffness: 100 }}
                className={`absolute ${stat.pos} animate-float group`}
                style={{ animationDelay: `${i * 1.5}s` }}
              >
                <div className={`bg-background/70 backdrop-blur-xl border ${stat.border} rounded-2xl p-6 shadow-2xl shadow-black/5 w-64 hover:scale-105 transition-transform duration-300`}>
                  <div className={`w-12 h-12 rounded-xl ${stat.bg} flex items-center justify-center mb-4 group-hover:rotate-6 transition-transform`}>
                    <stat.icon className={`w-6 h-6 ${stat.color}`} />
                  </div>
                  <h3 className="font-display font-bold text-lg text-foreground mb-1">{stat.title}</h3>
                  <p className="text-sm text-muted-foreground font-medium">{stat.desc}</p>
                </div>
              </motion.div>
            ))}
            
            {/* Center Decorative Circle */}
            <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] border-[1px] border-primary/10 rounded-full animate-[spin_60s_linear_infinite]" />
            <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[300px] h-[300px] border-[1px] border-secondary/10 border-dashed rounded-full animate-[spin_40s_linear_infinite_reverse]" />
          </motion.div>
        </div>
      </div>
    </section>
  );
}