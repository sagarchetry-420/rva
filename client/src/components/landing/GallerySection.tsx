import { motion } from "framer-motion";

const galleryItems = [
  { title: "Annual Day 2025", category: "Events", span: "md:col-span-2 md:row-span-2" },
  { title: "Science Lab Excursion", category: "Academics", span: "md:col-span-1 md:row-span-1" },
  { title: "Regional Sports Meet", category: "Sports", span: "md:col-span-1 md:row-span-1" },
  { title: "Inter-School Debate", category: "Culture", span: "md:col-span-1 md:row-span-2" },
  { title: "New Robotics Lab", category: "Infrastructure", span: "md:col-span-2 md:row-span-1" },
];

export default function GallerySection() {
  return (
    <section id="gallery" className="py-24 bg-background">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <div className="inline-flex items-center gap-2 bg-primary/10 text-primary px-4 py-1.5 rounded-full text-sm font-semibold mb-4">
            Campus Life
          </div>
          <h2 className="font-display text-4xl lg:text-5xl font-extrabold mb-4 text-foreground tracking-tight">
            Photo <span className="text-primary">Gallery</span>
          </h2>
          <p className="text-muted-foreground text-lg max-w-2xl mx-auto font-medium">
            Glimpses of life, learning, and unforgettable moments at Rose Valley Academy.
          </p>
        </motion.div>

        <div className="grid grid-cols-1 md:grid-cols-3 auto-rows-[250px] gap-4 lg:gap-6">
          {galleryItems.map((item, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, scale: 0.95 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true, margin: "-50px" }}
              transition={{ delay: i * 0.1, duration: 0.5 }}
              className={`group relative rounded-3xl overflow-hidden bg-muted border border-border cursor-pointer shadow-sm hover:shadow-2xl hover:shadow-primary/20 transition-all duration-500 ${item.span}`}
            >
              <div
                className="absolute inset-0 bg-cover bg-center transform group-hover:scale-110 transition-transform duration-700 ease-out saturate-50 group-hover:saturate-100"
                style={{ backgroundImage: "url('/school_image/school image1.jpg')" }}
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-300" />
              
              {/* Optional Placeholder Letter if no image */}
              <div className="absolute inset-0 flex items-center justify-center opacity-0">
                <div className="w-16 h-16 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                  <span className="text-2xl font-display font-bold text-white/50">{item.title[0]}</span>
                </div>
              </div>

              <div className="absolute bottom-0 left-0 right-0 p-6 md:p-8 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                <span className="inline-block text-xs font-bold text-primary bg-primary/20 backdrop-blur-md border border-primary/30 px-3 py-1 rounded-full uppercase tracking-wider mb-3">
                  {item.category}
                </span>
                <h3 className="font-display font-bold text-2xl text-white leading-tight drop-shadow-md">
                  {item.title}
                </h3>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
