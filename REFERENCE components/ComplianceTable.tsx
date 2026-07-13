import React, { useState, useEffect } from 'react';
import { Scale, Search, ExternalLink, Download, ShieldCheck } from 'lucide-react';
import { ComplianceItem, SiteConfig } from '../types';

interface ComplianceTableProps {
  config: SiteConfig;
}

export default function ComplianceTable({ config }: ComplianceTableProps) {
  const [items, setItems] = useState<ComplianceItem[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);

  const primaryColor = config.color_primary || '#015BA7';

  useEffect(() => {
    fetch('/api/compliance')
      .then((res) => res.json())
      .then((data) => {
        setItems(data);
        setLoading(false);
      })
      .catch((err) => {
        console.error("Failed to load compliance data", err);
        setLoading(false);
      });
  }, []);

  const filteredItems = items.filter((item) =>
    item.title.toLowerCase().includes(search.toLowerCase()) ||
    item.policy.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" id="compliance-module">
      {/* Module Header */}
      <div className="p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="p-3 bg-blue-50 text-blue-600 rounded-lg" style={{ color: primaryColor, backgroundColor: `${primaryColor}10` }}>
            <Scale size={22} />
          </div>
          <div>
            <h3 className="text-base font-bold text-gray-900 leading-tight">ROE Website Compliance Documents</h3>
            <p className="text-xs text-gray-500">Required public information pursuant to Illinois School Code mandates</p>
          </div>
        </div>

        {/* Live Filter Search Input */}
        <div className="relative w-full md:w-72">
          <Search className="absolute left-3 top-2.5 text-gray-400" size={16} />
          <input
            type="text"
            placeholder="Search policies or titles..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white"
          />
        </div>
      </div>

      {loading ? (
        <div className="p-16 text-center text-gray-400 text-sm">
          Loading audit entries...
        </div>
      ) : filteredItems.length === 0 ? (
        <div className="p-16 text-center text-gray-400 text-sm">
          No compliance documents match your criteria.
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-100/50 border-b border-gray-100 text-[10px] font-extrabold uppercase tracking-wider text-gray-400">
                <th className="px-6 py-4">Required Information / Document Title</th>
                <th className="px-6 py-4">Legal Citation (Illinois School Code)</th>
                <th className="px-6 py-4 text-center">Status</th>
                <th className="px-6 py-4 text-right">Access</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 text-xs">
              {filteredItems.map((item) => (
                <tr key={item.id} className="hover:bg-gray-50/40 transition-colors">
                  <td className="px-6 py-4">
                    <div className="font-bold text-gray-800 leading-normal">{item.title}</div>
                  </td>
                  <td className="px-6 py-4">
                    <span className="font-mono px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 border border-gray-200">
                      {item.policy}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-center">
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                      <ShieldCheck size={10} /> Compliant
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    {item.link.startsWith('http') ? (
                      <a
                        href={item.link}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline cursor-pointer"
                        style={{ color: primaryColor }}
                      >
                        Portal <ExternalLink size={12} />
                      </a>
                    ) : (
                      <a
                        href="#"
                        onClick={(e) => {
                          e.preventDefault();
                          alert(`Displaying details for compliance target: ${item.title}. Real deployment links connect to matching cached Google Drive directories.`);
                        }}
                        className="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline cursor-pointer"
                        style={{ color: primaryColor }}
                      >
                        Folder <ExternalLink size={12} />
                      </a>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
