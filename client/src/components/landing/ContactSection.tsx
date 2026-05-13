import { motion } from "framer-motion";
import { MapPin, Phone, Mail, Clock } from "lucide-react";

export default function ContactSection() {
  return (
    <section id="contact" className="py-20 relative overflow-hidden bg-white text-slate-900">
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
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <div className="inline-flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-6">
            Get in Touch
          </div>
          <h2 className="font-sans font-extrabold tracking-tight text-4xl lg:text-5xl mb-4 text-slate-900">
            Contact <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500">&amp; Location</span>
          </h2>
        </motion.div>

        <div className="grid lg:grid-cols-2 gap-12">
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            className="space-y-6"
          >
            {[
              { icon: MapPin, label: "Address", value: "Rose Valley Academy, Bamunbari — 786613" },
              { icon: Phone, label: "Phone", value: "+91 xxxxx xxxxx" },
              { icon: Mail, label: "Email", value: "info@xxx.edu" },
              { icon: Clock, label: "Office Hours", value: "Mon – Sat: 8:00 AM – 4:00 PM" },
            ].map((item, i) => (
              <div key={i} className="flex items-start gap-4 bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-orange-200 transition-all">
                <div className="w-10 h-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                  <item.icon className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm font-medium text-slate-500">{item.label}</p>
                  <p className="text-slate-900 font-medium">{item.value}</p>
                </div>
              </div>
            ))}
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: 20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            className="rounded-xl overflow-hidden min-h-[320px] border border-slate-200 bg-white shadow-sm"
          >
            <iframe
              title="Rose Valley Academy Location"
              src="https://www.google.com/maps?q=Rose+Valley+Academy+Bamunbari+786613&output=embed"
              className="w-full h-full min-h-[320px]"
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
            />
          </motion.div>
        </div>
      </div>
    </section>
  );
}
