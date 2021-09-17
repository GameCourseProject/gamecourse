export function downloadAsCSV(filename: string, contents: string): void {
  const el = document.createElement('a');
  el.setAttribute('href', contents);
  el.setAttribute('download', filename + '.csv');
  el.style.display = 'none';
  document.body.appendChild(el);
  el.click();
  document.body.removeChild(el);
}
