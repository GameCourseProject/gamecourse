import {DomSanitizer, SafeUrl} from "@angular/platform-browser";
import {SecurityContext} from "@angular/core";

/**
 * This class is responsible for getting a resource URL. It ensures that
 * the correct resource is provided, even if the URL is unchanged. This
 * is done by appending a timestamp to the URL so that it overcomes
 * unwanted browser caching.
 *
 * It is most useful for non-static resources, like user profile pictures
 * and custom modules styles.
 *
 * It also has utility functions related to resources, like getting base64.
 */
export class ResourceManager {

  private url: string;
  private timestamp: number;    // Append to url to prevent caching from displaying uploaded resource

  constructor(private sanitizer: DomSanitizer) { }

  public get(format: 'SafeUrl' | 'URL' = 'SafeUrl'): SafeUrl | string {
    if (!this.url) return null;

    const safeURL = this.sanitize(this.url + (this.timestamp ? '?' + this.timestamp : ''));
    return format === 'SafeUrl' ? safeURL : this.sanitizer.sanitize(SecurityContext.URL, safeURL);
  }

  public set(resource: string | File) {
    if (!resource) {
      this.url = null;
      this.timestamp = null;
      return;
    }

    if (typeof resource === 'string') {  // resource URL
      this.url = resource;
      this.timestamp = (new Date()).getTime();

    } else {  // resource blob
      this.url = URL.createObjectURL(resource);
      this.timestamp = null;
    }
  }

  private sanitize(url: string): SafeUrl {
    return this.sanitizer.bypassSecurityTrustUrl(url);
  }

  public static getBase64(file: File): Promise<string | ArrayBuffer> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = () => resolve(reader.result);
      reader.onerror = error => reject(error);
    });
  }
}
