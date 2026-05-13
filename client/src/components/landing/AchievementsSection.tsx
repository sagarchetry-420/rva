import { motion } from "framer-motion";
import { Award, TrendingUp } from "lucide-react";

const examResults = [
  { year: 2014, board: "Class 10", passPercentage: "100%" },
  { year: 2015, board: "Class 10", passPercentage: "100%" },
  { year: 2016, board: "Class 10", passPercentage: "100%" },
  { year: 2017, board: "Class 10", passPercentage: "100%" },
  { year: 2018, board: "Class 10", passPercentage: "100%" },
  { year: 2019, board: "Class 10", passPercentage: "100%" },
  { year: 2020, board: "Class 10", passPercentage: "100%" },
  { year: 2021, board: "Class 10", passPercentage: "100%" },
  { year: 2022, board: "Class 10", passPercentage: "100%" },
  { year: 2023, board: "Class 10", passPercentage: "100%" },
  { year: 2024, board: "Class 10, 12", passPercentage: "100%", note: "* one student appeared HSLC exam in the next academic year." },
  { year: 2025, board: "Class 10, 12", passPercentage: "100%" },
];

export default function AchievementsSection() {
  return (
    <section id="achievements" className="py-24 relative overflow-hidden bg-muted/20">
      {/* Decorative gradients */}
      <div className="absolute top-0 right-0 w-[40vw] h-[40vw] bg-primary/5 rounded-full blur-[100px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[40vw] h-[40vw] bg-accent/5 rounded-full blur-[100px] pointer-events-none" />

      <div className="container mx-auto px-4 relative z-10">
        {/* Header */}
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.8 }}
          className="text-center mb-16"
        >
          <h2 className="font-display text-4xl lg:text-5xl font-extrabold mb-4 text-foreground leading-tight">
            Proof of Domination in the <span className="text-transparent bg-clip-text" style={{ backgroundImage: 'linear-gradient(to right, rgb(247, 150, 70), rgb(247, 150, 70))' }}>Education Sphere</span>
          </h2>
          <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
            A decade of excellence with consistent 100% pass rates in HSLC & HSSLC exams
          </p>
        </motion.div>

        {/* Stats Cards */}
        <div className="grid md:grid-cols-3 gap-8 mb-16">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6, delay: 0 }}
            className="p-8 rounded-2xl transition-all"
            style={{
              borderColor: "rgb(247,150,70,0.2)",
              backgroundColor: "rgb(247,150,70,0.05)"
            }}
            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = "rgb(247,150,70,0.1)"}
            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = "rgb(247,150,70,0.05)"}
          >
            <div className="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style={{ backgroundColor: "rgb(247,150,70,0.2)" }}>
              <Award className="w-6 h-6" style={{ color: "rgb(247,150,70)" }} />
            </div>
            <p className="text-4xl font-extrabold text-foreground mb-2">100%</p>
            <p className="text-muted-foreground font-medium">Pass Rate Consistently</p>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="p-8 rounded-2xl transition-all"
            style={{
              borderColor: "rgb(247,150,70,0.2)",
              backgroundColor: "rgb(247,150,70,0.05)"
            }}
            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = "rgb(247,150,70,0.1)"}
            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = "rgb(247,150,70,0.05)"}
          >
            <div className="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style={{ backgroundColor: "rgb(247,150,70,0.2)" }}>
              <TrendingUp className="w-6 h-6" style={{ color: "rgb(247,150,70)" }} />
            </div>
            <p className="text-4xl font-extrabold text-foreground mb-2">12 Years</p>
            <p className="text-muted-foreground font-medium">Of Excellence (2014-2025)</p>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6, delay: 0.2 }}
            className="p-8 rounded-2xl transition-all"
            style={{
              borderColor: "rgb(247,150,70,0.2)",
              backgroundColor: "rgb(247,150,70,0.05)"
            }}
            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = "rgb(247,150,70,0.1)"}
            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = "rgb(247,150,70,0.05)"}
          >
            <div className="w-12 h-12 rounded-xl flex items-center justify-center mb-4" style={{ backgroundColor: "rgb(247,150,70,0.2)" }}>
              <Award className="w-6 h-6" style={{ color: "rgb(247,150,70)" }} />
            </div>
            <p className="text-4xl font-extrabold text-foreground mb-2">Zero</p>
            <p className="text-muted-foreground font-medium">Failures in All Years</p>
          </motion.div>
        </div>

        {/* Results Table */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.8 }}
          className="overflow-x-auto rounded-2xl border border-border/50 bg-background/50 backdrop-blur"
        >
          <table className="w-full">
            <thead>
              <tr className="border-b border-border/50 bg-muted/50">
                <th className="px-6 py-4 text-left text-sm font-bold text-foreground">Academic Year</th>
                <th className="px-6 py-4 text-left text-sm font-bold text-foreground">Board / Class</th>
                <th className="px-6 py-4 text-left text-sm font-bold text-foreground">Pass Percentage</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/30">
              {examResults.map((result, i) => (
                <motion.tr
                  key={i}
                  initial={{ opacity: 0, x: -10 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.3, delay: i * 0.05 }}
                  className="hover:bg-muted/50 transition-colors"
                >
                  <td className="px-6 py-4 text-foreground font-semibold">{result.year}</td>
                  <td className="px-6 py-4 text-muted-foreground">{result.board}</td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-2">
                      <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-700 border border-green-200">
                        {result.passPercentage}
                      </span>
                    </div>
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
        </motion.div>

        {/* Footer Note */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.4, duration: 0.8 }}
          className="mt-8 p-4 rounded-xl bg-muted/50 border border-border/30 text-center"
        >
          <p className="text-sm text-muted-foreground">
            <span className="font-semibold">*</span> One student appeared HSLC exam in the next academic year.
          </p>
        </motion.div>
      </div>
    </section>
  );
}
