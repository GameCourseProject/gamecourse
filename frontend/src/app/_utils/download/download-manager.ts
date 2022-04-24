/**
 * This class has utility functions used to download various
 * types of files.
 */
export class DownloadManager {

  public static downloadAsText(filename: string, contents: string): void {
    this.downloadHelper(filename, contents, DownloadTypes.TEXT);
  }

  public static downloadAsCSV(filename: string, contents: string): void {
    this.downloadHelper(filename, contents, DownloadTypes.CSV);
  }

  public static downloadAsZip(filename: string, path: string): void {
    if (path.substr(path.length - 1) !== '/')
      path = path + '/';

    const zip = path + filename;
    location.replace(zip);

    // FIXME: should delete temporary zip file in server
  }

  private static downloadHelper(filename: string, contents: string, type: DownloadTypes) {
    const el = document.createElement('a');
    el.setAttribute('href', contents);
    el.setAttribute('download', filename + '.' + type);
    el.style.display = 'none';
    document.body.appendChild(el);
    el.click();
    document.body.removeChild(el);

    // FIXME: should delete temporary zip file in server
  }
}

enum DownloadTypes {
  TEXT = 'txt',
  CSV = 'csv',
  ZIP = 'zip'
}
