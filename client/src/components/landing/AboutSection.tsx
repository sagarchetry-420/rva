import { motion, useScroll, useTransform } from "framer-motion";
import { CheckCircle, Quote } from "lucide-react";
import { useRef } from "react";

const highlights = [
  "State-of-the-art science and computer labs",
  "Experienced and dedicated faculty members",
  "Holistic development — academics, sports & arts",
  "Safe and nurturing campus environment",
  "Strong alumni network across the globe",
  "Consistent top results in board examinations",
];

export default function AboutSection() {
  const imageRef = useRef<HTMLDivElement>(null);
  const { scrollYProgress } = useScroll({
    target: imageRef,
    offset: ["start end", "end start"],
  });
  const imageY = useTransform(scrollYProgress, [0, 1], ["-10%", "10%"]);

  return (
    <section id="about" className="py-20 relative overflow-hidden bg-white text-slate-900">
      <motion.div
        animate={{ scale: [1, 1.05, 1] }}
        transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
        className="absolute top-10 right-20 w-40 h-40 rounded-full bg-slate-100 opacity-60"
      />
      <motion.div
        animate={{ x: [0, -20, 0] }}
        transition={{ duration: 10, repeat: Infinity, ease: "easeInOut" }}
        className="absolute bottom-20 left-10 w-32 h-32 rounded-full bg-slate-50 opacity-60"
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
              Our Legacy
            </div>

            <h2 className="font-sans font-extrabold tracking-tight text-4xl lg:text-5xl mb-6 text-slate-900 leading-tight">
              Shaping the{" "}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-500">Leaders</span>{" "}
              of Tomorrow
            </h2>

            <p className="text-lg text-slate-700 mb-6 leading-relaxed font-medium">
              Rose Valley Academy was founded with a vision to provide quality education that combines academic excellence with moral values. Located in a serene environment, our school has been a beacon of learning for over two decades.
            </p>
            <p className="text-slate-600 mb-8 leading-relaxed">
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
                  className="flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white shadow-sm hover:shadow-md hover:border-orange-200 transition-all"
                >
                  <div className="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center shrink-0 mt-0.5">
                    <CheckCircle className="w-5 h-5 text-orange-600" />
                  </div>
                  <span className="text-slate-700 font-medium pt-1">{h}</span>
                </motion.div>
              ))}
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: 40 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="relative"
          >
            <div ref={imageRef} className="relative aspect-square md:aspect-[4/5] overflow-hidden rounded-xl border border-slate-200 shadow-sm">
              <motion.img
                src="/principal's_image/principal's image1.jpg"
                alt="Principal Bhargav Chetia"
                style={{ y: imageY }}
                className="absolute inset-0 w-full h-[120%] -top-[10%] object-cover"
              />
              <div className="absolute inset-0 bg-black/10" />
            </div>

            <motion.div
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: 0.4, duration: 0.8 }}
              className="absolute -bottom-12 -left-4 md:-left-16 right-4 md:right-8 bg-white border border-slate-200 rounded-xl p-6 sm:p-8 shadow-xl"
            >
              <Quote className="absolute top-6 right-6 w-12 h-12 text-slate-200 rotate-180" />
              <div className="flex items-center gap-4 mb-4">
                <img
                  src="/principal's_image/principal's image1.jpg"
                  alt="Principal Bhargav Chetia"
                  className="w-14 h-14 rounded-full object-cover border-2 border-orange-100"
                />
                <div>
                  <h3 className="font-sans font-bold text-lg text-slate-900">Principal's Message</h3>
                  <p className="text-sm font-medium text-orange-600">Bhargav Chetia</p>
                </div>
              </div>
              <blockquote className="text-slate-600 italic leading-relaxed text-sm sm:text-base relative z-10">
                "Welcome to the Phrontistery of Knowledge and Harmony. Education is not just about subjects learned in school. It is a life-long exercise that can be exceedingly exciting if we jump onto the train of experience. We encourage children to ask questions and learn as much as possible. Your child will gain real-world skills to succeed in today's competitive world. Founded in 2008, we've grown from 52 students to 750+ today with 40+ dedicated staff."
              </blockquote>
            </motion.div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
