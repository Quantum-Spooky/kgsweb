import React from 'react';
import { motion } from 'motion/react';
import { SiteConfig } from '../types';

interface HeroProps {
  config: SiteConfig;
}

export default function Hero({ config }: HeroProps) {
  if (!config.show_hero_section) return null;

  const primaryColor = config.color_primary || '#015BA7';
  const secondaryColor = config.color_secondary || '#002366';

  // Use the actual compiled hero image from the assets directory [TASK 20, 57]
  const heroImgUrl = "/assets/img/hero_image.png";

  return (
    <div className="relative overflow-hidden bg-gray-900 h-[380px] sm:h-[460px] flex items-center justify-center text-center" id="hero-banner">
      {/* Background Image with elegant overlay */}
      {config.show_hero_img && (
        <>
          <img
            src={heroImgUrl}
            alt="Kell Grade School Campus"
            className="absolute inset-0 w-full h-full object-cover object-center opacity-45 select-none pointer-events-none"
            referrerPolicy="no-referrer"
          />
          <div 
            className="absolute inset-0 opacity-80 mix-blend-multiply" 
            style={{ backgroundImage: `linear-gradient(to bottom, ${primaryColor}, ${secondaryColor})` }}
          />
        </>
      )}

      {/* Hero Content Overlays */}
      <div className="relative max-w-4xl mx-auto px-4 z-10 text-white flex flex-col items-center gap-3">
        {config.show_hero_title && (
          <motion.h2 
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="text-4xl sm:text-5xl md:text-6xl font-black tracking-tight leading-tight"
          >
            {config.hero_title || "Welcome to Kell Grade School"}
          </motion.h2>
        )}

        {config.show_hero_subtitle && (
          <motion.p 
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.2, duration: 0.6 }}
            className="text-lg sm:text-xl md:text-2xl text-sky-100 font-medium tracking-wide max-w-2xl"
          >
            {config.hero_subtitle || "Home of the Indians"}
          </motion.p>
        )}

        {/* Decorative feather detail matching small school culture */}
        <div className="w-12 h-1 bg-sky-400 rounded-full mt-2" />
      </div>
    </div>
  );
}
