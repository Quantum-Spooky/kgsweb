import React from 'react';
import { Newspaper, BellRing, ArrowRight, ShieldAlert, Sparkles, ExternalLink } from 'lucide-react';
import { SiteConfig } from '../types';

interface NewsPageProps {
  config: SiteConfig;
}

interface NewsArticle {
  id: string;
  title: string;
  excerpt: string;
  date: string;
  category: 'academic' | 'board' | 'pto' | 'sports';
  imgUrl: string;
}

const schoolNews: NewsArticle[] = [
  {
    id: "news-1",
    title: "Terry Milt Announces Regular Board Training Schedule Update",
    excerpt: "Superintendent Terry Milt has published the official ROE compliance document listing board member qualifications and scheduled educational development completions.",
    date: "July 6, 2026",
    category: "board",
    imgUrl: "https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=600&auto=format&fit=crop"
  },
  {
    id: "news-2",
    title: "KGS Volleyball Team Prepares for St. Elizabeth Tournament",
    excerpt: "Our Lady Indians volleyball team has scheduled intensive training sessions next week to prepare for the local Southern Illinois PreK-12 Athletic Conference.",
    date: "July 2, 2026",
    category: "sports",
    imgUrl: "https://images.unsplash.com/photo-1592656094267-764a450201c5?q=80&w=600&auto=format&fit=crop"
  },
  {
    id: "news-3",
    title: "Kell PTO Sponsors Annual School Supply Distribution Drives",
    excerpt: "Help support our local classrooms! KGS PTO is coordinating with regional centers to distribute backpacks, color notebooks, and markers to registered elementary school students.",
    date: "June 29, 2026",
    category: "pto",
    imgUrl: "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?q=80&w=600&auto=format&fit=crop"
  }
];

export default function NewsPage({ config }: NewsPageProps) {
  const primaryColor = config.color_primary || '#015BA7';
  const secondaryColor = config.color_secondary || '#002366';

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fadeIn" id="news-page">
      {/* Alert Ribbon Scroll Marquee */}
      <div className="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-3 flex items-center gap-3 shadow-2xs overflow-hidden select-none">
        <ShieldAlert className="text-red-600 animate-pulse shrink-0" size={18} />
        <div className="flex-1 min-w-0">
          <p className="text-xs font-bold text-red-900 truncate">
            <span className="bg-red-200 text-red-800 text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-sm mr-2">NOTICE</span>
            Website cache is operating in real-time mode. If pages seem out of sync, press "Update Feed" in Live Feed.
          </p>
        </div>
      </div>

      {/* Grid Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Main News Articles */}
        <div className="lg:col-span-8 space-y-6">
          <div className="border-b border-gray-100 pb-3 flex items-center gap-2">
            <Newspaper className="text-blue-600" style={{ color: primaryColor }} size={20} />
            <h3 className="text-lg font-bold text-gray-950">Featured News Articles</h3>
          </div>

          <div className="grid grid-cols-1 gap-6">
            {schoolNews.map((article) => (
              <div 
                key={article.id} 
                className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col sm:flex-row group hover:border-blue-100 hover:shadow-md transition-all duration-300"
              >
                {/* Article Image */}
                <div className="sm:w-52 h-44 sm:h-full bg-gray-50 overflow-hidden relative shrink-0">
                  <img
                    src={article.imgUrl}
                    alt={article.title}
                    className="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300"
                    referrerPolicy="no-referrer"
                  />
                  <div className="absolute top-2 left-2 bg-blue-600/90 text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                    {article.category}
                  </div>
                </div>

                {/* Article Body */}
                <div className="p-5 flex flex-col justify-between flex-1 min-w-0 gap-3">
                  <div className="space-y-1.5">
                    <span className="text-[10px] text-gray-400 font-bold">{article.date}</span>
                    <h4 className="text-sm font-bold text-gray-950 leading-snug group-hover:text-blue-600 transition-colors">
                      {article.title}
                    </h4>
                    <p className="text-xs text-gray-500 leading-relaxed truncate-2-lines font-sans">
                      {article.excerpt}
                    </p>
                  </div>

                  <button 
                    onClick={() => alert(`Reading full news release: "${article.title}"`)}
                    className="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1 cursor-pointer"
                    style={{ color: primaryColor }}
                  >
                    Read Full Story <ArrowRight size={12} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Sidebar Announcements Panel */}
        <div className="lg:col-span-4 space-y-6">
          <div className="border-b border-gray-100 pb-3 flex items-center gap-2">
            <BellRing className="text-blue-600" style={{ color: primaryColor }} size={18} />
            <h3 className="text-lg font-bold text-gray-950">Campus Updates</h3>
          </div>

          <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
            <div className="flex gap-2.5 items-start">
              <span className="w-1.5 h-1.5 rounded-full bg-blue-600 mt-1.5 shrink-0" style={{ backgroundColor: primaryColor }} />
              <div>
                <h5 className="text-xs font-bold text-gray-800">Summer Office Hours</h5>
                <p className="text-[11px] text-gray-400 leading-normal mt-0.5 font-sans">Monday - Thursday: 8:00 AM to 1:00 PM. Closed Fridays.</p>
              </div>
            </div>

            <div className="flex gap-2.5 items-start">
              <span className="w-1.5 h-1.5 rounded-full bg-blue-600 mt-1.5 shrink-0" style={{ backgroundColor: primaryColor }} />
              <div>
                <h5 className="text-xs font-bold text-gray-800">Supply Lists Available</h5>
                <p className="text-[11px] text-gray-400 leading-normal mt-0.5 font-sans">Supplies required for PreK through 8th grades are listed on the school website.</p>
              </div>
            </div>

            <div className="flex gap-2.5 items-start">
              <span className="w-1.5 h-1.5 rounded-full bg-blue-600 mt-1.5 shrink-0" style={{ backgroundColor: primaryColor }} />
              <div>
                <h5 className="text-xs font-bold text-gray-800">Physical Examinations</h5>
                <p className="text-[11px] text-gray-400 leading-normal mt-0.5 font-sans">All incoming kindergarten and sixth grade students must file a child health exam form prior to the start of classes.</p>
              </div>
            </div>
          </div>

          {/* Quick External Links matching original specs */}
          <div className="bg-linear-to-b from-blue-900 to-blue-950 rounded-xl p-5 text-white shadow-sm space-y-4" style={{ backgroundImage: `linear-gradient(to bottom, ${primaryColor}, ${secondaryColor})` }}>
            <div className="space-y-1">
              <span className="text-[9px] font-extrabold uppercase tracking-widest text-sky-200">Resources</span>
              <h4 className="text-base font-bold">Quick School Links</h4>
            </div>

            <div className="space-y-2">
              <a 
                href={config.teacherease_url} 
                target="_blank" 
                rel="noopener noreferrer"
                className="w-full flex items-center justify-between p-2 rounded-lg bg-white/10 hover:bg-white/15 transition-colors text-xs font-semibold cursor-pointer"
              >
                <span>TeacherEase Parent Portal</span>
                <ExternalLink size={12} className="opacity-75" />
              </a>
              <a 
                href="https://www.illinoisreportcard.com/School.aspx?schoolid=130580020032001" 
                target="_blank" 
                rel="noopener noreferrer"
                className="w-full flex items-center justify-between p-2 rounded-lg bg-white/10 hover:bg-white/15 transition-colors text-xs font-semibold cursor-pointer"
              >
                <span>Illinois Report Card Portal</span>
                <ExternalLink size={12} className="opacity-75" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
