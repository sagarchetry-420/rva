import { motion } from "framer-motion";
import { CheckCircle } from "lucide-react";

const highlights = [
  "State-of-the-art science and computer labs",
  "Experienced and dedicated faculty members",
  "Holistic development — academics, sports & arts",
  "Safe and nurturing campus environment",
  "Strong alumni network across the globe",
  "Consistent top results in board examinations",
];

export default function AboutSection() {
  return (
    <section id="about" className="py-24 bg-muted/30">
      <div className="container mx-auto px-4">
        <div className="grid lg:grid-cols-2 gap-16 items-center">
          <motion.div
            initial={{ opacity: 0, x: -30 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <h2 className="font-display text-4xl font-bold mb-4 text-foreground">About Our School</h2>
            <div className="w-16 h-1 bg-primary rounded mb-6" />
            <p className="text-muted-foreground mb-6 leading-relaxed">
              Rose Valley Academy was founded with a vision to provide quality education that combines academic excellence with moral values. Located in a serene environment, our school has been a beacon of learning for over two decades.
            </p>
            <p className="text-muted-foreground mb-8 leading-relaxed">
              We believe every child is unique and deserves an education that nurtures their individual talents while preparing them for the challenges of the future.
            </p>
            <div className="grid sm:grid-cols-2 gap-3">
              {highlights.map((h, i) => (
                <div key={i} className="flex items-start gap-2">
                  <CheckCircle className="w-5 h-5 text-accent mt-0.5 shrink-0" />
                  <span className="text-sm text-foreground">{h}</span>
                </div>
              ))}
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: 30 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
            className="relative"
          >
            {/* Principal's Message Card */}
            <div className="bg-card border border-border rounded-2xl p-8 shadow-xl">
              <div className="flex items-center gap-4 mb-6">
                <div className="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                  <span className="font-display font-bold text-2xl text-primary">P</span>
                </div>
                <div>
                  <h3 className="font-display font-bold text-lg text-card-foreground">Principal's Message</h3>
                  <p className="text-sm text-muted-foreground">Dr. Rajesh Kumar</p>
                </div>
              </div>
              <blockquote className="text-muted-foreground italic leading-relaxed border-l-4 border-primary pl-4">
                "At Rose Valley Academy, we don't just educate minds — we shape futures. Our commitment is to provide each student with the tools, guidance, and inspiration they need to become responsible global citizens and leaders of tomorrow."
              </blockquote>
            </div>
            <div className="absolute -bottom-4 -right-4 w-32 h-32 bg-secondary/30 rounded-2xl -z-10" />
            <div className="absolute -top-4 -left-4 w-24 h-24 bg-accent/20 rounded-2xl -z-10" />
          </motion.div>
        </div>
      </div>
    </section>
  );
}
