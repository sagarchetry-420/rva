import { motion } from "framer-motion";
import { Trophy, Star } from "lucide-react";

const toppers = [
  { name: "Ananya Sharma", score: "98.6%", year: "2025", stream: "Science", image: "A" },
  { name: "Rahul Verma", score: "97.8%", year: "2025", stream: "Commerce", image: "R" },
  { name: "Priya Patel", score: "97.2%", year: "2025", stream: "Arts", image: "P" },
  { name: "Arjun Singh", score: "96.9%", year: "2024", stream: "Science", image: "A" },
  { name: "Meera Nair", score: "96.5%", year: "2024", stream: "Commerce", image: "M" },
  { name: "Kavya Reddy", score: "96.1%", year: "2024", stream: "Science", image: "K" },
];

export default function ToppersSection() {
  return (
    <section id="toppers" className="py-24 relative overflow-hidden bg-gradient-to-br from-primary via-primary/95 to-primary">
      {/* Decorative Background Patterns */}
      <div className="absolute inset-0 opacity-5" style={{ backgroundImage: 'radial-gradient(circle at 1px 1px, rgba(255,255,255,0.2) 1px, transparent 1px)', backgroundSize: '20px 20px' }}></div>
      <div className="absolute top-0 right-0 w-[600px] h-[600px] bg-secondary/20 rounded-full blur-[120px] pointer-events-none translate-x-1/3 -translate-y-1/3" />
      <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-accent/20 rounded-full blur-[120px] pointer-events-none -translate-x-1/3 translate-y-1/3" />

      <div className="container mx-auto px-4 relative z-10">
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-20"
        >
          <div className="inline-flex items-center justify-center p-4 rounded-3xl bg-white/10 backdrop-blur-md border border-white/20 mb-6 shadow-xl">
            <Trophy className="w-10 h-10 text-secondary" />
          </div>
          <h2 className="font-display text-4xl lg:text-5xl font-extrabold mb-6 text-white tracking-tight">
            Hall of <span className="text-secondary">Fame</span>
          </h2>
          <p className="text-primary-foreground/80 max-w-2xl mx-auto text-lg leading-relaxed font-medium">
            Celebrating academic excellence. Meet the brilliant minds who have set new benchmarks and made our academy proud.
          </p>
        </motion.div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
          {toppers.map((t, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-50px" }}
              transition={{ delay: i * 0.1, duration: 0.5 }}
              className="group relative"
            >
              <div className="absolute inset-0 bg-secondary/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
              <div className="relative bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-8 text-center hover:-translate-y-2 transition-transform duration-300 overflow-hidden shadow-2xl">
                
                {/* Ranking Badge */}
                <div className="absolute top-0 right-6 w-10 h-12 bg-secondary text-secondary-foreground flex flex-col items-center justify-center rounded-b-lg shadow-lg font-bold text-lg border border-secondary/50">
                  <Star className="w-3 h-3 absolute -top-1 fill-white/50 text-white/50" />
                  #{i + 1}
                </div>

                <div className="w-24 h-24 rounded-full bg-gradient-to-br from-white/20 to-white/5 mx-auto mb-6 flex items-center justify-center border-4 border-white/10 shadow-inner group-hover:scale-110 group-hover:border-secondary/50 transition-all duration-300">
                  <span className="font-display font-bold text-4xl text-white">{t.image}</span>
                </div>
                
                <h3 className="font-display font-bold text-2xl mb-1 text-white group-hover:text-secondary transition-colors">{t.name}</h3>
                <p className="inline-block px-4 py-1.5 rounded-full bg-white/10 border border-white/10 text-sm font-medium text-white/90 mb-6 mt-2 tracking-wide backdrop-blur-sm">
                  {t.stream} • Class of {t.year}
                </p>
                
                <div className="pt-6 border-t border-white/10">
                  <p className="text-xs text-white/60 mb-1 uppercase tracking-widest font-semibold">Score Achieved</p>
                  <p className="text-4xl font-display font-extrabold text-secondary drop-shadow-md group-hover:scale-105 transition-transform">{t.score}</p>
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
