import { Injectable } from '@angular/core';
import { HttpErrorResponse } from "@angular/common/http";
import { throwError } from "rxjs";

import { AlertService } from "./alert.service";

@Injectable({
  providedIn: 'root'
})
export class ErrorService {

  private static _error: {message: string, stack: string, full: string} = {
    message: null,
    stack: null,
    full: null
  };

  constructor() { }

  public static get error(): {message: string, stack: string, full: string} {
    return this._error;
  }

  private static set error(value: {message: string, stack: string, full: string}) {
    this._error = value;
  }

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
}
