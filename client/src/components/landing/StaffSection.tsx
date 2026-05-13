import { motion } from "framer-motion";

const staffMembers = [
  {
    title: "Administrative Coordinator",
    dept: "Administration",
    image: "/faculty/administrative-coordinator.jpg",
  },
  {
    title: "Dean",
    dept: "Administration",
    image: "/faculty/dean.jpg",
  },
  {
    title: "Deputy Dean",
    dept: "Administration",
    image: "/faculty/deputy-dean.jpg",
  },
  {
    title: "Managing Director",
    dept: "Administration",
    image: "/faculty/managing-director.jpg",
  },
];

export default function StaffSection() {
  return (
    <section id="staff" className="py-20 relative overflow-hidden bg-white text-slate-900">
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

      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <div className="inline-flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-6">
            Leadership
          </div>
          <h2 className="font-sans font-extrabold tracking-tight text-4xl lg:text-5xl mb-4 text-slate-900">
            Administrative <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500">Leadership</span>
          </h2>
          <p className="text-slate-600 max-w-2xl mx-auto">
            Meet the core administrative leaders guiding school strategy and operations.
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
              className="group relative bg-white rounded-xl p-6 text-center border border-slate-200 shadow-sm hover:shadow-xl hover:border-orange-200 hover:-translate-y-1 transition-all duration-300 overflow-hidden"
            >
              <div className="absolute top-0 left-0 h-[2px] w-0 bg-gradient-to-r from-orange-400 to-amber-500 group-hover:w-full transition-all duration-500" />
              <div className="w-20 h-20 rounded-full bg-slate-100 mx-auto mb-4 overflow-hidden">
                <img
                  src={s.image}
                  alt={s.title}
                  className="w-full h-full object-cover"
                  loading="lazy"
                />
              </div>
              <h3 className="font-sans font-bold text-slate-900">{s.title}</h3>
              <p className="text-xs text-slate-500 mt-1 uppercase tracking-wide">{s.dept}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
