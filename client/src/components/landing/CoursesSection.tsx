import { motion } from "framer-motion";
import { BookOpen, Beaker, Calculator, Globe, Palette, Music, Dumbbell, Code } from "lucide-react";

const courses = [
  { icon: BookOpen, title: "English Literature", desc: "Comprehensive language and literature program" },
  { icon: Calculator, title: "Mathematics", desc: "From basic arithmetic to advanced calculus" },
  { icon: Beaker, title: "Science", desc: "Physics, Chemistry & Biology with lab work" },
  { icon: Globe, title: "Social Studies", desc: "History, Geography & Civic Education" },
  { icon: Code, title: "Computer Science", desc: "Programming, IT & digital literacy" },
  { icon: Palette, title: "Fine Arts", desc: "Drawing, painting & creative expression" },
  { icon: Music, title: "Music & Dance", desc: "Classical and modern performing arts" },
  { icon: Dumbbell, title: "Physical Education", desc: "Sports, yoga & fitness training" },
];

export default function CoursesSection() {
  return (
    <section id="courses" className="py-20 relative overflow-hidden bg-white text-slate-900">
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
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <div className="inline-flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-6">
            Academics
          </div>
          <h2 className="font-sans font-extrabold tracking-tight text-4xl lg:text-5xl mb-4 text-slate-900">
            Our <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500">Courses</span>
          </h2>
          <p className="text-slate-600 max-w-2xl mx-auto">
            A comprehensive curriculum designed to develop well-rounded individuals ready for the future.
          </p>
        </motion.div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {courses.map((course, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.05 }}
              className="group relative bg-white rounded-xl p-5 border border-slate-200 shadow-sm hover:shadow-xl hover:border-orange-200 hover:-translate-y-1 transition-all duration-300 overflow-hidden"
            >
              <div className="absolute top-0 left-0 h-[2px] w-0 bg-gradient-to-r from-orange-400 to-amber-500 group-hover:w-full transition-all duration-500" />
              <div className="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center mb-4 group-hover:bg-orange-100 group-hover:text-orange-600 transition-colors">
                <course.icon className="w-6 h-6" />
              </div>
              <h3 className="font-sans font-bold text-lg mb-2 text-slate-900">{course.title}</h3>
              <p className="text-sm text-slate-600 leading-relaxed">{course.desc}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
