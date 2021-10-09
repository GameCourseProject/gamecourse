import { Injectable } from '@angular/core';
import {throwError} from "rxjs";
import {HttpErrorResponse} from "@angular/common/http";

@Injectable({
  providedIn: 'root'
})
export class ErrorService {

  private static _error: string;
  private static _callback: () => any;

  constructor() { }

  static get error(): string {
    return this._error;
  }

  private static set error(value: string) {
    this._error = value;
  }

  static get callback() {
    return this._callback;
  }

  private static set callback(value: () => any) {
    this._callback = value;
  }

  public static set(error: HttpErrorResponse | string, callback?: () => any): void {
    if (callback)
      this.callback = callback;

    this.error = error instanceof HttpErrorResponse ? error.error.text || error.error.error : error;
    throwError(error);
    console.error(error);
  }

  public static clear(): void {
    this.error = null;
    this.callback = null;
  }
}
