// js/kgsweb-folders.js

(() => {
	  'use strict';

	  const SELECTORS = {
		container: '.kgsweb-documents',
	  };

	  function $(sel, ctx = document) { return ctx.querySelector(sel); }
	  function $all(sel, ctx = document) { return Array.from(ctx.querySelectorAll(sel)); }

	  function iconFor(node) {
		if (node.type === 'folder') return '📁';
		// quick mime-ish badges
		const name = (node.name || '').toLowerCase();
		if (name.endsWith('.pdf')) return '📄';
		if (name.endsWith('.doc') || name.endsWith('.docx')) return '📝';
		if (name.endsWith('.xls') || name.endsWith('.xlsx')) return '📊';
		if (name.endsWith('.ppt') || name.endsWith('.pptx')) return '📈';
		if (name.endsWith('.jpg') || name.endsWith('.jpeg') || name.endsWith('.png')) return '🖼️';
		return '📎';
	  }

	  function fileLink(node) {
		// Direct link to Google Drive file by ID
		return `https://drive.google.com/file/d/${encodeURIComponent(node.id)}/view`;
	  }

	  function renderNode(node, depth = 0) {
		const el = document.createElement('div');
		el.className = `kgsweb-node depth-${depth} type-${node.type}`;

		if (node.type === 'folder') {
		  const btn = document.createElement('button');
		  btn.type = 'button';
		  btn.className = 'kgsweb-toggle';
		  btn.setAttribute('aria-expanded', 'false');
		  btn.setAttribute('aria-controls', `children-${node.id}`);
		  btn.innerHTML = `<span class="kgsweb-icon">${iconFor(node)}</span><span class="kgsweb-label">${escapeHtml(node.name || 'Folder')}</span>`;
		  el.appendChild(btn);

		  const childrenWrap = document.createElement('div');
		  childrenWrap.id = `children-${node.id}`;
		  childrenWrap.className = 'kgsweb-children';
		  childrenWrap.hidden = true;

		  (node.children || []).forEach(child => {
			childrenWrap.appendChild(renderNode(child, depth + 1));
		  });

		  el.appendChild(childrenWrap);
		} else {
		  const link = document.createElement('a');
		  link.className = 'kgsweb-file';
		  link.href = fileLink(node);
		  link.target = '_blank';
		  link.rel = 'noopener';
		  link.innerHTML = `<span class="kgsweb-icon">${iconFor(node)}</span><span class="kgsweb-label">${escapeHtml(node.name || 'File')}</span>`;
		  el.appendChild(link);
		}

		return el;
	  }

	  function escapeHtml(str) {
		return String(str)
		  .replaceAll('&', '&amp;')
		  .replaceAll('<', '&lt;')
		  .replaceAll('>', '&gt;')
		  .replaceAll('"', '&quot;')
		  .replaceAll("'", '&#039;');
	  }

	  function attachToggles(rootEl) {
		rootEl.addEventListener('click', (e) => {
		  const btn = e.target.closest('button.kgsweb-toggle');
		  if (!btn) return;
		  const controlsId = btn.getAttribute('aria-controls');
		  const panel = controlsId && document.getElementById(controlsId);
		  if (!panel) return;

		  const expanded = btn.getAttribute('aria-expanded') === 'true';
		  btn.setAttribute('aria-expanded', String(!expanded));
		  panel.hidden = expanded;
		});
	  }

	  async function fetchTree(restUrl, rootId = '') {
		const url = new URL(restUrl);
		if (rootId) url.searchParams.set('root', rootId);
		const res = await fetch(url.toString(), { credentials: 'same-origin' });
		if (!res.ok) throw new Error(`Request failed: ${res.status}`);
		return res.json();
	  }

	  async function bootOne(container) {
		const loading = container.querySelector('.kgsweb-docs-loading');
		try {
		  const rootId = container.getAttribute('data-root-id') || '';
		  const data = await fetchTree(KGSwebFolders.restUrl, rootId);

		  container.classList.add('kgsweb-ready');
		  if (loading) loading.remove();

		  const treeWrap = document.createElement('div');
		  treeWrap.className = 'kgsweb-tree';
		  (data.tree || []).forEach(node => {
			treeWrap.appendChild(renderNode(node, 0));
		  });

		  container.appendChild(treeWrap);
		  attachToggles(container);
		} catch (err) {
		  if (loading) loading.textContent = 'Unable to load documents right now.';
		  container.setAttribute('data-error', '1');
		  // Optional: console.warn for debug
		  // console.warn('KGSweb folders error:', err);
		}
	  }

	  function bootAll() {
		const containers = $all(SELECTORS.container);
		if (!containers.length) return;
		containers.forEach(bootOne);
	  }

	  if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bootAll);
	  } else {
		bootAll();
	  }
})();

			
