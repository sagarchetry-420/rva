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
    <section id="courses" className="py-24">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <h2 className="font-display text-4xl font-bold mb-4 text-foreground">Our Courses</h2>
          <div className="w-16 h-1 bg-primary rounded mx-auto mb-4" />
          <p className="text-muted-foreground max-w-2xl mx-auto">
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
              className="group bg-card border border-border rounded-2xl p-6 hover:shadow-lg hover:border-primary/30 transition-all duration-300"
            >
              <div className="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4 group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                <course.icon className="w-6 h-6" />
              </div>
              <h3 className="font-display font-semibold text-lg mb-2 text-card-foreground">{course.title}</h3>
              <p className="text-sm text-muted-foreground">{course.desc}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
