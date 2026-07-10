import React, { useState } from 'react';
import { Menu, X, ChevronDown, GraduationCap, Calendar, Users, ShieldAlert, Settings, LogIn, MapPin, Phone } from 'lucide-react';
import { MenuItem, SiteConfig } from '../types';

interface HeaderProps {
  config: SiteConfig;
  menuItems: MenuItem[];
  onNavigate: (page: string, subpage?: string) => void;
  currentPage: string;
  currentSubpage?: string;
  onOpenAdmin: () => void;
}

export default function Header({ config, menuItems, onNavigate, currentPage, currentSubpage, onOpenAdmin }: HeaderProps) {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [expandedMobileParent, setExpandedMobileParent] = useState<string | null>(null);

  const primaryColor = config.color_primary || '#015BA7';
  const secondaryColor = config.color_secondary || '#002366';

  return (
    <header className="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-sm" id="main-header">
      {/* Top Banner (Address & Quick Info) */}
      <div 
        className="text-xs text-white py-1 px-4 hidden md:flex justify-between items-center"
        style={{ backgroundColor: secondaryColor }}
      >
        <div className="flex items-center gap-4">
          <span className="flex items-center gap-1">
            <MapPin size={12} /> {config.address}
          </span>
          <span className="flex items-center gap-1">
            <Phone size={12} /> {config.phone}
          </span>
        </div>
        <div className="flex items-center gap-4">
          <a 
            href={config.teacherease_url} 
            target="_blank" 
            rel="noopener noreferrer" 
            className="hover:underline flex items-center gap-1 font-medium text-sky-200"
          >
            <LogIn size={12} /> TeacherEase Login
          </a>
          <button 
            onClick={onOpenAdmin} 
            className="hover:underline flex items-center gap-1 font-medium text-sky-100"
          >
            <Settings size={12} /> Admin Portal
          </button>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-20">
          {/* Logo and Identity */}
          <div 
            className="flex items-center cursor-pointer select-none gap-3" 
            onClick={() => {
              onNavigate('home');
              setMobileMenuOpen(false);
            }}
          >
            {/* Visual Emblem / School Indian Head Logo */}
            <div className="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center bg-gray-50 border border-gray-100 shadow-sm relative group">
              <img 
                src="/assets/img/indian_head.png" 
                alt="Kell Grade School Logo" 
                className="w-full h-full object-contain p-0.5"
                onError={(e) => {
                  e.currentTarget.style.display = 'none';
                  const fb = document.getElementById('emblem-fallback');
                  if (fb) fb.className = 'absolute inset-0 flex items-center justify-center text-white font-black text-xl';
                }}
              />
              <div 
                id="emblem-fallback" 
                className="absolute inset-0 hidden items-center justify-center text-white font-black text-xl"
                style={{ backgroundColor: primaryColor }}
              >
                K
              </div>
            </div>
            <div>
              <h1 className="text-lg font-bold tracking-tight text-gray-900 leading-tight">
                {config.site_name}
              </h1>
              <p className="text-xs text-gray-500 hidden sm:block">
                {config.district_name}
              </p>
            </div>
          </div>

          {/* Desktop Navigation */}
          <nav className="hidden lg:flex items-center space-x-1" id="desktop-nav">
            <button
              onClick={() => onNavigate('home')}
              className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                currentPage === 'home' 
                  ? 'bg-sky-50 text-blue-600' 
                  : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'
              }`}
              style={currentPage === 'home' ? { color: primaryColor, backgroundColor: `${primaryColor}10` } : {}}
            >
              Home
            </button>

            {menuItems.map((item) => {
              const hasSubItems = item.items && item.items.length > 0;
              const isActive = currentPage === item.slug;

              if (!item.show) return null;

              return (
                <div key={item.slug} className="relative group">
                  <button
                    onClick={() => {
                      if (!hasSubItems) onNavigate(item.slug);
                    }}
                    className={`px-4 py-2 text-sm font-medium rounded-md transition-colors flex items-center gap-1 cursor-pointer ${
                      isActive 
                        ? 'bg-sky-50 text-blue-600' 
                        : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'
                    }`}
                    style={isActive ? { color: primaryColor, backgroundColor: `${primaryColor}10` } : {}}
                  >
                    {item.label}
                    {hasSubItems && <ChevronDown size={14} className="opacity-70 group-hover:rotate-180 transition-transform duration-250" />}
                  </button>

                  {/* Dropdown Menu */}
                  {hasSubItems && (
                    <div className="absolute left-0 mt-1 w-56 rounded-md shadow-lg bg-white border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                      <div className="py-1">
                        {item.items?.map((sub) => {
                          const isSubActive = currentPage === item.slug && currentSubpage === sub.slug;
                          return (
                            <button
                              key={sub.slug}
                              onClick={() => onNavigate(item.slug, sub.slug)}
                              className={`w-full text-left px-4 py-2.5 text-sm transition-colors ${
                                isSubActive 
                                  ? 'bg-blue-50 text-blue-700 font-medium' 
                                  : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600'
                              }`}
                              style={isSubActive ? { color: primaryColor, backgroundColor: `${primaryColor}10` } : {}}
                            >
                              {sub.label}
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  )}
                </div>
              );
            })}

            <button
              onClick={() => onNavigate('contact')}
              className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                currentPage === 'contact' 
                  ? 'bg-sky-50 text-blue-600' 
                  : 'text-gray-700 hover:text-blue-600 hover:bg-gray-50'
              }`}
              style={currentPage === 'contact' ? { color: primaryColor, backgroundColor: `${primaryColor}10` } : {}}
            >
              Contact
            </button>
          </nav>

          {/* Mobile Menu Button */}
          <div className="flex items-center lg:hidden gap-2">
            <button
              onClick={onOpenAdmin}
              className="p-2 text-gray-500 hover:text-blue-600 hover:bg-gray-50 rounded-md"
              title="Admin Portal"
            >
              <Settings size={20} />
            </button>
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="p-2 rounded-md text-gray-700 hover:bg-gray-100"
              id="mobile-menu-toggle"
            >
              {mobileMenuOpen ? <X size={24} /> : <Menu size={24} />}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Drawer */}
      {mobileMenuOpen && (
        <div className="lg:hidden bg-white border-t border-gray-100 absolute w-full left-0 shadow-lg z-50 max-h-[85vh] overflow-y-auto pb-6">
          <div className="px-2 pt-2 pb-3 space-y-1">
            <button
              onClick={() => {
                onNavigate('home');
                setMobileMenuOpen(false);
              }}
              className="w-full text-left block px-3 py-3 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600"
            >
              Home
            </button>

            {menuItems.map((item) => {
              const hasSubItems = item.items && item.items.length > 0;
              const isExpanded = expandedMobileParent === item.slug;

              if (!item.show) return null;

              return (
                <div key={item.slug} className="space-y-1">
                  <button
                    onClick={() => {
                      if (hasSubItems) {
                        setExpandedMobileParent(isExpanded ? null : item.slug);
                      } else {
                        onNavigate(item.slug);
                        setMobileMenuOpen(false);
                      }
                    }}
                    className="w-full flex justify-between items-center px-3 py-3 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600"
                  >
                    <span>{item.label}</span>
                    {hasSubItems && <ChevronDown size={18} className={`transform transition-transform ${isExpanded ? 'rotate-180' : ''}`} />}
                  </button>

                  {hasSubItems && isExpanded && (
                    <div className="pl-6 space-y-1 border-l-2 border-gray-100 ml-4">
                      {item.items?.map((sub) => (
                        <button
                          key={sub.slug}
                          onClick={() => {
                            onNavigate(item.slug, sub.slug);
                            setMobileMenuOpen(false);
                          }}
                          className="w-full text-left block px-3 py-2 rounded-md text-sm text-gray-600 hover:bg-gray-50 hover:text-blue-600"
                        >
                          {sub.label}
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              );
            })}

            <button
              onClick={() => {
                onNavigate('contact');
                setMobileMenuOpen(false);
              }}
              className="w-full text-left block px-3 py-3 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600"
            >
              Contact
            </button>

            <a
              href={config.teacherease_url}
              target="_blank"
              rel="noopener noreferrer"
              className="w-full text-left block px-3 py-3 rounded-md text-base font-medium text-sky-700 hover:bg-sky-50 flex items-center gap-2"
            >
              <LogIn size={18} /> TeacherEase Login
            </a>
          </div>
        </div>
      )}
    </header>
  );
}
