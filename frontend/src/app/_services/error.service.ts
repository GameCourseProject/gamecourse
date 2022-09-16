import { Injectable } from '@angular/core';
import { HttpErrorResponse } from "@angular/common/http";
import { throwError } from "rxjs";

import { AlertService, AlertType } from "./alert.service";

@Injectable({
  providedIn: 'root'
})
export class ErrorService {

  private static _error: {message: string, stack: string, full: string} = {
    message: null,
    stack: null,
    full: null
  };
  private static _callback: () => any;

  constructor() { }

  static get error(): {message: string, stack: string, full: string} {
    return this._error;
  }

  private static set error(value: {message: string, stack: string, full: string}) {
    this._error = value;
  }

  static get callback() {
    return this._callback;
  }

  private static set callback(value: () => any) {
    this._callback = value;
  }

  public static set(error: HttpErrorResponse | string, callback?): void {
    if (callback)
      this.callback = callback;

    if (error instanceof HttpErrorResponse) { // http error
      this.error.full = error.error.text ?? error.error.error;
      let matches = this.error.full.matchAll(/<b>Fatal error<\/b>:(\w|\s)+:(.*) in ((.|\s)+)/g);
      for (const match of matches) { this.error.message = match[2]; }
      matches = this.error.full.matchAll(/Stack trace:(.|\s)+/g);
      for (const match of matches) { this.error.stack = match[0]; }

    } else { // simple string
      this.error.message = error;
      this.error.stack = null;
      this.error.full = error;
      AlertService.showAlert(AlertType.ERROR, this.error.message, 12000);
    }

    throwError(error);
    console.error(this.error.full);
  }

  public static clear(): void {
    this.error.message = null;
    this.error.stack = null;
    this.error.full = null;
    this.callback = null;
  }
}
