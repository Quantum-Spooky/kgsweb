// ==========================================
// js/kgsweb-documents.js
// Builds the interactive document tree for KGSweb
// - FontAwesome icons for folders/files
// - Toggle open/close folders
// - Keyboard support (Enter/Space)
// - Properly sorted folders and files
// Namespaced under KGSWEB.Documents for clarity
// ==========================================

(() => {
	'use strict';

	window.KGSWEB = window.KGSWEB || {};
	KGSWEB.Documents = KGSWEB.Documents || {};
	KGSWEB.Format = KGSWEB.Format || window.KGSWEB_FORMAT || {};
	KGSWEB.Helpers = KGSWEB.Helpers || {};

	// ------------------- DOM helpers -------------------
	const $ = (sel, ctx = document) => ctx.querySelector(sel);
	const $all = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

	// ------------------- Icons -------------------
	function iconFor(node) {
		if (node.type === 'folder') return '<i class="fa fa-folder"></i><i class="fa fa-folder-open" style="display:none"></i>';
		const name = (node.name || '').toLowerCase();
		if (name.endsWith('.pdf')) return '<i class="fa fa-file-pdf"></i>';
		if (name.endsWith('.doc') || name.endsWith('.docx')) return '<i class="fa fa-file-word"></i>';
		if (name.endsWith('.xls') || name.endsWith('.xlsx')) return '<i class="fa fa-file-excel"></i>';
		if (name.endsWith('.ppt') || name.endsWith('.pptx')) return '<i class="fa fa-file-powerpoint"></i>';
		if (name.endsWith('.jpg') || name.endsWith('.jpeg') || name.endsWith('.png')) return '<i class="fa fa-file-image"></i>';
		return '<i class="fa fa-file"></i>';
	}

	function toggleIcon(expanded) {
		return `<i class="fa ${expanded ? 'fa-chevron-down' : 'fa-chevron-right'} kgsweb-toggle-icon"></i>`;
	}

	function fileLink(node) {
		return `https://drive.google.com/file/d/${encodeURIComponent(node.id)}/view`;
	}

	function escapeHtml(str) {
		return String(str)
			.replaceAll('&', '&amp;')
			.replaceAll('<', '&lt;')
			.replaceAll('>', '&gt;')
			.replaceAll('"', '&quot;')
			.replaceAll("'", "&#039;");
	}

	// ------------------- Document-specific formatting functions -------------------
	KGSWEB.Format.formatFolderName = function(name) {
		if (!name) return '';
		return name.replace(/[-_]+/g, ' ')
			.replace(/\s+/g, ' ')
			.trim()
			.replace(/\b\w/g, c => c.toUpperCase());
	};

	KGSWEB.Format.sanitizeFileName = function(name) {
		if (!name) return '';
		let base = name.replace(/\.[^.]+$/, '');
		base = base.replace(/^school[\s-_]*board[\s-_]*/i, '');
		base = base.replace(/[-_]+/g, ' ').trim();

		const match = base.match(/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/);
		if (match) base = base.replace(match[0], `${match[2]}/${match[3]}/${match[1]}`);

		base = base.replace(/\b\w/g, c => c.toUpperCase());
		return base;
	};

	// ------------------- Render node -------------------
	function renderNode(node, depth = 0, sortMode = 'alpha-asc', foldersFirst = true) {
		const li = document.createElement('li');
		li.className = `kgsweb-node depth-${depth} type-${node.type}`;

		if (node.type === 'folder') {
			const toggle = document.createElement('div');
			toggle.className = 'kgsweb-toggle';
			toggle.setAttribute('role', 'button');
			toggle.setAttribute('aria-expanded', 'false');
			toggle.setAttribute('aria-controls', `children-${node.id}`);
			toggle.setAttribute('tabindex', '0');

			toggle.innerHTML = `
        <span class="kgsweb-icon"><i class="fa fa-folder"></i><i class="fa fa-folder-open" style="display:none"></i></span>
        <span class="kgsweb-label">${escapeHtml(KGSWEB.Format.formatFolderName(node.name || 'Folder'))}</span>
        ${toggleIcon(false)}
      `;
			li.appendChild(toggle);

			const childrenUl = document.createElement('ul');
			childrenUl.id = `children-${node.id}`;
			childrenUl.className = 'kgsweb-children';
			childrenUl.hidden = true;

			let children = Array.isArray(node.children) ? node.children : [];
			const safeChildren = children.filter(c => c && typeof c === 'object');
			const sortedChildren = KGSWEB.Helpers.sortItems(safeChildren, {
				mode: sortMode,
				foldersFirst
			});

			sortedChildren.forEach(child => childrenUl.appendChild(renderNode(child, depth + 1, sortMode, foldersFirst)));
			li.appendChild(childrenUl);

		} else {
			const link = document.createElement('a');
			link.className = 'kgsweb-file';
			link.href = fileLink(node);
			link.target = '_blank';
			link.rel = 'noopener';
			link.innerHTML = `
        <span class="kgsweb-icon">${iconFor(node)}</span>
        <span class="kgsweb-label">${escapeHtml(KGSWEB.Format.sanitizeFileName(node.name || 'File'))}</span>
      `;
			li.appendChild(link);
		}

		return li;
	}

	// ------------------- Toggles -------------------
	function attachToggles(container) {
		container.addEventListener('click', e => {
			const toggle = e.target.closest('.kgsweb-toggle');
			if (!toggle) return;
			const panel = document.getElementById(toggle.getAttribute('aria-controls'));
			if (!panel) return;

			const expanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', String(!expanded));
			panel.hidden = expanded;

			const iconEl = toggle.querySelector('.kgsweb-icon');
			if (iconEl) {
				const closed = iconEl.querySelector('.fa-folder');
				const open = iconEl.querySelector('.fa-folder-open');
				if (closed && open) {
					closed.style.display = expanded ? 'inline-block' : 'none';
					open.style.display = expanded ? 'none' : 'inline-block';
				}
			}

			const chevron = toggle.querySelector('.kgsweb-toggle-icon');
			if (chevron) chevron.className = `fa ${expanded ? 'fa-chevron-right' : 'fa-chevron-down'} kgsweb-toggle-icon`;
		});

		container.addEventListener('keydown', e => {
			if (e.key === 'Enter' || e.key === ' ') {
				const toggle = e.target.closest('.kgsweb-toggle');
				if (toggle) {
					e.preventDefault();
					toggle.click();
				}
			}
		});
	}

	// ------------------- Fetch tree -------------------
	async function fetchTree(restUrl, rootId = '') {
		const url = new URL(restUrl, window.location.origin);
		if (rootId) url.searchParams.set('root', rootId);
		const res = await fetch(url.toString(), {
			credentials: 'same-origin'
		});
		if (!res.ok) throw new Error(`Request failed: ${res.status}`);
		return res.json();
	}

	// ------------------- Boot one container -------------------
	async function bootOne(container) {
		if (container.classList.contains('kgsweb-ready')) return;

		const loading = container.querySelector('.kgsweb-docs-loading');
		try {
			const rootId = container.getAttribute('data-root-id') || (window.KGSwebFolders && KGSwebFolders.rootId) || '';
			if (!rootId) throw new Error('No root folder specified');

			// Normalize sort mode and foldersFirst
			const sortMode = (container.getAttribute('data-sort-mode') || 'alpha-asc').toLowerCase();
			const foldersFirst = container.getAttribute('data-folders-first') !== 'false';

			const data = await fetchTree(KGSwebFolders.restUrl, rootId);

			container.classList.add('kgsweb-ready');
			if (loading) loading.remove();

			const existingTree = container.querySelector('.kgsweb-tree');
			if (existingTree) existingTree.remove();

			const treeUl = document.createElement('ul');
			treeUl.className = 'kgsweb-tree';
			const rootNodes = Array.isArray(data.tree) ? data.tree : [];

			// Sort root nodes according to container attributes
			const sortedRootNodes = KGSWEB.Helpers.sortItems(rootNodes, {
				mode: sortMode,
				foldersFirst
			});
			sortedRootNodes.forEach(node => treeUl.appendChild(renderNode(node, 0, sortMode, foldersFirst)));

			container.appendChild(treeUl);
			attachToggles(container);

		} catch (err) {
			if (loading) loading.textContent = 'Unable to load documents right now.';
			container.setAttribute('data-error', '1');
			console.warn('KGSweb documents error:', err);
		}
	}

	// ------------------- Boot all containers -------------------
	function bootAll() {
		$all('.kgsweb-documents').forEach(bootOne);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bootAll);
	} else {
		bootAll();
	}

})();