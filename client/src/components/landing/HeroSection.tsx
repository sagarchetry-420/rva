import { motion, AnimatePresence, useScroll, useTransform } from "framer-motion";
import { ArrowUpRight, ChevronLeft, ChevronRight } from "lucide-react";
import { useRef, useState, useEffect, useCallback } from "react";

const images = [
  "/school_images/img1.jpg",
  "/school_images/img2.jpg",
  "/school_images/img3.jpg",
];

export default function HeroSection() {
  const [[currentImageIndex, direction], setPage] = useState([0, 0]);
  const containerRef = useRef<HTMLElement>(null);

  const { scrollYProgress } = useScroll({
    target: containerRef,
    offset: ["start start", "end start"]
  });

  // Parallax effect for the background images
  const y = useTransform(scrollYProgress, [0, 1], ["0%", "30%"]);

  const paginate = useCallback((newDirection: number) => {
    setPage(([prevIndex]) => {
      let nextIndex = prevIndex + newDirection;
      if (nextIndex < 0) nextIndex = images.length - 1;
      if (nextIndex >= images.length) nextIndex = 0;
      return [nextIndex, newDirection];
    });
  }, []);

  useEffect(() => {
    const interval = setInterval(() => {
      paginate(1);
    }, 6000);
    return () => clearInterval(interval);
  }, [paginate]);

  // Magnetic Button Effect state
  const [mousePosition, setMousePosition] = useState({ x: 0, y: 0 });
  const buttonRef = useRef<HTMLButtonElement>(null);

  const handleMouseMove = (e: React.MouseEvent<HTMLButtonElement>) => {
    if (!buttonRef.current) return;
    const { left, top, width, height } = buttonRef.current.getBoundingClientRect();
    const x = (e.clientX - left - width / 2) * 0.2;
    const y = (e.clientY - top - height / 2) * 0.2;
    setMousePosition({ x, y });
  };

  const resetMousePosition = () => {
    setMousePosition({ x: 0, y: 0 });
  };

  const textVariants = {
    hidden: { opacity: 0, y: 40, filter: "blur(10px)" },
    visible: (custom: number) => ({
      opacity: 1,
      y: 0,
      filter: "blur(0px)",
      transition: { 
        delay: custom * 0.15,
        duration: 0.8,
        ease: [0.215, 0.61, 0.355, 1], // premium easing
      }
    })
  };

  const slideVariants = {
    enter: (direction: number) => ({
      x: direction > 0 ? "100%" : "-100%",
    }),
    center: {
      zIndex: 1,
      x: 0,
    },
    exit: (direction: number) => ({
      zIndex: 0,
      x: direction < 0 ? "100%" : "-100%",
    })
  };

  return (
    <section 
      ref={containerRef}
      id="home" 
      className="relative min-h-[100vh] w-full flex items-center overflow-hidden bg-black text-white"
    >
      {/* Background Visual Area Slider (Full Width & Height) */}
      <div className="absolute inset-0 z-0 overflow-hidden">
        <AnimatePresence initial={false} custom={direction}>
          <motion.div
            key={currentImageIndex}
            custom={direction}
            variants={slideVariants}
            initial="enter"
            animate="center"
            exit="exit"
            transition={{
              x: { type: "tween", duration: 0.8, ease: "easeOut" }
            }}
            style={{ y }}
            className="absolute inset-0 w-full h-[120%] -top-[10%]"
          >
            <img
              src={images[currentImageIndex]}
              alt={`School image ${currentImageIndex + 1}`}
              loading={currentImageIndex === 0 ? "eager" : "lazy"}
              decoding="async"
              className="w-full h-full object-cover object-center"
            />
          </motion.div>
        </AnimatePresence>
        
        {/* Dark overlays for text readability */}
        <div className="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent z-10 pointer-events-none" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/30 z-10 pointer-events-none" />
      </div>

      <div className="container mx-auto px-6 md:px-12 relative z-20 flex flex-col justify-center h-full pt-28 pb-10 pointer-events-none">
        <div className="grid lg:grid-cols-12 gap-12 lg:gap-0 items-center h-full">
          
          {/* Left - Main Content */}
          <div className="lg:col-span-8 xl:col-span-7 flex flex-col items-start text-left z-30 pt-10 lg:pt-0 relative pr-0 lg:pr-10 pointer-events-auto">
            <motion.div
              custom={1}
              initial="hidden"
              animate="visible"
              variants={textVariants}
              className="inline-block"
            >
              <span className="text-xs md:text-sm font-semibold tracking-[0.2em] uppercase text-white/80 mb-6 block border-l-2 border-orange-500 pl-4">
                Inspiring Futures
              </span>
            </motion.div>

            <motion.h1 
              className="font-sans font-extrabold leading-[0.95] tracking-tight text-white mb-8"
              style={{ fontSize: "clamp(3.5rem, 7vw, 6.5rem)" }}
            >
              <motion.span 
                custom={2} initial="hidden" animate="visible" variants={textVariants}
                className="block"
              >
                Visionary
              </motion.span>
              <motion.span 
                custom={3} initial="hidden" animate="visible" variants={textVariants}
                className="block text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500 pb-2"
              >
                Education.
              </motion.span>
            </motion.h1>
            
            <motion.p 
              custom={4} initial="hidden" animate="visible" variants={textVariants}
              className="text-lg md:text-xl text-white/80 leading-relaxed font-light max-w-lg mb-12"
            >
              Empowering the next generation with a world-class environment, innovative learning, and a premium educational experience designed for excellence.
            </motion.p>
            
            <motion.div 
              custom={5} initial="hidden" animate="visible" variants={textVariants}
              className="flex flex-wrap gap-8 items-center"
            >
              <motion.button
                ref={buttonRef}
                onMouseMove={handleMouseMove}
                onMouseLeave={resetMousePosition}
                animate={{ x: mousePosition.x, y: mousePosition.y }}
                transition={{ type: "spring", stiffness: 150, damping: 15, mass: 0.1 }}
                className="relative group overflow-hidden rounded-full px-8 py-4 bg-white text-slate-900 font-semibold text-sm tracking-wide transition-all duration-300 hover:scale-105 shadow-xl hover:shadow-white/20"
              >
                <span className="relative z-10 flex items-center gap-2 group-hover:text-white transition-colors duration-300">
                  Discover More
                  <ArrowUpRight className="w-4 h-4 transition-transform group-hover:translate-x-1 group-hover:-translate-y-1" />
                </span>
                <div className="absolute inset-0 h-full w-full bg-gradient-to-r from-orange-500 to-amber-500 scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-500 ease-out z-0" />
              </motion.button>

              <a 
                href="#about" 
                className="text-sm font-medium tracking-wide text-white/80 hover:text-white transition-colors relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-full after:h-px after:bg-white/30 hover:after:bg-white after:transition-colors"
              >
                View Gallery
              </a>
            </motion.div>
          </div>
        </div>
      </div>

      {/* Navigation Arrows */}
      <div className="absolute bottom-10 right-6 md:right-12 z-20 flex gap-4">
        <button 
          onClick={() => paginate(-1)}
          className="w-12 h-12 flex items-center justify-center rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 hover:scale-110 transition-all shadow-lg"
          aria-label="Previous image"
        >
          <ChevronLeft className="w-6 h-6" />
        </button>
        <button 
          onClick={() => paginate(1)}
          className="w-12 h-12 flex items-center justify-center rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white/20 hover:scale-110 transition-all shadow-lg"
          aria-label="Next image"
        >
          <ChevronRight className="w-6 h-6" />
        </button>
      </div>

    </section>
  );
}