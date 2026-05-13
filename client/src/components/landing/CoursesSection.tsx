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
    <section id="courses" className="py-24 relative overflow-hidden bg-gradient-to-b from-amber-50 to-white">
      {/* Decorative shapes */}
      <motion.div 
        animate={{ y: [0, -30, 0] }}
        transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
        className="absolute top-20 right-10 w-40 h-40 rounded-full bg-orange-300 opacity-20"
      />
      <motion.div 
        animate={{ scale: [1, 1.1, 1] }}
        transition={{ duration: 10, repeat: Infinity, ease: "easeInOut" }}
        className="absolute bottom-20 left-20 w-32 h-32 rounded-full bg-teal-200 opacity-15"
      />

      <div className="container mx-auto px-4 relative z-10">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <h2 className="font-display text-4xl font-bold mb-4 text-gray-900">Our Courses</h2>
          <div className="w-16 h-1 bg-gradient-to-r from-teal-500 to-orange-500 rounded mx-auto mb-4" />
          <p className="text-gray-700 max-w-2xl mx-auto">
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
              className="group bg-white border-2 border-amber-100 rounded-2xl p-6 hover:shadow-lg hover:border-teal-300 hover:bg-amber-50 transition-all duration-300"
            >
              <div className="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center mb-4 group-hover:bg-teal-100 group-hover:text-teal-600 transition-colors">
                <course.icon className="w-6 h-6" />
              </div>
              <h3 className="font-display font-semibold text-lg mb-2 text-gray-900">{course.title}</h3>
              <p className="text-sm text-gray-600">{course.desc}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
