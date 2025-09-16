// js/kgsweb-folders.js
(() => {
  'use strict';

  const SELECTORS = {
    container: '.kgsweb-documents',
  };

  function $(sel, ctx = document) { return ctx.querySelector(sel); }
  function $all(sel, ctx = document) { return Array.from(ctx.querySelectorAll(sel)); }

  // Map nodes to FontAwesome icons
  function iconFor(node) {
    if (node.type === 'folder') {
      return `
        <i class="fa fa-folder"></i>
        <i class="fa fa-folder-open" style="display:none"></i>
      `;
    }

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

  function renderNode(node, depth = 0) {
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
        <span class="kgsweb-icon">
          <i class="fa fa-folder"></i>
          <i class="fa fa-folder-open" style="display:none"></i>
        </span>
        <span class="kgsweb-label">${escapeHtml(node.name || 'Folder')}</span>
        ${toggleIcon(false)}
      `;

      li.appendChild(toggle);

      const childrenUl = document.createElement('ul');
      childrenUl.id = `children-${node.id}`;
      childrenUl.className = 'kgsweb-children';
      childrenUl.hidden = true;

      (node.children || []).forEach(child => {
        childrenUl.appendChild(renderNode(child, depth + 1));
      });

      li.appendChild(childrenUl);
    } else {
      const link = document.createElement('a');
      link.className = 'kgsweb-file';
      link.href = fileLink(node);
      link.target = '_blank';
      link.rel = 'noopener';
      link.innerHTML = `<span class="kgsweb-icon">${iconFor(node)}</span>
                        <span class="kgsweb-label">${escapeHtml(node.name || 'File')}</span>`;
      li.appendChild(link);
    }

    return li;
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
      const toggle = e.target.closest('.kgsweb-toggle');
      if (!toggle) return;

      const panel = document.getElementById(toggle.getAttribute('aria-controls'));
      if (!panel) return;

      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      panel.hidden = expanded;

      // Swap folder icons
      const iconEl = toggle.querySelector('.kgsweb-icon');
      if (iconEl) {
        const closedIcon = iconEl.querySelector('.fa-folder');
        const openIcon = iconEl.querySelector('.fa-folder-open');
        if (closedIcon && openIcon) {
          closedIcon.style.display = expanded ? 'inline-block' : 'none';
          openIcon.style.display = expanded ? 'none' : 'inline-block';
        }
      }

      // Update chevron icon
      const chevron = toggle.querySelector('.kgsweb-toggle-icon');
      if (chevron) {
        chevron.className = `fa ${expanded ? 'fa-chevron-right' : 'fa-chevron-down'} kgsweb-toggle-icon`;
      }
    });

    // Keyboard accessibility
    rootEl.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        const toggle = e.target.closest('.kgsweb-toggle');
        if (toggle) {
          e.preventDefault();
          toggle.click();
        }
      }
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
      const rootId = container.getAttribute('data-root-id') || (window.KGSwebFolders && window.KGSwebFolders.rootId) || '';
      if (!rootId) throw new Error('No root folder specified');

      const data = await fetchTree(KGSwebFolders.restUrl, rootId);

      container.classList.add('kgsweb-ready');
      if (loading) loading.remove();

      const treeUl = document.createElement('ul');
      treeUl.className = 'kgsweb-tree';
      (data.tree || []).forEach(node => {
        treeUl.appendChild(renderNode(node, 0));
      });

      container.appendChild(treeUl);
      attachToggles(container);
    } catch (err) {
      if (loading) loading.textContent = 'Unable to load documents right now.';
      container.setAttribute('data-error', '1');
      console.warn('KGSweb folders error:', err);
    }
  }

  function bootAll() {
    const containers = $all(SELECTORS.container);
    containers.forEach(bootOne);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAll);
  } else {
    bootAll();
  }

})();
