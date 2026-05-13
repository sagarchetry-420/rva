import { motion } from "framer-motion";
import { Shield, Users, BookOpen } from "lucide-react";

const studentDemands = [
  "Regular attendance in the classes, at least 85% of total class days.",
  "Wearing proper uniform is mandatory.",
  "No stylish hair-cuts are allowed.",
  "Ear studs are totally banned for boys.",
  "No electrical gadgets such as mobiles, music players, cameras are allowed.",
  "Any indiscipline inside the school campus will not be tolerated.",
];

const guardianDemands = [
  "Students must be provided with the required apparatus like pen, pencil etc.",
  "No polythene wrapped food items will be allowed to bring as tiffin.",
  "Kindly do not buy any chips/kurkure/cake etc. plastic packeted food items for your child.",
  "Don't enter inside the campus without taking prior permission from the school authority.",
  "Any problem faced by the students should be brought to the notice of the Principal.",
];

export default function RulesSection() {
  return (
    <section id="rules" className="py-28 relative overflow-hidden">
      {/* Dot pattern */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          backgroundImage: "radial-gradient(circle at 1px 1px, rgba(0,0,0,0.02) 1px, transparent 1px)",
          backgroundSize: "28px 28px",
        }}
      />

      {/* Ambient glow */}
      <div
        className="absolute top-0 left-0 w-[40vw] h-[40vw] rounded-full pointer-events-none"
        style={{ background: "radial-gradient(circle, rgba(217,119,6,0.03) 0%, transparent 60%)" }}
      />

      <div className="container mx-auto px-4 relative z-10">
        {/* Header */}
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.8 }}
          className="text-center mb-16"
        >
          {/* Editorial eyebrow */}
          <div className="flex items-center justify-center gap-3 mb-5">
            <div className="w-8 h-[1px]" style={{ background: "hsl(38,92%,50%)" }} />
            <span
              className="text-xs font-semibold tracking-[0.25em] uppercase"
              style={{ color: "hsl(38,92%,50%)" }}
            >
              Discipline
            </span>
            <div className="w-8 h-[1px]" style={{ background: "hsl(38,92%,50%)" }} />
          </div>

          <h2 className="font-display text-4xl lg:text-5xl font-bold mb-4 text-foreground leading-tight">
            Our Code of{" "}
            <span
              className="italic"
              style={{
                background: "linear-gradient(135deg, hsl(38,92%,50%), hsl(32,95%,42%))",
                WebkitBackgroundClip: "text",
                WebkitTextFillColor: "transparent",
              }}
            >
              Conduct
            </span>
          </h2>
          <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
            We believe in maintaining a disciplined and nurturing environment for all our students. Below are our expectations from students and their guardians.
          </p>
        </motion.div>

        {/* Rules Grid */}
        <div className="grid lg:grid-cols-2 gap-12 lg:gap-16">
          {/* Student Demands */}
          <motion.div
            initial={{ opacity: 0, x: -40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
          >
            <div className="flex items-center gap-3 mb-8">
              <div
                className="w-11 h-11 rounded-xl flex items-center justify-center"
                style={{ background: "rgba(217,119,6,0.08)" }}
              >
                <BookOpen className="w-5 h-5" style={{ color: "hsl(38,92%,50%)" }} />
              </div>
              <h3 className="text-xl font-display font-bold text-foreground">Our Demands from Valleites</h3>
            </div>

            <div className="space-y-3">
              {studentDemands.map((demand, i) => (
                <motion.div
                  key={i}
                  initial={{ opacity: 0, y: 10 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: i * 0.08 }}
                  className="flex gap-4 p-4 rounded-xl transition-all duration-300 group cursor-default"
                  style={{ border: "1px solid hsl(var(--border))" }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.borderColor = "rgba(217,119,6,0.15)";
                    e.currentTarget.style.background = "rgba(217,119,6,0.02)";
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.borderColor = "hsl(var(--border))";
                    e.currentTarget.style.background = "transparent";
                  }}
                >
                  <div
                    className="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5 transition-transform duration-300 group-hover:scale-110"
                    style={{ background: "rgba(217,119,6,0.12)" }}
                  >
                    <span className="w-1.5 h-1.5 rounded-full" style={{ background: "hsl(38,92%,50%)" }} />
                  </div>
                  <p className="text-foreground font-medium leading-relaxed text-[15px]">{demand}</p>
                </motion.div>
              ))}
            </div>
          </motion.div>

          {/* Guardian Demands */}
          <motion.div
            initial={{ opacity: 0, x: 40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
          >
            <div className="flex items-center gap-3 mb-8">
              <div
                className="w-11 h-11 rounded-xl flex items-center justify-center"
                style={{ background: "rgba(217,119,6,0.08)" }}
              >
                <Users className="w-5 h-5" style={{ color: "hsl(38,92%,50%)" }} />
              </div>
              <h3 className="text-xl font-display font-bold text-foreground">Our Demands from Guardians</h3>
            </div>

            <div className="space-y-3">
              {guardianDemands.map((demand, i) => (
                <motion.div
                  key={i}
                  initial={{ opacity: 0, y: 10 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: i * 0.08 }}
                  className="flex gap-4 p-4 rounded-xl transition-all duration-300 group cursor-default"
                  style={{ border: "1px solid hsl(var(--border))" }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.borderColor = "rgba(217,119,6,0.15)";
                    e.currentTarget.style.background = "rgba(217,119,6,0.02)";
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.borderColor = "hsl(var(--border))";
                    e.currentTarget.style.background = "transparent";
                  }}
                >
                  <div
                    className="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5 transition-transform duration-300 group-hover:scale-110"
                    style={{ background: "rgba(217,119,6,0.12)" }}
                  >
                    <span className="w-1.5 h-1.5 rounded-full" style={{ background: "hsl(38,92%,50%)" }} />
                  </div>
                  <p className="text-foreground font-medium leading-relaxed text-[15px]">{demand}</p>
                </motion.div>
              ))}
            </div>
          </motion.div>
        </div>

        {/* Footer message */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.4, duration: 0.8 }}
          className="mt-16 p-8 rounded-2xl text-center"
          style={{
            background: "rgba(217,119,6,0.03)",
            border: "1px solid rgba(217,119,6,0.08)",
          }}
        >
          <p className="text-foreground font-display font-bold text-lg mb-2">Together, We Create Excellence</p>
          <p className="text-muted-foreground text-sm">
            These rules are designed to ensure a safe, disciplined, and productive learning environment for all students at Rose Valley Academy.
          </p>
        </motion.div>
      </div>
    </section>
  );
}
