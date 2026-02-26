import { motion } from "framer-motion";

const galleryItems = [
  { title: "Annual Day Celebration", category: "Events" },
  { title: "Science Exhibition", category: "Academics" },
  { title: "Sports Day", category: "Sports" },
  { title: "Cultural Fest", category: "Culture" },
  { title: "Computer Lab", category: "Infrastructure" },
  { title: "Library", category: "Infrastructure" },
];

export default function GallerySection() {
  return (
    <section id="gallery" className="py-24">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <h2 className="font-display text-4xl font-bold mb-4 text-foreground">Photo Gallery</h2>
          <div className="w-16 h-1 bg-primary rounded mx-auto mb-4" />
          <p className="text-muted-foreground max-w-2xl mx-auto">Glimpses of life at Rose Valley Academy</p>
        </motion.div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {galleryItems.map((item, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.05 }}
              className="group relative aspect-[4/3] rounded-2xl overflow-hidden bg-muted border border-border"
            >
              <div className="absolute inset-0 bg-gradient-to-t from-foreground/80 via-foreground/20 to-transparent" />
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="w-16 h-16 rounded-full bg-muted-foreground/20 flex items-center justify-center">
                  <span className="text-2xl font-display font-bold text-muted-foreground/60">{item.title[0]}</span>
                </div>
              </div>
              <div className="absolute bottom-0 left-0 right-0 p-4">
                <span className="text-xs font-medium text-primary-foreground/80 bg-primary/80 px-2 py-1 rounded">{item.category}</span>
                <h3 className="font-display font-semibold text-primary-foreground mt-2">{item.title}</h3>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
