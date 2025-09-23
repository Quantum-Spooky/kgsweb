// js/kgsweb-documents.js
(() => {
	'use strict';

	window.KGSWEB = window.KGSWEB || {};
	KGSWEB.Documents = KGSWEB.Documents || {};
	KGSWEB.Helpers = KGSWEB.Helpers || {};
	KGSWEB.Format = KGSWEB.Format || {};

	const $ = KGSWEB.Helpers.qs;
	const $all = KGSWEB.Helpers.qsa;
	const KGSWEB = window.KGSWEB || {};

	// ------------------- Icon helper -------------------
	function iconFor(node) {
		if (node.type === 'folder') return '<i class="fa fa-folder"></i><i class="fa fa-folder-open" style="display:none"></i>';
		const ext = (node.name || '').split('.').pop().toLowerCase();
		switch (ext) {
			case 'pdf': return '<i class="fa fa-file-pdf"></i>';
			case 'doc': case 'docx': return '<i class="fa fa-file-word"></i>';
			case 'xls': case 'xlsx': return '<i class="fa fa-file-excel"></i>';
			case 'ppt': case 'pptx': return '<i class="fa fa-file-powerpoint"></i>';
			case 'jpg': case 'jpeg': case 'png': return '<i class="fa fa-file-image"></i>';
			default: return '<i class="fa fa-file"></i>';
		}
	}

	function toggleIcon(expanded) {
		return `<i class="fa ${expanded ? 'fa-chevron-down' : 'fa-chevron-right'} kgsweb-toggle-icon"></i>`;
	}

	function escapeHtml(str) {
		return String(str)
			.replaceAll('&', '&amp;')
			.replaceAll('<', '&lt;')
			.replaceAll('>', '&gt;')
			.replaceAll('"', '&quot;')
			.replaceAll("'", "&#039;");
	}

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

			// Pass sortMode and foldersFirst to child UL
			childrenUl.setAttribute('data-sort-mode', sortMode);
			childrenUl.setAttribute('data-folders-first', foldersFirst ? 'true' : 'false');

			const children = Array.isArray(node.children) ? node.children : [];
			const safeChildren = children.filter(c => c && typeof c === 'object');
			const sortedChildren = KGSWEB.Helpers.sortItems(safeChildren, { mode: sortMode, foldersFirst });

			sortedChildren.forEach(child => childrenUl.appendChild(renderNode(child, depth + 1, sortMode, foldersFirst)));
			li.appendChild(childrenUl);

		} else {
			const link = document.createElement('a');
			link.className = 'kgsweb-file';
			link.href = `https://drive.google.com/file/d/${encodeURIComponent(node.id)}/view`;
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
			const panel = $( `[id="${toggle.getAttribute('aria-controls')}"]`, container );
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

	// ------------------- Fetch tree via REST -------------------
	async function fetchTree(restUrl, rootId = '') {
		if (!restUrl) throw new Error('REST URL missing');
		const url = new URL(restUrl, window.location.origin);
		if (rootId) url.searchParams.set('root', rootId);

		const res = await fetch(url.toString(), { credentials: 'same-origin' });
		if (!res.ok) throw new Error(`Request failed: ${res.status}`);
		return res.json();
	}

	// ------------------- Boot one container -------------------
	async function bootOne(container) {
		if (container.classList.contains('kgsweb-ready')) return;

		const restUrl = container.getAttribute('data-rest-url');
		const rootId = container.getAttribute('data-root-id') || '';
		const sortMode = (container.getAttribute('data-sort-mode') || 'alpha-asc').toLowerCase();
		const foldersFirst = container.getAttribute('data-folders-first') !== 'false';

		attachToggles(container);

		if (restUrl && rootId) {
			const loading = container.querySelector('.kgsweb-docs-loading');
			try {
				const data = await fetchTree(restUrl, rootId);
				if (loading) loading.remove();

				const treeUl = document.createElement('ul');
				treeUl.className = 'kgsweb-tree';
				treeUl.setAttribute('data-sort-mode', sortMode);
				treeUl.setAttribute('data-folders-first', foldersFirst ? 'true' : 'false');

				const nodes = Array.isArray(data.tree) ? data.tree : [];
				const sortedNodes = KGSWEB.Helpers.sortItems(nodes, { mode: sortMode, foldersFirst });
				sortedNodes.forEach(node => treeUl.appendChild(renderNode(node, 0, sortMode, foldersFirst)));

				const existingTree = container.querySelector('.kgsweb-tree');
				if (existingTree) existingTree.remove();
				container.appendChild(treeUl);
			} catch (err) {
				if (loading) loading.textContent = 'Unable to load documents right now.';
				container.setAttribute('data-error', '1');
				console.warn('KGSweb documents error:', err);
			}
		}

		container.classList.add('kgsweb-ready');
	}

	// ------------------- Boot all containers -------------------

        bootAll: function(){
            $('.kgsweb-documents').each(function(){
                const root = $(this).data('root');
                $.getJSON('/wp-json/kgsweb/v1/documents', { root: root })
                    .done(function(data){
                        console.log('KGSWEB: Documents tree loaded', data);
                    })
                    .fail(function(xhr){
                        console.warn('KGSWEB: Could not fetch documents tree', xhr.status);
                    });
            });
        }
    };

    $(document).ready(function(){ KGSWEB.Documents.bootAll(); });
	
	
})(jQuery);