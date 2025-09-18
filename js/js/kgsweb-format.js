// js/kgsweb-format.js
// ==========================================
// Utility functions for formatting folder/file names,
// extracting dates, and sorting items for display.
// ==========================================

(function(global) {
  "use strict";


  /**
   * Convert raw folder name to a human-friendly display name.
   * Example: "school-board_minutes_2024" -> "School Board Minutes 2024"
   */	 
   
  function formatFolderName(name) {
    if (!name) return "";
			   
    return name.replace(/[-_]+/g, ' ')
               .replace(/\s+/g, ' ')
               .trim()
               .replace(/\b\w/g, c => c.toUpperCase());
  }

  /**
   * Extract a sortable date (YYYYMMDD) from a filename.
   * Supports: 2024-09-14, 2024_09_14, 20240914
   */ 
   
  function extractDate(name) {
    if (!name) return null;
    const match = name.match(/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/);
    if (match) return match[1] + match[2] + match[3];
    return null;
  }

  /**
   * Format filename into a display-friendly string.
   * Removes "school board" prefix, strips extension,
   * converts underscores/dashes to spaces, title-cases,
   * and reformats dates as MM/DD/YYYY.
   */
   
  function sanitizeFileName(name) {
    if (!name) return '';
    let base = name.replace(/\.[^.]+$/, '');
    base = base.replace(/^school[\s-_]*board[\s-_]*/i, '');
    base = base.replace(/[-_]+/g, ' ').trim();

    const match = base.match(/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/);
				
    if (match) base = base.replace(match[0], `${match[2]}/${match[3]}/${match[1]}`);

    base = base.replace(/\b\w/g, c => c.toUpperCase());
    return base;
  }

  /**
   * Sort folders and files so that:
   *  - Folders always come first
   *  - Files are sorted by date (if available) then alphabetically
   */
   
  function sortItems(items) {
    return items.sort((a, b) => {
      if (a.type === 'folder' && b.type !== 'folder') return -1;
      if (a.type !== 'folder' && b.type === 'folder') return 1;

      const dateA = extractDate(a.name) || '99999999';
      const dateB = extractDate(b.name) || '99999999';
      if (dateA !== dateB) return dateB.localeCompare(dateA);

      return a.name.localeCompare(b.name, undefined, { sensitivity: 'base' });
    });
  }

  global.KGSWEB_FORMAT = {
    formatFolderName,
    extractDate,
    sanitizeFileName,
    sortItems
  };

																						
})(window);
