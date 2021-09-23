import { Injectable } from '@angular/core';
import {throwError} from "rxjs";
import {HttpErrorResponse} from "@angular/common/http";

@Injectable({
  providedIn: 'root'
})
export class ErrorService {

  private static error: string;

  constructor() { }

  public static get(): string {
    return this.error;
  }

  public static set(error: HttpErrorResponse | string): void {
    this.error = error instanceof HttpErrorResponse ? error.error.text || error.error.error : error;
    throwError(error);
    console.error(error);
  }

  public static clear(): void {
    this.error = null;
  }
}
