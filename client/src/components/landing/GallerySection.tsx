import { motion } from "framer-motion";

const galleryItems = [
  { title: "Campus Memories", category: "Events", image: "/gallery/photo.jpg", span: "md:col-span-2 md:row-span-2" },
  { title: "Student Activities", category: "Campus Life", image: "/gallery/photo2.jpg", span: "md:col-span-1 md:row-span-1" },
  { title: "Classroom Moments", category: "Academics", image: "/gallery/photo3.jpg", span: "md:col-span-1 md:row-span-1" },
  { title: "Cultural Highlights", category: "Culture", image: "/gallery/photo4.jpg", span: "md:col-span-1 md:row-span-1" },
  { title: "Learning in Action", category: "Academics", image: "/gallery/photo5.jpg", span: "md:col-span-1 md:row-span-1" },
  { title: "School Events", category: "Events", image: "/gallery/photo6.jpg", span: "md:col-span-1 md:row-span-1" },
  { title: "Celebration Moments", category: "Campus Life", image: "/gallery/photo8.jpg", span: "md:col-span-1 md:row-span-1" },
  { title: "NCC Activities", category: "Discipline", image: "/gallery/ncc.jpg", span: "md:col-span-1 md:row-span-1" },
  { title: "NCC Excellence", category: "Discipline", image: "/gallery/ncc2.jpg", span: "md:col-span-1 md:row-span-1" },
];

export default function GallerySection() {
  return (
    <section id="gallery" className="py-20 relative overflow-hidden bg-white text-slate-900">
      <motion.div
        animate={{ scale: [1, 1.1, 1] }}
        transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
        className="absolute top-10 right-20 w-40 h-40 rounded-full bg-slate-100 opacity-60"
      />
      <motion.div
        animate={{ x: [0, -20, 0] }}
        transition={{ duration: 10, repeat: Infinity, ease: "easeInOut" }}
        className="absolute bottom-20 left-10 w-32 h-32 rounded-full bg-slate-50 opacity-60"
      />

      <div className="container mx-auto px-4 relative z-10">
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <div className="inline-flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-4">
            Campus Life
          </div>
          <h2 className="font-sans font-extrabold tracking-tight text-4xl lg:text-5xl mb-4 text-slate-900">
            Photo <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500">Gallery</span>
          </h2>
          <p className="text-slate-600 text-lg max-w-2xl mx-auto font-medium">
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
              className={`group relative rounded-xl overflow-hidden border border-slate-200 cursor-pointer shadow-sm hover:shadow-xl hover:border-orange-200 hover:-translate-y-1 transition-all duration-300 ${item.span}`}
            >
              <img
                src={item.image}
                alt={`${item.title} - ${item.category}`}
                loading="lazy"
                decoding="async"
                sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw"
                className="absolute inset-0 w-full h-full object-cover object-center transform-gpu group-hover:scale-105 transition-transform duration-700 ease-out"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent" />

              <div className="absolute bottom-0 left-0 right-0 p-6 md:p-8 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                <span className="inline-block text-xs font-bold text-orange-200 bg-black/25 backdrop-blur-md border border-white/20 px-3 py-1 rounded-full uppercase tracking-wider mb-3">
                  {item.category}
                </span>
                <h3 className="font-sans font-bold text-2xl text-white leading-tight drop-shadow-sm">
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
