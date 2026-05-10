(() => {
  'use strict';

  console.log('KGSWEB FOLDERS INIT', Date.now());

  const SELECTORS = { container: '.kgsweb-documents' };

  function $(sel, ctx = document) { return ctx.querySelector(sel); }
  function $all(sel, ctx = document) { return Array.from(ctx.querySelectorAll(sel)); }

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

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'",'&#039;');
  }

  function renderNode(node, depth=0, startCollapsed=true) {
    const li = document.createElement('li');
    li.className = `kgsweb-node depth-${depth} type-${node.type}`;

    if (node.type === 'folder') {
      const toggle = document.createElement('div');
      toggle.className = 'kgsweb-toggle';
      toggle.setAttribute('role','button');
      toggle.setAttribute('aria-expanded', startCollapsed ? 'false' : 'true');
      toggle.setAttribute('tabindex','0');

      toggle.innerHTML = `
        <span class="kgsweb-icon">
          <i class="fa fa-folder" style="display:${startCollapsed ? 'inline-block':'none'}"></i>
          <i class="fa fa-folder-open" style="display:${startCollapsed ? 'none':'inline-block'}"></i>
        </span>
        <span class="kgsweb-label">${escapeHtml(node.name || 'Folder')}</span>
        ${toggleIcon(!startCollapsed)}
      `;

      li.appendChild(toggle);

      const childrenUl = document.createElement('ul');
      childrenUl.className = 'kgsweb-children';
      childrenUl.hidden = startCollapsed;

      (node.children || []).forEach(child => {
        childrenUl.appendChild(renderNode(child, depth+1, startCollapsed));
      });

      li.appendChild(childrenUl);

    } else {
      const link = document.createElement('a');
      link.className = 'kgsweb-file';
      link.href = fileLink(node);
      link.target = '_blank';
      link.rel = 'noopener';

      link.innerHTML = `
        <span class="kgsweb-icon">${iconFor(node)}</span>
        <span class="kgsweb-label">${escapeHtml(node.name || 'File')}</span>
      `;

      li.appendChild(link);
    }

    return li;
  }

  function setAllFolders(rootEl, expand=true) {
    if (!rootEl) return;

    const toggles = rootEl.querySelectorAll('.kgsweb-toggle');

    toggles.forEach(toggle => {
      const li = toggle.closest('.kgsweb-node');
      const panel = li?.querySelector(':scope > .kgsweb-children');
      if (!panel) return;

      panel.hidden = !expand;
      toggle.setAttribute('aria-expanded', String(expand));

      const closed = toggle.querySelector('.fa-folder:not(.fa-folder-open)');
      const open = toggle.querySelector('.fa-folder-open');

      if (closed && open) {
        closed.style.display = expand ? 'none' : 'inline-block';
        open.style.display = expand ? 'inline-block' : 'none';
      }

      const chevron = toggle.querySelector('.kgsweb-toggle-icon');
      if (chevron) {
        chevron.className =
          `fa ${expand ? 'fa-chevron-down' : 'fa-chevron-right'} kgsweb-toggle-icon`;
      }
    });
  }

  function injectExpandCollapseControls(container) {
    if (container.querySelector('.kgsweb-controls')) return;

    const controls = document.createElement('div');
    controls.className = 'kgsweb-controls';

    const expandBtn = document.createElement('button');
    expandBtn.type = 'button';
    expandBtn.textContent = 'Expand All';

    const collapseBtn = document.createElement('button');
    collapseBtn.type = 'button';
    collapseBtn.textContent = 'Collapse All';

    expandBtn.addEventListener('click', () => setAllFolders(container, true));
    collapseBtn.addEventListener('click', () => setAllFolders(container, false));

    controls.appendChild(expandBtn);
    controls.appendChild(collapseBtn);

    container.prepend(controls);
  }

  function attachToggles(rootEl) {
    rootEl.addEventListener('click', e => {
      const toggle = e.target.closest('.kgsweb-toggle');
      if (!toggle) return;

      const li = toggle.closest('.kgsweb-node');
      const panel = li?.querySelector(':scope > .kgsweb-children');
      if (!panel) return;

      const expanded = toggle.getAttribute('aria-expanded') === 'true';

      toggle.setAttribute('aria-expanded', String(!expanded));
      panel.hidden = expanded;

      const iconEl = toggle.querySelector('.kgsweb-icon');
      if (iconEl) {
        const closed = iconEl.querySelector('.fa-folder:not(.fa-folder-open)');
        const open = iconEl.querySelector('.fa-folder-open');

        if (closed && open) {
          closed.style.display = expanded ? 'inline-block' : 'none';
          open.style.display = expanded ? 'none' : 'inline-block';
        }
      }

      const chevron = toggle.querySelector('.kgsweb-toggle-icon');
      if (chevron) {
        chevron.className =
          `fa ${expanded ? 'fa-chevron-right' : 'fa-chevron-down'} kgsweb-toggle-icon`;
      }
    });

    rootEl.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        const toggle = e.target.closest('.kgsweb-toggle');
        if (toggle) {
          e.preventDefault();
          toggle.click();
        }
      }
    });
  }

  async function fetchTree(restUrl, rootId='') {
    const url = new URL(restUrl);
    if (rootId) url.searchParams.set('root', rootId);

    const res = await fetch(url.toString(), {credentials:'same-origin'});
    if (!res.ok) throw new Error(`Request failed: ${res.status}`);
    return res.json();
  }

  async function bootOne(container) {
    const startCollapsed = container.dataset.collapsed !== 'false';

    const loading = container.querySelector('.kgsweb-docs-loading');

    try {
      const rootId =
        container.getAttribute('data-root-id') ||
        (window.KGSwebFolders && window.KGSwebFolders.rootId) ||
        '';

      if (!rootId) throw new Error('No root folder specified');

      const data = await fetchTree(KGSwebFolders.restUrl, rootId);

      container.classList.add('kgsweb-ready');
      if (loading) loading.remove();

      const treeUl = document.createElement('ul');
      treeUl.className = 'kgsweb-tree';

      (data.tree || []).forEach(node => {
        treeUl.appendChild(renderNode(node, 0, startCollapsed));
      });

      const existing = container.querySelector('.kgsweb-tree');
      if (existing) existing.remove();

      container.appendChild(treeUl);

      injectExpandCollapseControls(container);
      attachToggles(container);

    } catch (err) {
      console.error(err);
    }
  }

  function bootAll() {
    $all(SELECTORS.container).forEach(bootOne);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAll);
  } else {
    bootAll();
  }

})();