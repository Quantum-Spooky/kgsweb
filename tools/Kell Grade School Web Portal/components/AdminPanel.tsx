import React, { useState } from 'react';
import { Settings, Save, ToggleLeft, ToggleRight, Trash2, Shield, LogOut, Check, Sparkles, MessageSquare, Plus, CheckSquare, Settings2, Sliders, Bell } from 'lucide-react';
import { SiteConfig, LiveFeedPost } from '../types';

interface AdminPanelProps {
  config: SiteConfig;
  onSaveConfig: (updated: SiteConfig) => void;
  onClose: () => void;
}

export default function AdminPanel({ config, onSaveConfig, onClose }: AdminPanelProps) {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [password, setPassword] = useState('');
  const [email, setEmail] = useState('');
  const [authMethod, setAuthMethod] = useState<'google' | 'password'>('google');
  const [isVerifying, setIsVerifying] = useState(false);
  const [authError, setAuthError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<'settings' | 'nav' | 'feed'>('settings');

  // Forms state
  const [localConfig, setLocalConfig] = useState<SiteConfig>({ ...config });
  const [newPost, setNewPost] = useState({ content: '', author: '', image_url: '' });
  const [feedPosts, setFeedPosts] = useState<LiveFeedPost[]>([]);

  // Fetch feed to delete posts
  React.useEffect(() => {
    if (isAuthenticated) {
      fetch('/api/live-feed')
        .then(res => res.json())
        .then(data => setFeedPosts(data));
    }
  }, [isAuthenticated]);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError(null);

    if (authMethod === 'password') {
      if (password === 'admin123' || password === 'kellschool') {
        setIsAuthenticated(true);
      } else {
        setAuthError("Invalid master security key. Please try again.");
      }
    } else {
      if (!email || !email.includes('@')) {
        setAuthError("Please enter a valid Google Account email address.");
        return;
      }
      setIsVerifying(true);
      try {
        const res = await fetch('/api/auth/google-check', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email })
        });
        const data = await res.json();
        if (res.ok && data.authorized) {
          setIsAuthenticated(true);
        } else {
          setAuthError(data.message || "Your email is not listed in the authorized_users.json staff whitelist.");
        }
      } catch (err) {
        setAuthError("Connection error. Failed to verify whitelist.");
      } finally {
        setIsVerifying(false);
      }
    }
  };

  const handleToggle = (key: keyof SiteConfig) => {
    setLocalConfig(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
  };

  const handleInputChange = (key: keyof SiteConfig, val: string) => {
    setLocalConfig(prev => ({
      ...prev,
      [key]: val
    }));
  };

  const handleSave = () => {
    onSaveConfig(localConfig);
    alert("Configurations saved successfully!");
  };

  const handleAddFeedPost = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newPost.content || !newPost.author) {
      alert("Content and Author are required.");
      return;
    }

    try {
      const res = await fetch('/api/live-feed', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newPost)
      });
      if (res.ok) {
        const result = await res.json();
        setFeedPosts(prev => [result.post, ...prev]);
        setNewPost({ content: '', author: '', image_url: '' });
        alert("Live Feed announcement posted!");
      }
    } catch (err) {
      console.error("Failed to add post", err);
    }
  };

  const handleDeletePost = async (id: string) => {
    if (!confirm("Are you sure you want to delete this live feed post?")) return;
    try {
      const res = await fetch(`/api/live-feed/${id}`, { method: 'DELETE' });
      if (res.ok) {
        setFeedPosts(prev => prev.filter(p => p.id !== id));
      }
    } catch (err) {
      console.error("Failed to delete post", err);
    }
  };

  if (!isAuthenticated) {
    return (
      <div className="max-w-md mx-auto my-16 bg-white rounded-2xl border border-gray-100 shadow-xl p-8 space-y-6 animate-fadeIn" id="admin-login-gate">
        <div className="text-center space-y-2">
          <div className="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto border border-blue-100">
            <Shield size={24} />
          </div>
          <h3 className="text-xl font-bold text-gray-950">CMS Administration Guard</h3>
          <p className="text-xs text-gray-500">Authentication required to customize Kell Grade School portal</p>
        </div>

        {/* Auth Method Selector */}
        <div className="flex border-b border-gray-100 pb-1 gap-4 text-xs font-bold text-gray-400">
          <button 
            type="button" 
            onClick={() => { setAuthMethod('google'); setAuthError(null); }}
            className={`pb-2 border-b-2 transition-all cursor-pointer ${authMethod === 'google' ? 'text-blue-600 border-blue-600' : 'border-transparent hover:text-gray-600'}`}
          >
            Google Whitelist Email
          </button>
          <button 
            type="button" 
            onClick={() => { setAuthMethod('password'); setAuthError(null); }}
            className={`pb-2 border-b-2 transition-all cursor-pointer ${authMethod === 'password' ? 'text-blue-600 border-blue-600' : 'border-transparent hover:text-gray-600'}`}
          >
            Security Key
          </button>
        </div>

        {authError && (
          <div className="p-3 bg-red-50 text-red-700 rounded-lg text-xs font-medium border border-red-100">
            {authError}
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-4">
          {authMethod === 'google' ? (
            <div className="space-y-2">
              <label className="text-[10px] font-bold text-gray-400 uppercase">Google Staff Email</label>
              <input
                type="email"
                placeholder="e.g. tmilt@kellgradeschool.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-3 py-2.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                disabled={isVerifying}
              />
              <p className="text-[10px] text-gray-400 italic font-sans leading-relaxed">
                Checks your email against the active <strong>authorized_users.json</strong> Google Group whitelist generated by your server crawl.
                <br />
                <span className="text-gray-500">Try entering: <strong className="text-blue-600">travisdonoho@gmail.com</strong> or <strong className="text-blue-600">tmilt@kellgradeschool.com</strong></span>
              </p>
            </div>
          ) : (
            <div className="space-y-1">
              <label className="text-[10px] font-bold text-gray-400 uppercase">Admin Security Key</label>
              <input
                type="password"
                placeholder="Enter password (e.g. admin123)"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-3 py-2.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              />
              <p className="text-[10px] text-gray-400 italic font-sans mt-1">Hint: Use password <strong>admin123</strong> to enter dashboard</p>
            </div>
          )}

          <div className="flex gap-2 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 py-2.5 border border-gray-100 hover:bg-gray-50 text-gray-600 rounded-lg text-xs font-semibold select-none cursor-pointer"
              disabled={isVerifying}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs select-none cursor-pointer flex items-center justify-center gap-1.5"
              disabled={isVerifying}
            >
              {isVerifying ? "Verifying..." : "Sign In"}
            </button>
          </div>
        </form>
      </div>
    );
  }

  return (
    <div className="max-w-5xl mx-auto my-10 bg-white rounded-2xl border border-gray-100 shadow-xl overflow-hidden animate-fadeIn" id="admin-dashboard">
      {/* Dashboard Top Header */}
      <div className="bg-gray-900 text-white px-6 py-4 flex justify-between items-center">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-blue-500 text-white rounded-lg">
            <Settings size={18} />
          </div>
          <div>
            <h3 className="text-base font-bold">KGS Dashboard Hub</h3>
            <p className="text-[10px] text-gray-400 font-sans">Syncing directly to Google Sheets database mappings</p>
          </div>
        </div>
        <button 
          onClick={() => setIsAuthenticated(false)}
          className="text-xs font-semibold px-3 py-1.5 rounded-md bg-white/10 hover:bg-white/15 transition-colors flex items-center gap-1.5 cursor-pointer"
        >
          <LogOut size={12} /> Sign Out
        </button>
      </div>

      {/* Tabs Menu */}
      <div className="flex border-b border-gray-100 bg-gray-50/50">
        <button
          onClick={() => setActiveTab('settings')}
          className={`flex items-center gap-1.5 px-6 py-3.5 text-xs font-semibold border-b-2 transition-all cursor-pointer ${activeTab === 'settings' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-gray-500 hover:text-gray-900'}`}
        >
          <Settings2 size={14} /> Site Config
        </button>
        <button
          onClick={() => setActiveTab('nav')}
          className={`flex items-center gap-1.5 px-6 py-3.5 text-xs font-semibold border-b-2 transition-all cursor-pointer ${activeTab === 'nav' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-gray-500 hover:text-gray-900'}`}
        >
          <Sliders size={14} /> Menu Nav Toggles
        </button>
        <button
          onClick={() => setActiveTab('feed')}
          className={`flex items-center gap-1.5 px-6 py-3.5 text-xs font-semibold border-b-2 transition-all cursor-pointer ${activeTab === 'feed' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-gray-500 hover:text-gray-900'}`}
        >
          <Bell size={14} /> Live Feed GUI
        </button>
      </div>

      {/* Content Panels */}
      <div className="p-6 sm:p-8 space-y-8">
        {activeTab === 'settings' && (
          <div className="space-y-6">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase">School Name</label>
                <input
                  type="text"
                  value={localConfig.site_name}
                  onChange={(e) => handleInputChange('site_name', e.target.value)}
                  className="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>

              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase">District Slogan</label>
                <input
                  type="text"
                  value={localConfig.district_name}
                  onChange={(e) => handleInputChange('district_name', e.target.value)}
                  className="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>

              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase">Weather ZIP Location [TASK 48/50]</label>
                <input
                  type="text"
                  value={localConfig.weather_location}
                  onChange={(e) => handleInputChange('weather_location', e.target.value)}
                  className="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>

              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase">Principal Spt</label>
                <input
                  type="text"
                  value={localConfig.principal_name}
                  onChange={(e) => handleInputChange('principal_name', e.target.value)}
                  className="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
              </div>
            </div>

            {/* Custom Theme Colors (Task 16 Style standards) */}
            <div className="border-t border-gray-100 pt-6 space-y-4">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1">
                <Sparkles size={14} className="text-blue-500" /> Style Standard Brandings
              </h4>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div className="space-y-1">
                  <label className="text-[10px] font-bold text-gray-400 uppercase">Primary Kell Blue</label>
                  <div className="flex gap-2">
                    <input
                      type="color"
                      value={localConfig.color_primary}
                      onChange={(e) => handleInputChange('color_primary', e.target.value)}
                      className="w-8 h-8 rounded border border-gray-200 cursor-pointer"
                    />
                    <input
                      type="text"
                      value={localConfig.color_primary}
                      onChange={(e) => handleInputChange('color_primary', e.target.value)}
                      className="flex-1 px-3 py-1.5 text-xs border border-gray-200 rounded-lg font-mono"
                    />
                  </div>
                </div>

                <div className="space-y-1">
                  <label className="text-[10px] font-bold text-gray-400 uppercase">Secondary Deep Navy</label>
                  <div className="flex gap-2">
                    <input
                      type="color"
                      value={localConfig.color_secondary}
                      onChange={(e) => handleInputChange('color_secondary', e.target.value)}
                      className="w-8 h-8 rounded border border-gray-200 cursor-pointer"
                    />
                    <input
                      type="text"
                      value={localConfig.color_secondary}
                      onChange={(e) => handleInputChange('color_secondary', e.target.value)}
                      className="flex-1 px-3 py-1.5 text-xs border border-gray-200 rounded-lg font-mono"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'nav' && (
          <div className="space-y-6">
            <div className="border-b border-gray-100 pb-3">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Navigation Visibility Map</h4>
              <p className="text-[11px] text-gray-400 mt-0.5">Toggle links displayed inside the main header and mobile drawer</p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              {[
                { label: "Home Link", key: "show_home_nav_link" },
                { label: "District Link", key: "show_district_nav_link" },
                { label: "Academics Link", key: "show_academics_nav_link" },
                { label: "Calendar Link", key: "show_calendar_nav_link" },
                { label: "Dining Link", key: "show_dining_nav_link" },
                { label: "News Link", key: "show_news_nav_link" },
                { label: "Activities Link", key: "show_activities_nav_link" },
                { label: "Family Link", key: "show_family_nav_link" }
              ].map((item) => (
                <div 
                  key={item.key} 
                  onClick={() => handleToggle(item.key as keyof SiteConfig)}
                  className="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between cursor-pointer hover:bg-gray-100/50 select-none"
                >
                  <span className="text-xs font-bold text-gray-700">{item.label}</span>
                  {localConfig[item.key as keyof SiteConfig] ? (
                    <ToggleRight className="text-blue-600" size={24} />
                  ) : (
                    <ToggleLeft className="text-gray-400" size={24} />
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {activeTab === 'feed' && (
          <div className="space-y-8" id="live-feed-dashboard">
            {/* Post creator Form (Task 52) */}
            <form onSubmit={handleAddFeedPost} className="bg-gray-50/50 p-6 rounded-xl border border-gray-100 space-y-4">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
                <Plus size={14} /> Create Live Feed Announcement
              </h4>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-1">
                  <label className="text-[10px] font-bold text-gray-400 uppercase">Author Designation</label>
                  <input
                    type="text"
                    required
                    placeholder="Terry Milt, Principal"
                    value={newPost.author}
                    onChange={(e) => setNewPost({ ...newPost, author: e.target.value })}
                    className="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white"
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-[10px] font-bold text-gray-400 uppercase">Optional Attachment Image URL</label>
                  <input
                    type="url"
                    placeholder="https://domain.com/photo.jpg"
                    value={newPost.image_url}
                    onChange={(e) => setNewPost({ ...newPost, image_url: e.target.value })}
                    className="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white"
                  />
                </div>
              </div>

              <div className="space-y-1">
                <label className="text-[10px] font-bold text-gray-400 uppercase">Announcement Content</label>
                <textarea
                  required
                  rows={3}
                  placeholder="Enter message details..."
                  value={newPost.content}
                  onChange={(e) => setNewPost({ ...newPost, content: e.target.value })}
                  className="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white leading-relaxed"
                />
              </div>

              <button
                type="submit"
                className="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-opacity flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <Save size={14} /> Post Announcement Instantly
              </button>
            </form>

            {/* List for deletions */}
            <div className="space-y-3">
              <h4 className="text-xs font-bold text-gray-800 uppercase tracking-wider">Active Live Feed List</h4>
              <div className="divide-y divide-gray-100 max-h-60 overflow-y-auto border border-gray-100 rounded-xl bg-white p-2">
                {feedPosts.map((post) => (
                  <div key={post.id} className="py-3 px-2 flex justify-between items-center gap-4 hover:bg-gray-50/40">
                    <div className="min-w-0">
                      <div className="flex items-center gap-1.5 flex-wrap">
                        <span className="text-xs font-bold text-gray-900">{post.author}</span>
                        <span className="text-[9px] text-gray-400 font-medium">{post.date}</span>
                      </div>
                      <p className="text-[11px] text-gray-500 truncate leading-relaxed mt-0.5">{post.content}</p>
                    </div>
                    <button
                      onClick={() => handleDeletePost(post.id)}
                      className="p-1.5 hover:bg-red-50 text-red-500 rounded-lg transition-colors shrink-0"
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Action Controls */}
        <div className="flex gap-3 border-t border-gray-100 pt-6">
          <button
            onClick={onClose}
            className="px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-900 border border-gray-100 hover:bg-gray-50 rounded-lg cursor-pointer"
          >
            Cancel
          </button>
          <button
            onClick={handleSave}
            className="ml-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1.5 cursor-pointer"
          >
            <Check size={14} /> Save Mappings Sync
          </button>
        </div>
      </div>
    </div>
  );
}
