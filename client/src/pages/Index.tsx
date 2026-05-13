import Navbar from "@/components/landing/Navbar";
import NoticesCarousel from "@/components/landing/NoticesCarousel";
import HeroSection from "@/components/landing/HeroSection";
import NoticesSection from "@/components/landing/NoticesSection";
import AboutSection from "@/components/landing/AboutSection";
import CoursesSection from "@/components/landing/CoursesSection";
import ToppersSection from "@/components/landing/ToppersSection";
import GallerySection from "@/components/landing/GallerySection";
import StaffSection from "@/components/landing/StaffSection";
import RulesSection from "@/components/landing/RulesSection";
import AchievementsSection from "@/components/landing/AchievementsSection";
import ContactSection from "@/components/landing/ContactSection";
import Footer from "@/components/landing/Footer";

const Index = () => {
  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <NoticesCarousel />
      <HeroSection />
      <NoticesSection />
      <AboutSection />
      <CoursesSection />
      <ToppersSection />
      <GallerySection />
      <StaffSection />
      <RulesSection />
      <AchievementsSection />
      <ContactSection />
      <Footer />
    </div>
  );
};

export default Index;
