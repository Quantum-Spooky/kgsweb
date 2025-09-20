// kgsweb-documents.js
// Cleaned version: single boot, proper sorting, folders first

if (!window.KGSWEB) window.KGSWEB = {};
KGSWEB.Documents = {};
KGSWEB.Helpers = KGSWEB.Helpers || {};
KGSWEB.Format = KGSWEB.Format || {};

// --- Formatting helpers ---
KGSWEB.Format.sanitizeName = function(name){
    return (name || '').replace(/_/g, ' ');
};

KGSWEB.Format.extractDate = function(str){
    const match = str && str.match(/(\d{8})/);
    return match ? match[1] : null;
};

// --- Sorting ---
KGSWEB.Helpers.sortItems = function(items, opts={}){
    const mode = (opts.mode || 'alpha-asc').toLowerCase();
    const foldersFirst = opts.foldersFirst ?? true;

    return items.slice().sort((a,b)=>{
        if(foldersFirst){
            if(a.type==='folder' && b.type!=='folder') return -1;
            if(a.type!=='folder' && b.type==='folder') return 1;
        }

        let valA, valB;
        if(mode.startsWith('alpha')){
            valA = (a.name||'').toLowerCase();
            valB = (b.name||'').toLowerCase();
        } else if(mode.startsWith('date')){
            valA = KGSWEB.Format.extractDate(a.name) || '99999999';
            valB = KGSWEB.Format.extractDate(b.name) || '99999999';
        }

        let cmp = valA.localeCompare(valB, undefined, {sensitivity:'base'});
        if(mode.endsWith('-desc')) cmp *= -1;

        return cmp;
    });
};

// --- Tree Rendering ---
KGSWEB.Documents.renderNode = function(node, depth=0, sortMode='alpha-asc', foldersFirst=true){
    const li = document.createElement('li');
    li.className = node.type;

    let content = document.createElement('span');
    content.textContent = KGSWEB.Format.sanitizeName(node.name);
    li.appendChild(content);

    if(node.type==='folder' && node.children?.length){
        const ul = document.createElement('ul');
        const sorted = KGSWEB.Helpers.sortItems(node.children, {mode: sortMode, foldersFirst});
        sorted.forEach(child => ul.appendChild(KGSWEB.Documents.renderNode(child, depth+1, sortMode, foldersFirst)));
        li.appendChild(ul);
    }

    return li;
};

// --- Boot function ---
KGSWEB.Documents.bootAll = function(){
    const containers = document.querySelectorAll('.kgsweb-documents');
    containers.forEach(container=>{
        if(container.dataset.booted) return; // prevent double render
        container.dataset.booted = 'true';

        const rootId = container.getAttribute('data-root-id') || '';
        const sortMode = (container.getAttribute('data-sort-mode') || 'alpha-asc').toLowerCase();
        const foldersFirst = container.getAttribute('data-folders-first') !== 'false';

        fetch(`${KGSwebFolders.restUrl}?root=${rootId}`, {
            headers: {'X-WP-Nonce': KGSwebFolders.restNonce}
        })
        .then(resp => resp.json())
        .then(data => {
            container.innerHTML = '';
            if(!data.tree || !data.tree.length){
                container.textContent = 'No documents found.';
                return;
            }
            const ul = document.createElement('ul');
            const sorted = KGSWEB.Helpers.sortItems(data.tree, {mode: sortMode, foldersFirst});
            sorted.forEach(node => ul.appendChild(KGSWEB.Documents.renderNode(node,0,sortMode,foldersFirst)));
            container.appendChild(ul);
        })
        .catch(err => {
            console.error(err);
            container.textContent = 'Error loading documents.';
        });
    });
};

// --- Single boot ---
if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded', KGSWEB.Documents.bootAll);
}else{
    KGSWEB.Documents.bootAll();
}
