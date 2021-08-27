import { Injectable } from '@angular/core';
import {HttpClient} from "@angular/common/http";
import {ApiEndpointsService} from "./api-endpoints.service";
import {Observable, throwError} from "rxjs";
import {catchError, map} from "rxjs/operators";

@Injectable({
  providedIn: 'root'
})
export class ApiHttpService {

  constructor(
    private http: HttpClient,
    private apiEndpointsService: ApiEndpointsService,
  ) { }


  /*** --------------------------------------------- ***/
  /*** ------------------- Setup ------------------- ***/
  /*** --------------------------------------------- ***/

  public needsSetup(): Observable<boolean> {
    return this.get(this.apiEndpointsService.createUrl('requires_setup')).pipe(
      map(value => !!value),
      catchError(error => throwError(error))
    );
  }

  public doSetup(formData): Observable<ArrayBuffer> {
    return this.post(this.apiEndpointsService.createUrl('setup'), formData);
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Helper Functions ------------- ***/
  /*** --------------------------------------------- ***/

  public get(url: string, options?: any) {
    return this.http.get(url, options);
  }

  public post(url: string, data: any, options?: any) {
    return this.http.post(url, data, options);
  }

  public put(url: string, data: any, options?: any) {
    return this.http.put(url, data, options);
  }

  public delete(url: string, options?: any) {
    return this.http.delete(url, options);
  }

}
