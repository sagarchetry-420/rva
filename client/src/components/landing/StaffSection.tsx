import { motion } from "framer-motion";

const staffMembers = [
  { name: "Dr. Rajesh Kumar", role: "Principal", dept: "Administration" },
  { name: "Mrs. Sunita Devi", role: "Vice Principal", dept: "Academics" },
  { name: "Mr. Amit Gupta", role: "Head of Science", dept: "Science" },
  { name: "Ms. Neha Joshi", role: "Head of Mathematics", dept: "Mathematics" },
  { name: "Mr. Vikram Rao", role: "Head of Sports", dept: "Physical Ed." },
  { name: "Mrs. Kavitha Menon", role: "Head of Arts", dept: "Fine Arts" },
  { name: "Mr. Sanjay Das", role: "IT Head", dept: "Computer Science" },
  { name: "Ms. Pooja Mishra", role: "English Faculty", dept: "Languages" },
];

export default function StaffSection() {
  return (
    <section id="staff" className="py-24 bg-muted/30">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <h2 className="font-display text-4xl font-bold mb-4 text-foreground">Our Faculty</h2>
          <div className="w-16 h-1 bg-primary rounded mx-auto mb-4" />
          <p className="text-muted-foreground max-w-2xl mx-auto">
            Meet the dedicated educators shaping the future of our students.
          </p>
        </motion.div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          {staffMembers.map((s, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.05 }}
              className="bg-card border border-border rounded-2xl p-6 text-center hover:shadow-lg transition-shadow"
            >
              <div className="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                <span className="font-display font-bold text-2xl text-primary">
                  {s.name.split(" ").map(n => n[0]).join("").slice(0, 2)}
                </span>
              </div>
              <h3 className="font-display font-semibold text-card-foreground">{s.name}</h3>
              <p className="text-sm font-medium text-primary mt-1">{s.role}</p>
              <p className="text-xs text-muted-foreground mt-1">{s.dept}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
