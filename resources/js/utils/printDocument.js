// Capture the rendered invoice so print uses the same layout, locale and values.
// Resolved styles also keep the print window independent of Vite and Tailwind.
export function createPrintSnapshot(source, targetDocument) {
  const snapshot = targetDocument.importNode(source, true);
  const originals = [source, ...source.querySelectorAll('*')];
  const copies = [snapshot, ...snapshot.querySelectorAll('*')];
  const sourceWindow = source.ownerDocument.defaultView;

  originals.forEach((original, index) => {
    const copy = copies[index];
    const styles = sourceWindow.getComputedStyle(original);
    copy.removeAttribute('id');
    for (const property of styles) {
      copy.style.setProperty(property, styles.getPropertyValue(property));
    }
    copy.style.setProperty('animation', 'none');
    copy.style.setProperty('transition', 'none');
    copy.style.setProperty('print-color-adjust', 'exact');
    copy.style.setProperty('-webkit-print-color-adjust', 'exact');

    if (original.tagName === 'IMG') {
      copy.src = original.currentSrc || original.src;
      copy.loading = 'eager';
    }
    if (['TEXTAREA', 'INPUT', 'SELECT'].includes(original.tagName)) {
      const value = targetDocument.createElement('div');
      value.style.cssText = copy.style.cssText;
      value.style.whiteSpace = 'pre-wrap';
      value.style.overflowWrap = 'anywhere';
      value.style.height = 'auto';
      value.style.minHeight = styles.height;
      value.style.overflow = 'visible';
      value.textContent = original.tagName === 'SELECT'
        ? original.selectedOptions[0]?.textContent || ''
        : original.value;
      copy.replaceWith(value);
    }
  });

  snapshot.style.margin = '0';
  snapshot.style.boxShadow = 'none';
  snapshot.style.height = 'auto';
  snapshot.style.minHeight = sourceWindow.getComputedStyle(source).height;
  snapshot.style.overflow = 'visible';
  // Allow long invoices to continue onto following pages without splitting rows.
  snapshot.querySelectorAll('tr').forEach(row => {
    row.style.breakInside = 'avoid';
    row.style.pageBreakInside = 'avoid';
  });
  return snapshot;
}

export async function waitForPrintAssets(root) {
  const doc = root.ownerDocument || root;
  await Promise.all(Array.from(root.querySelectorAll('img'), image => {
    if (image.complete) return Promise.resolve();
    return new Promise(resolve => {
      image.addEventListener('load', resolve, { once: true });
      image.addEventListener('error', resolve, { once: true });
    });
  }));
  if (doc.fonts) await doc.fonts.ready;
}

// All print actions use a snapshot of a rendered Vue component.
export async function printVueDocument(source, { printWindow, title, pageSize = 'A4', margin = '14mm', buttonLabel }) {
  await waitForPrintAssets(source);
  if (printWindow.closed) return;
  const doc = printWindow.document;
  doc.documentElement.lang = source.ownerDocument.documentElement.lang;
  doc.title = title;
  const style = doc.createElement('style');
  style.textContent = '@page { size: ' + pageSize + '; margin: ' + margin + '; } body { margin: 0; background: white; } @media print { .document-print-toolbar { display: none !important; } }';
  doc.head.append(style);
  doc.body.replaceChildren(createPrintSnapshot(source, doc));
  const toolbar = doc.createElement('div');
  toolbar.className = 'document-print-toolbar';
  toolbar.style.cssText = 'padding:12px;text-align:center;background:#e2e8f0;position:sticky;top:0;z-index:1';
  const button = doc.createElement('button');
  button.type = 'button';
  button.textContent = buttonLabel;
  button.style.cssText = 'padding:8px 16px;border:0;border-radius:6px;background:#2563eb;color:white;cursor:pointer';
  button.onclick = () => { printWindow.focus(); printWindow.print(); };
  toolbar.append(button);
  doc.body.prepend(toolbar);
  await waitForPrintAssets(doc);
  if (!printWindow.closed) { printWindow.focus(); printWindow.print(); }
}
