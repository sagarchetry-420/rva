import { motion } from "framer-motion";
import { Trophy } from "lucide-react";

const toppers = [
  { name: "Ananya Sharma", score: "98.6%", year: "2025", stream: "Science" },
  { name: "Rahul Verma", score: "97.8%", year: "2025", stream: "Commerce" },
  { name: "Priya Patel", score: "97.2%", year: "2025", stream: "Arts" },
  { name: "Arjun Singh", score: "96.9%", year: "2024", stream: "Science" },
  { name: "Meera Nair", score: "96.5%", year: "2024", stream: "Commerce" },
  { name: "Kavya Reddy", score: "96.1%", year: "2024", stream: "Science" },
];

export default function ToppersSection() {
  return (
    <section id="toppers" className="py-24 bg-primary text-primary-foreground">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <Trophy className="w-12 h-12 mx-auto mb-4 text-secondary" />
          <h2 className="font-display text-4xl font-bold mb-4">Our Toppers</h2>
          <div className="w-16 h-1 bg-secondary rounded mx-auto mb-4" />
          <p className="opacity-80 max-w-2xl mx-auto">
            Celebrating academic excellence — our students consistently achieve top scores.
          </p>
        </motion.div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {toppers.map((t, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, scale: 0.95 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.05 }}
              className="bg-primary-foreground/10 backdrop-blur border border-primary-foreground/20 rounded-2xl p-6 text-center"
            >
              <div className="w-16 h-16 rounded-full bg-secondary text-secondary-foreground flex items-center justify-center mx-auto mb-4 font-display font-bold text-xl">
                #{i + 1}
              </div>
              <h3 className="font-display font-bold text-lg mb-1">{t.name}</h3>
              <p className="text-3xl font-display font-extrabold text-secondary mb-2">{t.score}</p>
              <p className="text-sm opacity-70">{t.stream} • {t.year}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
