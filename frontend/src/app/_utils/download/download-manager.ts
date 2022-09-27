import {ApiHttpService} from "../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../_services/api/api-endpoints.service";

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

  public static async downloadAsZip(path: string, api: ApiHttpService, courseID?: number): Promise<void> {
    location.replace(path);
    const cleanPath = path.replace(ApiEndpointsService.API_ENDPOINT, '');
    await api.cleanAfterDownloading(cleanPath, courseID).toPromise();
  }

  private static downloadHelper(filename: string, contents: string, type: DownloadTypes) {
    const el = document.createElement('a');
    el.setAttribute('href', contents);
    el.setAttribute('download', filename + '.' + type);
    el.style.display = 'none';
    document.body.appendChild(el);
    el.click();
    document.body.removeChild(el);
  }
}

enum DownloadTypes {
  TEXT = 'txt',
  CSV = 'csv',
  ZIP = 'zip'
}
