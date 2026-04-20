import { motion } from "framer-motion";
import { ArrowRight, PlayCircle } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function HeroSection() {
  return (
    <section id="home" className="relative min-h-[100vh] flex items-center pt-40 overflow-hidden bg-background">
      {/* Dynamic Background Elements */}
      <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-background to-secondary/10" />
      
      {/* Sophisticated Pattern */}
      <div className="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]" style={{ backgroundImage: 'radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0)', backgroundSize: '32px 32px' }}></div>

      {/* Hero Image / Overlay */}
      <div
        className="absolute right-0 top-0 w-full lg:w-[80%] h-full bg-cover bg-right-bottom bg-no-repeat opacity-30 dark:opacity-40 transition-all"
        style={{ backgroundImage: "url('/school_image/school image1.jpg')", maskImage: 'linear-gradient(to left, rgba(0,0,0,1) 0%, rgba(0,0,0,0.5) 30%, transparent 70%)' }}
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
            className="lg:col-span-8 flex flex-col gap-6 sm:gap-8 pt-10 sm:pt-0"
          >
            <h1 className="font-display text-5xl sm:text-6xl lg:text-[5.5rem] font-extrabold leading-[1.1] tracking-tight text-foreground">
              A Small <span className="block">School for a</span>
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary to-primary/60">
                Big Change
              </span>
              <span className="block">With Endless</span>
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary to-primary/60">
                Possibilities
              </span>
            </h1>
            
            <p className="text-lg sm:text-xl text-foreground/80 lg:text-lg leading-relaxed font-medium">
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
              <a href="#gallery" className="flex items-center gap-2.5 text-foreground/80 hover:text-primary font-semibold transition-colors group">
                <div className="w-12 h-12 rounded-full bg-secondary/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <PlayCircle className="w-6 h-6 text-secondary" />
                </div>
                Take a Tour
              </a>
            </motion.div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}