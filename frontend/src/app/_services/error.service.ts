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
      let matches = this.error.full.matchAll(/<b>Fatal error<\/b>:(\w|\s)+:(.*) in ((.|\s)+)/g);
      for (const match of matches) { this.error.message = match[2]; }
      matches = this.error.full.matchAll(/Stack trace:(.|\s)+ on line(.|\s)+<\/b>/g);
      for (const match of matches) { this.error.stack = match[0].replaceAll('\n', '<br>'); }

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
