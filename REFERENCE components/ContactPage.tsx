import React, { useState } from 'react';
import { Mail, Phone, Printer, MapPin, Send, MessageSquarePlus, Clock, Sparkles } from 'lucide-react';
import { SiteConfig } from '../types';

interface ContactPageProps {
  config: SiteConfig;
}

export default function ContactPage({ config }: ContactPageProps) {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    message: ''
  });
  const [submitted, setSubmitted] = useState(false);

  const primaryColor = config.color_primary || '#015BA7';

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.email || !formData.message) {
      alert("Please fill out all required fields.");
      return;
    }
    setSubmitted(true);
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12 animate-fadeIn" id="contact-page">
      {/* Intro Header */}
      <div className="text-center space-y-2">
        <h2 className="text-3xl font-black text-gray-900 tracking-tight">Contact Our School</h2>
        <p className="text-sm text-gray-500 max-w-lg mx-auto">We are here to answer any questions. Reach out directly or send us an online message.</p>
        <div className="w-12 h-1 bg-sky-400 rounded-full mx-auto mt-4" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Contact Info Card */}
        <div className="lg:col-span-4 bg-white p-6 rounded-xl border border-gray-100 shadow-sm space-y-8 h-full">
          <div>
            <h3 className="text-lg font-bold text-gray-950 mb-4 flex items-center gap-1.5">
              <Sparkles size={18} className="text-blue-600" style={{ color: primaryColor }} />
              Office Directory
            </h3>
            <p className="text-xs text-gray-500 leading-relaxed mb-6">
              Our front office is open Monday through Friday during regular school semesters. If calling after hours, please leave a voicemail.
            </p>
          </div>

          <div className="space-y-4">
            <div className="flex gap-3">
              <div className="p-2.5 bg-gray-50 text-gray-600 rounded-lg h-10 w-10 flex items-center justify-center border border-gray-100">
                <MapPin size={18} />
              </div>
              <div>
                <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Mailing Address</span>
                <p className="text-xs font-semibold text-gray-800 leading-relaxed">
                  {config.address || "207 N Johnson St, Kell, IL 62853"}
                </p>
              </div>
            </div>

            <div className="flex gap-3">
              <div className="p-2.5 bg-gray-50 text-gray-600 rounded-lg h-10 w-10 flex items-center justify-center border border-gray-100">
                <Phone size={18} />
              </div>
              <div>
                <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Main Phone</span>
                <p className="text-xs font-semibold text-gray-800">
                  {config.phone || "618-822-6234"}
                </p>
              </div>
            </div>

            <div className="flex gap-3">
              <div className="p-2.5 bg-gray-50 text-gray-600 rounded-lg h-10 w-10 flex items-center justify-center border border-gray-100">
                <Printer size={18} />
              </div>
              <div>
                <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Fax Line</span>
                <p className="text-xs font-semibold text-gray-800">
                  {config.fax || "618-822-6733"}
                </p>
              </div>
            </div>

            <div className="flex gap-3">
              <div className="p-2.5 bg-gray-50 text-gray-600 rounded-lg h-10 w-10 flex items-center justify-center border border-gray-100">
                <Mail size={18} />
              </div>
              <div>
                <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Inquiry Email</span>
                <p className="text-xs font-semibold text-gray-800">
                  {config.email || "contact@kellgradeschool.com"}
                </p>
              </div>
            </div>

            <div className="flex gap-3">
              <div className="p-2.5 bg-gray-50 text-gray-600 rounded-lg h-10 w-10 flex items-center justify-center border border-gray-100">
                <Clock size={18} />
              </div>
              <div>
                <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">School Hours</span>
                <p className="text-xs font-semibold text-gray-800">
                  7:50 AM - 3:10 PM (Regular Day)
                </p>
              </div>
            </div>
          </div>

          <div className="border-t border-gray-100 pt-6">
            <h4 className="text-xs font-bold text-gray-800 mb-2">District Administration</h4>
            <div className="bg-gray-50/50 p-3 rounded-lg border border-gray-100">
              <p className="text-xs font-bold text-gray-800">{config.principal_name || "Terry Milt"}</p>
              <p className="text-[10px] text-gray-500 font-medium">Superintendent / Principal</p>
            </div>
          </div>
        </div>

        {/* Online Feedback Form */}
        <div className="lg:col-span-8 bg-white p-6 sm:p-8 rounded-xl border border-gray-100 shadow-sm h-full flex flex-col justify-between">
          <div>
            <h3 className="text-lg font-bold text-gray-950 mb-2 flex items-center gap-1.5">
              <MessageSquarePlus size={18} className="text-blue-600" style={{ color: primaryColor }} />
              Submit an Inquiry
            </h3>
            <p className="text-xs text-gray-500 mb-6">
              Fill out this form and a school secretary or administrator will follow up with you within 48 business hours.
            </p>
          </div>

          {submitted ? (
            <div className="bg-green-50/80 border border-green-100 text-green-800 p-8 rounded-xl text-center space-y-3 my-auto">
              <h4 className="text-base font-bold">Thank you, your message has been sent!</h4>
              <p className="text-xs text-green-700 leading-relaxed max-w-md mx-auto">
                We appreciate you taking the time to contact us. If your request is urgent, please call our main school office line at {config.phone}.
              </p>
              <button
                onClick={() => { setSubmitted(false); setFormData({ name: '', email: '', phone: '', message: '' }); }}
                className="mt-4 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold shadow-xs"
              >
                Send Another Message
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1">
                  <label className="text-[10px] font-bold text-gray-500 uppercase">Your Name <span className="text-red-500">*</span></label>
                  <input
                    type="text"
                    required
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full px-3 py-2.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter full name"
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-[10px] font-bold text-gray-500 uppercase">Your Email <span className="text-red-500">*</span></label>
                  <input
                    type="email"
                    required
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className="w-full px-3 py-2.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="username@domain.com"
                  />
                </div>
              </div>

              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-500 uppercase">Phone Number</label>
                <input
                  type="tel"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  className="w-full px-3 py-2.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="(618) 555-0123"
                />
              </div>

              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-500 uppercase">Your Message <span className="text-red-500">*</span></label>
                <textarea
                  required
                  rows={5}
                  value={formData.message}
                  onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                  className="w-full px-3 py-2.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 leading-relaxed"
                  placeholder="How can we help you today?"
                />
              </div>

              <button
                type="submit"
                className="w-full py-3 text-white rounded-lg text-xs font-bold transition-opacity hover:opacity-90 flex items-center justify-center gap-2 cursor-pointer shadow-sm mt-2"
                style={{ backgroundColor: primaryColor }}
              >
                <Send size={14} /> Send Message Inquiry
              </button>
            </form>
          )}
        </div>
      </div>

      {/* Embedded High Fidelity Static Map representing Rural Southern Illinois location */}
      <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden p-4">
        <h4 className="text-sm font-bold text-gray-800 mb-3 flex items-center gap-1.5">
          <MapPin size={16} className="text-blue-500" />
          School Campus Location
        </h4>
        <div className="w-full h-80 rounded-lg bg-gray-100 overflow-hidden relative border border-gray-100 flex items-center justify-center">
          {/* A high fidelity representation of a map utilizing public map image layout */}
          <img
            src="https://images.unsplash.com/photo-1524661135-423995f22d0b?q=80&w=1200&auto=format&fit=crop"
            alt="Kell Illinois Area Map View"
            className="w-full h-full object-cover opacity-60"
            referrerPolicy="no-referrer"
          />
          <div className="absolute inset-0 bg-blue-900/10" />
          <div className="absolute bg-white p-4 rounded-xl shadow-lg border border-gray-100 max-w-xs text-center flex flex-col items-center gap-2">
            <span className="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center text-white text-base font-bold animate-bounce">📍</span>
            <div>
              <p className="text-xs font-bold text-gray-950">Kell Grade School</p>
              <p className="text-[10px] text-gray-500 leading-tight">207 N Johnson St, Kell, IL 62853</p>
            </div>
            <a 
              href="https://maps.google.com/?q=207+N+Johnson+St,+Kell,+IL+62853" 
              target="_blank" 
              rel="noopener noreferrer"
              className="text-[10px] font-bold text-blue-600 hover:underline"
              style={{ color: primaryColor }}
            >
              Get Directions on Google Maps
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
