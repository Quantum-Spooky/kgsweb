import React, { useState, useEffect } from 'react';
import { RefreshCw, Bell, Image, Calendar, MessageSquare, Trash2, Heart } from 'lucide-react';
import { LiveFeedPost, SiteConfig } from '../types';
import { motion } from 'motion/react';

interface LiveFeedProps {
  config: SiteConfig;
  isAdmin?: boolean;
  onDeletePost?: (id: string) => void;
}

export default function LiveFeed({ config, isAdmin = false, onDeletePost }: LiveFeedProps) {
  const [posts, setPosts] = useState<LiveFeedPost[]>([]);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [likes, setLikes] = useState<Record<string, number>>({});

  const primaryColor = config.color_primary || '#015BA7';

  const fetchLiveFeed = async () => {
    setIsRefreshing(true);
    try {
      const res = await fetch('/api/live-feed');
      if (res.ok) {
        const data = await res.json();
        setPosts(data);
      }
    } catch (err) {
      console.error("Failed to load live feed", err);
    } finally {
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    fetchLiveFeed();
  }, []);

  const handleLike = (id: string) => {
    setLikes(prev => ({
      ...prev,
      [id]: (prev[id] || 0) + 1
    }));
  };

  return (
    <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-6" id="live-feed-component">
      {/* Title Header with Refresh Action */}
      <div className="flex justify-between items-center border-b border-gray-100 pb-4">
        <div className="flex items-center gap-2">
          <div className="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse" />
          <h3 className="text-lg font-bold text-gray-900 tracking-tight flex items-center gap-2">
            <Bell size={18} className="text-blue-600" style={{ color: primaryColor }} />
            Latest Announcements & Live Feed
          </h3>
        </div>
        <button
          onClick={fetchLiveFeed}
          disabled={isRefreshing}
          className="p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-gray-50 border border-gray-100 transition-colors flex items-center gap-1.5 text-xs font-semibold select-none cursor-pointer disabled:opacity-50"
          style={{ activeColor: primaryColor }}
        >
          <RefreshCw size={14} className={isRefreshing ? "animate-spin" : ""} />
          {isRefreshing ? "Syncing..." : "Update Feed"}
        </button>
      </div>

      {posts.length === 0 ? (
        <div className="text-center py-12 text-gray-400 text-sm">
          No live announcements posted yet. Check back soon!
        </div>
      ) : (
        <div className="space-y-6 max-h-[580px] overflow-y-auto pr-1">
          {posts.map((post, idx) => (
            <motion.div
              key={post.id}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: idx * 0.05 }}
              className="group p-4 bg-gray-50/50 rounded-xl border border-gray-100/80 hover:bg-white hover:border-blue-100 hover:shadow-xs transition-all duration-200 relative"
            >
              <div className="flex gap-3">
                {/* Author Avatar emblem */}
                <div 
                  className="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0 select-none"
                  style={{ backgroundColor: primaryColor }}
                >
                  {post.author.charAt(0)}
                </div>

                <div className="space-y-2 flex-1">
                  {/* Metadata */}
                  <div className="flex flex-wrap items-center justify-between gap-1">
                    <div>
                      <h4 className="text-sm font-bold text-gray-900 leading-tight">
                        {post.author}
                      </h4>
                      <p className="text-[10px] text-gray-400 font-medium flex items-center gap-1">
                        <Calendar size={10} /> {post.date}
                      </p>
                    </div>

                    {/* Delete capability if opened from admin pane */}
                    {isAdmin && onDeletePost && (
                      <button
                        onClick={() => onDeletePost(post.id)}
                        className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                        title="Remove Post"
                      >
                        <Trash2 size={14} />
                      </button>
                    )}
                  </div>

                  {/* Body Content */}
                  <p className="text-xs text-gray-700 leading-relaxed whitespace-pre-wrap font-sans">
                    {post.content}
                  </p>

                  {/* Attachment image */}
                  {post.image_url && (
                    <div className="mt-3 overflow-hidden rounded-lg border border-gray-100 max-h-48 bg-gray-100">
                      <img
                        src={post.image_url}
                        alt="Announcement attached attachment"
                        className="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300"
                        referrerPolicy="no-referrer"
                      />
                    </div>
                  )}

                  {/* Micro Actions (Likes/Comments to mimic fully functional Apptegy portal) */}
                  <div className="flex items-center gap-4 pt-1 text-gray-400 text-[11px] font-semibold select-none">
                    <button 
                      onClick={() => handleLike(post.id)}
                      className="flex items-center gap-1 hover:text-red-500 transition-colors"
                    >
                      <Heart size={12} className={likes[post.id] ? "fill-red-500 text-red-500" : ""} />
                      <span>{likes[post.id] || 0} Likes</span>
                    </button>
                    <span className="flex items-center gap-1">
                      <MessageSquare size={12} />
                      Official Announcement
                    </span>
                  </div>
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  );
}
