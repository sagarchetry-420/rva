import { motion } from "framer-motion";
import { CheckCircle, Quote } from "lucide-react";

const highlights = [
  "State-of-the-art science and computer labs",
  "Experienced and dedicated faculty members",
  "Holistic development — academics, sports & arts",
  "Safe and nurturing campus environment",
  "Strong alumni network across the globe",
  "Consistent top results in board examinations",
];

export default function AboutSection() {
  return (
    <section id="about" className="py-24 relative overflow-hidden bg-white">
      {/* Decorative shapes */}
      <motion.div 
        animate={{ scale: [1, 1.05, 1] }}
        transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
        className="absolute top-10 right-20 w-40 h-40 rounded-full bg-yellow-300 opacity-20"
      />
      <motion.div 
        animate={{ rotate: 360 }}
        transition={{ duration: 30, repeat: Infinity, ease: "linear" }}
        className="absolute bottom-20 left-10 w-32 h-32 rounded-full bg-teal-300 opacity-15"
      />

      <div className="container mx-auto px-4 relative z-10">
        <div className="grid lg:grid-cols-2 gap-16 lg:gap-24 items-center">
          
          <motion.div
            initial={{ opacity: 0, x: -40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="flex flex-col"
          >
            <div className="inline-flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-sm font-semibold mb-6 self-start">
              <span className="w-2 h-2 rounded-full bg-orange-500"></span>
              Our Legacy
            </div>
            
            <h2 className="font-display text-4xl lg:text-5xl font-extrabold mb-6 text-gray-900 leading-tight">
              Shaping the <span className="text-transparent bg-clip-text bg-gradient-to-r from-teal-500 to-orange-500">Leaders</span> of Tomorrow
            </h2>
            
            <p className="text-lg text-gray-700 mb-6 leading-relaxed font-medium">
              Rose Valley Academy was founded with a vision to provide quality education that combines academic excellence with moral values. Located in a serene environment, our school has been a beacon of learning for over two decades.
            </p>
            <p className="text-gray-600 mb-8 leading-relaxed">
              We believe every child is unique and deserves an education that nurtures their individual talents while preparing them for the challenges of the future.
            </p>
            
            <div className="grid sm:grid-cols-1 gap-4">
              {highlights.map((h, i) => (
                <motion.div 
                  key={i} 
                  initial={{ opacity: 0, y: 10 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: i * 0.1 }}
                  className="flex items-start gap-4 p-3 rounded-2xl hover:bg-amber-50 transition-colors"
                >
                  <div className="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center shrink-0 mt-0.5">
                    <CheckCircle className="w-5 h-5 text-teal-600" />
                  </div>
                  <span className="text-gray-900 font-medium pt-1">{h}</span>
                </motion.div>
              ))}
            </div>
          </motion.div>

          {/* Right Side - Visuals & Message */}
          <motion.div
            initial={{ opacity: 0, x: 40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="relative"
          >
            {/* Main Image Placeholder (Styled well) */}
            <div className="relative aspect-square md:aspect-[4/5] rounded-[2.5rem] overflow-hidden shadow-2xl">
              <img
                src="/school_image/school%20image1.jpg"
                alt="Rose Valley Academy Campus"
                className="w-full h-full object-cover"
              />
              <div className="absolute inset-0 bg-primary/10 mix-blend-multiply" />
            </div>

            {/* Principal's Message Floating Card */}
            <motion.div 
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: 0.4, duration: 0.8 }}
              className="absolute -bottom-12 -left-4 md:-left-16 right-4 md:right-8 bg-white/90 backdrop-blur-xl border border-amber-200/50 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-black/10"
            >
              <Quote className="absolute top-6 right-6 w-12 h-12 text-primary/10 rotate-180" />
              <div className="flex items-center gap-4 mb-4">
                <img
                  src="/principal's_image/principal's image1.jpg"
                  alt="Principal Bhargav Chetia"
                  className="w-14 h-14 rounded-full object-cover border-2 border-primary/20 shadow-inner"
                />
                <div>
                  <h3 className="font-display font-bold text-lg text-foreground">Principal's Message</h3>
                  <p className="text-sm font-medium text-primary">Bhargav Chetia</p>
                </div>
              </div>
              <blockquote className="text-muted-foreground italic leading-relaxed text-sm sm:text-base relative z-10">
                "Welcome to the Phrontistery of Knowledge and Harmony. Education is not just about subjects learned in school. It is a life-long exercise that can be exceedingly exciting if we jump onto the train of experience. We encourage children to ask questions and learn as much as possible. Your child will gain real-world skills to succeed in today's competitive world. Founded in 2008, we've grown from 52 students to 750+ today with 40+ dedicated staff."
              </blockquote>
            </motion.div>
            
          </motion.div>
        </div>
      </div>
    </section>
  );
}
