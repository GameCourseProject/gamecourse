/**
 * This class has utility functions used to download various
 * types of files.
 */
export class DownloadManager {

  public static downloadAsCSV(filename: string, contents: string): void {
    const el = document.createElement('a');
    el.setAttribute('href', contents);
    el.setAttribute('download', filename + '.csv');
    el.style.display = 'none';
    document.body.appendChild(el);
    el.click();
    document.body.removeChild(el);
  }

  public static downloadAsZip(filename: string, path: string): void {
    if (path.substr(path.length - 1) !== '/')
      path = path + '/';

    const zip = path + filename;
    location.replace(zip);
  }
}

// FIXME: add zip download
