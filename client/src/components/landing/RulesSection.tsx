import { motion } from "framer-motion";
import { Users, BookOpen } from "lucide-react";

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
    <section id="rules" className="py-20 relative overflow-hidden bg-white text-slate-900">
      <motion.div
        animate={{ scale: [1, 1.08, 1] }}
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
          initial={{ opacity: 0, y: -20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.8 }}
          className="text-center mb-16"
        >
          <div className="inline-flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-6">
            Discipline
          </div>

          <h2 className="font-sans font-extrabold tracking-tight text-4xl lg:text-5xl mb-4 text-slate-900 leading-tight">
            Our Code of{" "}
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500">
              Conduct
            </span>
          </h2>
          <p className="text-lg text-slate-600 max-w-2xl mx-auto">
            We believe in maintaining a disciplined and nurturing environment for all our students. Below are our expectations from students and their guardians.
          </p>
        </motion.div>

        <div className="grid lg:grid-cols-2 gap-12 lg:gap-16">
          <motion.div
            initial={{ opacity: 0, x: -40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="group relative bg-white rounded-xl p-6 border border-slate-200 shadow-sm hover:shadow-xl hover:border-orange-200 transition-all"
          >
            <div className="flex items-center gap-3 mb-8">
              <div className="absolute top-0 left-0 h-[2px] w-0 bg-gradient-to-r from-orange-400 to-amber-500 group-hover:w-full transition-all duration-500" />
              <div className="w-11 h-11 rounded-xl flex items-center justify-center bg-orange-100 text-orange-600">
                <BookOpen className="w-5 h-5" />
              </div>
              <h3 className="text-xl font-sans font-bold text-slate-900">Our Demands from Valleites</h3>
            </div>

            <div className="space-y-3">
              {studentDemands.map((demand, i) => (
                <motion.div
                  key={i}
                  initial={{ opacity: 0, y: 10 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: i * 0.08 }}
                  className="flex gap-4 p-4 rounded-xl border border-slate-200 bg-white transition-all duration-300 hover:border-orange-200 hover:bg-orange-50/30"
                >
                  <div className="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5 bg-orange-100">
                    <span className="w-1.5 h-1.5 rounded-full bg-orange-500" />
                  </div>
                  <p className="text-slate-700 font-medium leading-relaxed text-[15px]">{demand}</p>
                </motion.div>
              ))}
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: 40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="group relative bg-white rounded-xl p-6 border border-slate-200 shadow-sm hover:shadow-xl hover:border-orange-200 transition-all"
          >
            <div className="flex items-center gap-3 mb-8">
              <div className="absolute top-0 left-0 h-[2px] w-0 bg-gradient-to-r from-orange-400 to-amber-500 group-hover:w-full transition-all duration-500" />
              <div className="w-11 h-11 rounded-xl flex items-center justify-center bg-orange-100 text-orange-600">
                <Users className="w-5 h-5" />
              </div>
              <h3 className="text-xl font-sans font-bold text-slate-900">Our Demands from Guardians</h3>
            </div>

            <div className="space-y-3">
              {guardianDemands.map((demand, i) => (
                <motion.div
                  key={i}
                  initial={{ opacity: 0, y: 10 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: i * 0.08 }}
                  className="flex gap-4 p-4 rounded-xl border border-slate-200 bg-white transition-all duration-300 hover:border-orange-200 hover:bg-orange-50/30"
                >
                  <div className="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5 bg-orange-100">
                    <span className="w-1.5 h-1.5 rounded-full bg-orange-500" />
                  </div>
                  <p className="text-slate-700 font-medium leading-relaxed text-[15px]">{demand}</p>
                </motion.div>
              ))}
            </div>
          </motion.div>
        </div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.4, duration: 0.8 }}
          className="mt-16 p-8 rounded-xl text-center bg-white border border-slate-200 shadow-sm"
        >
          <p className="text-slate-900 font-sans font-bold text-lg mb-2">Together, We Create Excellence</p>
          <p className="text-slate-600 text-sm">
            These rules are designed to ensure a safe, disciplined, and productive learning environment for all students at Rose Valley Academy.
          </p>
        </motion.div>
      </div>
    </section>
  );
}
