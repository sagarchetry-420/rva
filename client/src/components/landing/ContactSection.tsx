import { motion } from "framer-motion";
import { MapPin, Phone, Mail, Clock } from "lucide-react";

export default function ContactSection() {
  return (
    <section id="contact" className="py-24 relative overflow-hidden bg-gradient-to-b from-white to-amber-50">
      {/* Decorative shapes */}
      <motion.div 
        animate={{ rotate: 360 }}
        transition={{ duration: 40, repeat: Infinity, ease: "linear" }}
        className="absolute top-20 left-10 w-40 h-40 rounded-full bg-teal-200 opacity-15"
      />

      <div className="container mx-auto px-4 relative z-10">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-16"
        >
          <h2 className="font-display text-4xl font-bold mb-4 text-gray-900">Contact & Location</h2>
          <div className="w-16 h-1 bg-gradient-to-r from-teal-500 to-orange-500 rounded mx-auto mb-4" />
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
              <div key={i} className="flex items-start gap-4 bg-white border-2 border-amber-100 rounded-xl p-5 hover:border-teal-300 hover:bg-amber-50 transition-all">
                <div className="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center shrink-0">
                  <item.icon className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-600">{item.label}</p>
                  <p className="text-gray-900 font-medium">{item.value}</p>
                </div>
              </div>
            ))}
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: 20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            className="bg-muted rounded-2xl overflow-hidden min-h-[300px] flex items-center justify-center border border-border"
          >
            <div className="text-center p-8">
              <MapPin className="w-12 h-12 text-muted-foreground/40 mx-auto mb-4" />
              <p className="text-muted-foreground">Map placeholder — Rose Valley Academy</p>
              <p className="text-sm text-muted-foreground/60">Bamunbari, 786613</p>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
