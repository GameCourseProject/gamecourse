import { Injectable } from '@angular/core';
import { HttpErrorResponse } from "@angular/common/http";
import { throwError } from "rxjs";

import { AlertService } from "./alert.service";

@Injectable({
  providedIn: 'root'
})
export class ErrorService {

  private static _viewId = null;

  private static _error: {message: string, stack: string, full: string} = {
    message: null,
    stack: null,
    full: null
  };

  constructor() { }

  /*** ---------------------------------------------------- ***/
  /*** ----------------- Setters/Getters ------------------ ***/
  /*** ---------------------------------------------------- ***/

  public static get error(): {message: string, stack: string, full: string} {
    return this._error;
  }

  private static set error(value: {message: string, stack: string, full: string}) {
    this._error = value;
  }

  public static get viewId(): number {
    return this._viewId;
  }

  private static set viewId(value: number) {
    this._viewId = value;
  }

  /*** ---------------------------------------------------- ***/
  /*** --------------------- Actions ---------------------- ***/
  /*** ---------------------------------------------------- ***/

  public static set(error: HttpErrorResponse | string): void {
    this.clear();

    if (error instanceof HttpErrorResponse) { // server error
      this.error.full = error.error.text ?? error.error.error;

      // Extract error message and stack trace
      const matchFatalError = /<b>Fatal error<\/b>:\s*(.*?)\s+in\s+(.*)/s.exec(this.error.full);
      if (matchFatalError) {
        this.error.message = matchFatalError[1];
        this.error.stack = matchFatalError[2].replaceAll('\n', '<br>');
      } else {
        // If no fatal error format matches, fallback to the full error message
        this.error.message = this.error.full;
        this.error.stack = '';
      }

    } else { // simple string
      this.error.message = error;
      this.error.stack = null;
      this.error.full = error;
    }

    throwError(error);
    console.error(this.error.full);
    AlertService.showErrorAlert(this.error);
  }

  public static clear(): void {
    this.error = {
      message: null,
      stack: null,
      full: null
    }
  }

  /*** ---------------------------------------------------- ***/
  /*** ------------------- View Editor -------------------- ***/
  /*** ---------------------------------------------------- ***/

  public static setInViewEditor(error: HttpErrorResponse | string): void {
    this.clear();

    if (error instanceof HttpErrorResponse) { // server error
      this.error.full = error.error.text ?? error.error.error;

      // Extract error message and stack trace
      const matchFatalError = /<b>(?:Fatal error|Warning)<\/b>:\s*(.*?)\s+in\s+(.*)/s.exec(this.error.full);
      if (matchFatalError) {
        this.error.message = matchFatalError[1].replace("Uncaught Exception", "Error");

        // Extract and remove the trailing .&lt;number&gt; if it exists
        const matchTrailingNumber = /&lt;(\d+)&gt;$/.exec(this.error.message);
        if (matchTrailingNumber) {
          this.viewId = +matchTrailingNumber[1];
          this.error.message = this.error.message.replace(matchTrailingNumber[0], '');
        }
      }
      else {
        this.error.message = "Error: The application has encountered an unknown error. If this persists, please consider contacting an admin with an error report.";
        this.error.stack = this.error.full;
      }

    } else { // simple string
      this.error.message = "Error: The application has encountered an unknown error. If this persists, please consider contacting an admin with an error report.";
      this.error.stack = error;
    }

    AlertService.showErrorAlert(this.error);
  }

  public static clearView(): void {
    this.viewId = null;
  }

}
