import React, { useState } from 'react';
import { BookOpen, UserCheck, Paperclip, ExternalLink, ShieldCheck, Sparkles, AlertCircle, Settings2 } from 'lucide-react';
import { SiteConfig } from '../types';

interface TemplatePageProps {
  config: SiteConfig;
  title: string;
  category: 'classroom' | 'team' | 'club';
}

export default function TemplatePage({ config, title, category }: TemplatePageProps) {
  const [showAbout, setShowAbout] = useState(true);
  const [showTeacher, setShowTeacher] = useState(true);
  const [showDocs, setShowDocs] = useState(true);

  const primaryColor = config.color_primary || '#015BA7';

  // Specific content based on category
  const getContent = () => {
    switch (category) {
      case 'classroom':
        return {
          aboutText: `Welcome to the ${title} portal. Our classroom curriculum focuses on building foundational literacy, core mathematical concepts, and physical sciences in a collaborative, supportive environment. We promote a growth mindset and look forward to partnering with parents to ensure student success.`,
          teacherName: `Mrs. Karen Garrison`,
          teacherRole: `${title} Lead Instructor`,
          teacherBio: `Mrs. Garrison has been teaching elementary students in Southern Illinois for over 15 years. She is passionate about child-centered learning and integrated science projects.`,
          docs: [
            { label: `${title} Syllabus & Curriculum Goals`, type: 'pdf' },
            { label: 'Weekly Homework Log & Schedule', type: 'doc' },
            { label: 'Parent-Teacher Communication Guidelines', type: 'pdf' }
          ]
        };
      case 'team':
        return {
          aboutText: `Welcome to the KGS ${title} Team home page. Our athletic programs emphasize physical fitness, teamwork, sportsmanship, and student leadership. We compete in the Southern Illinois PreK-12 Athletic Association. Practice schedules and physical clearance guidelines can be accessed below. Go Indians!`,
          teacherName: `Coach Todd Burroughs`,
          teacherRole: `Head Varsity ${title} Coach`,
          teacherBio: `Coach Burroughs has coached rural school athletics for a decade. He believes in student growth, physical endurance, and positive sportsmanship above all.`,
          docs: [
            { label: `2026 Varsity ${title} Schedule`, type: 'pdf' },
            { label: 'IHSA Athletic Physical Clearance Form', type: 'pdf' },
            { label: 'Athlete Code of Conduct Agreement', type: 'pdf' }
          ]
        };
      case 'club':
        return {
          aboutText: `Welcome to ${title}. Our club is designed to provide extracurricular opportunities for students to explore their interests, learn new skills, and connect with their peers. We meet weekly during activity periods. All interested students are welcome to join.`,
          teacherName: `Mrs. Jeanna Donoho`,
          teacherRole: `Faculty Sponsor & Coordinator`,
          teacherBio: `Mrs. Donoho sponsors multiple student initiatives. She loves encouraging creativity, collaborative puzzle-solving, and community service projects.`,
          docs: [
            { label: `${title} Meeting Schedule & Calendar`, type: 'pdf' },
            { label: 'Student Membership Registration Form', type: 'pdf' },
            { label: 'Activity Permission & Liability Waiver', type: 'pdf' }
          ]
        };
    }
  };

  const data = getContent();

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10 animate-fadeIn" id="template-page">
      {/* Dynamic Title Header */}
      <div className="bg-gray-50 border border-gray-100 p-6 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <span className="text-[10px] bg-blue-100 text-blue-800 font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-full" style={{ color: primaryColor }}>
            {category === 'classroom' ? 'Classroom Directory' : category === 'team' ? 'Sports Team' : 'Extracurricular Club'}
          </span>
          <h2 className="text-2xl sm:text-3xl font-black text-gray-900 mt-1">{title}</h2>
        </div>

        {/* Dynamic Display Toggles (Task 61: individual section show/hide) */}
        <div className="flex flex-wrap items-center gap-2 bg-white p-2 rounded-lg border border-gray-100/80 shadow-2xs">
          <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-2 flex items-center gap-1">
            <Settings2 size={12} /> Toggles
          </span>
          <button 
            onClick={() => setShowAbout(!showAbout)}
            className={`px-2.5 py-1 rounded text-[10px] font-bold transition-all cursor-pointer ${showAbout ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-400'}`}
          >
            About
          </button>
          <button 
            onClick={() => setShowTeacher(!showTeacher)}
            className={`px-2.5 py-1 rounded text-[10px] font-bold transition-all cursor-pointer ${showTeacher ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-400'}`}
          >
            Leader
          </button>
          <button 
            onClick={() => setShowDocs(!showDocs)}
            className={`px-2.5 py-1 rounded text-[10px] font-bold transition-all cursor-pointer ${showDocs ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-400'}`}
          >
            Docs
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Main Content Pane */}
        <div className="lg:col-span-8 space-y-6">
          {/* Section 1: About / Overview Document */}
          {showAbout && (
            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm space-y-3">
              <h3 className="text-base font-bold text-gray-900 border-b border-gray-100 pb-2 flex items-center gap-2">
                <BookOpen size={16} className="text-blue-600" style={{ color: primaryColor }} />
                General Information & Overview
              </h3>
              <p className="text-xs text-gray-600 leading-relaxed font-sans">
                {data.aboutText}
              </p>
            </div>
          )}

          {/* Section 2: Documents & Resource Files */}
          {showDocs && (
            <div className="bg-white p-6 rounded-xl border border-gray-100 shadow-sm space-y-4">
              <h3 className="text-base font-bold text-gray-900 border-b border-gray-100 pb-2 flex items-center gap-2">
                <Paperclip size={16} className="text-blue-600" style={{ color: primaryColor }} />
                Attached Schedules & Documentation
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                {data.docs.map((doc, idx) => (
                  <div 
                    key={idx}
                    onClick={() => alert(`Accessing: "${doc.label}"`)}
                    className="p-3 bg-gray-50/50 rounded-lg border border-gray-100/80 hover:bg-white hover:border-blue-100 hover:shadow-2xs transition-all duration-200 cursor-pointer flex justify-between items-center"
                  >
                    <div className="flex items-center gap-2 min-w-0">
                      <span className="text-[10px] px-1.5 py-0.5 font-bold uppercase tracking-wider rounded-sm bg-blue-100 text-blue-800 shrink-0">
                        {doc.type}
                      </span>
                      <span className="text-xs font-bold text-gray-700 truncate leading-snug">{doc.label}</span>
                    </div>
                    <span className="text-[10px] font-bold text-blue-600 shrink-0">Get</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Setup Guide for Google Sites (Task 66) */}
          <div className="p-5 rounded-xl border border-dashed border-blue-200 bg-blue-50/30 space-y-3">
            <h4 className="text-xs font-bold text-blue-900 uppercase tracking-wide flex items-center gap-1.5">
              <AlertCircle size={14} className="text-blue-700" />
              Staff Google Sites Integration
            </h4>
            <p className="text-[11px] text-blue-800 leading-normal font-sans">
              Are you a teacher, sponsor, or athletic director looking to build a rich Google Site? ContactTerry Milt to get the official <strong>KGS Google Site template</strong>. Once built, copy your public URL, update the "Links" tab in the Google Control Panel, and your Site will embed directly!
            </p>
          </div>
        </div>

        {/* Sponsor/Teacher Sidebar card */}
        <div className="lg:col-span-4 space-y-6">
          {showTeacher && (
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-6 text-center space-y-4">
              <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 border-b border-gray-100 pb-2 flex items-center justify-center gap-1.5">
                <UserCheck size={14} />
                Sponsor Profile
              </h3>
              <div className="flex flex-col items-center gap-3">
                <div 
                  className="w-20 h-20 rounded-full flex items-center justify-center text-white font-black text-2xl"
                  style={{ backgroundColor: primaryColor }}
                >
                  {data.teacherName.charAt(0)}
                </div>
                <div>
                  <h4 className="font-bold text-sm text-gray-950 leading-tight">{data.teacherName}</h4>
                  <p className="text-[11px] text-gray-400 font-medium mt-0.5">{data.teacherRole}</p>
                </div>
              </div>
              <p className="text-[11px] text-gray-500 italic leading-relaxed font-sans bg-gray-50/50 p-3 rounded-lg border border-gray-100">
                "{data.teacherBio}"
              </p>
            </div>
          )}

          {/* Compliance Checklist Widget */}
          <div className="bg-white rounded-xl border border-gray-100 p-5 space-y-3.5 shadow-sm">
            <h4 className="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-1">
              <ShieldCheck size={14} className="text-green-500" /> Audit Standards
            </h4>
            <div className="space-y-2 text-[11px] text-gray-500 leading-normal font-sans">
              <div className="flex gap-2">
                <span className="text-green-500 font-bold">✔</span>
                <span>Parent-Teacher Privacy compliant</span>
              </div>
              <div className="flex gap-2">
                <span className="text-green-500 font-bold">✔</span>
                <span>Full SOPPA 3rd Party coverage</span>
              </div>
              <div className="flex gap-2">
                <span className="text-green-500 font-bold">✔</span>
                <span>Active contact email available</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
