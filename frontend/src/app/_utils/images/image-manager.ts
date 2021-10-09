/**
 * This class is responsible for getting an image URL. It ensures that
 * the correct image is displayed, even if the URL is unchanged. This
 * is done by appending a timestamp to the URL so that it overcomes
 * unwanted browser caching.
 *
 * It is most useful for non-static images, like user profile pictures.
 *
 * It also has utility functions related to images.
 */
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";

export class ImageManager {

  private url: string;
  private timestamp: number;    // Append to url to prevent caching from displaying uploaded image

  constructor(private sanitizer: DomSanitizer) { }

  public get(): SafeUrl {
    if (!this.url) return null;

    if (this.timestamp)
      return this.sanitize(this.url + '?' + this.timestamp);

    return this.sanitize(this.url);
  }

  public set(img: string | File) {
    if (!img) {
      this.url = null;
      this.timestamp = null;
      return;
    }

    if (typeof img === 'string') {  // img URL
      this.url = img;
      this.timestamp = (new Date()).getTime();

    } else {  // blob
      this.url = URL.createObjectURL(img);
      this.timestamp = null;
    }
  }

  public static getBase64(file: File): Promise<string | ArrayBuffer> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = () => resolve(reader.result);
      reader.onerror = error => reject(error);
    });
  }

  private sanitize(url: string): SafeUrl {
    return this.sanitizer.bypassSecurityTrustUrl(url);
  }

}
