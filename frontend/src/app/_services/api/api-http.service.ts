import { Injectable } from '@angular/core';
import {HttpClient} from "@angular/common/http";
import {ApiEndpointsService} from "./api-endpoints.service";
import {Observable, throwError} from "rxjs";
import {catchError, map} from "rxjs/operators";
import {QueryStringParameters} from "../../_utils/query-string-parameters";
import {Course} from "../../_domain/Course";

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
    const url = this.apiEndpointsService.createUrl('requiresSetup');
    return this.get(url).pipe(
      map(value => !!value),
      catchError(error => throwError(error))
    );
  }

  public doSetup(formData): Observable<ArrayBuffer> {
    const url = this.apiEndpointsService.createUrl('setup');
    return this.post(url, formData);
  }


  /*** --------------------------------------------- ***/
  /*** ---------------- User related --------------- ***/
  /*** --------------------------------------------- ***/

  public getAllUserActiveCourses(userID: number): Observable<Course[]> {
    const url = this.apiEndpointsService.createUrlWithQueryParameters(
      'users/' + userID + '/courses',
      (qs: QueryStringParameters) => {
        qs.push('active', true);
      }
    );

    return this.get(url).pipe(
      map(value => {
        const res: [ {[key: string]: any} ] = JSON.parse(JSON.stringify(value));
        const courses: Course[] = [];

        res.forEach(course => courses.push(new Course(course)));
        return courses;
      }),
      catchError(error => throwError(error))
    );
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
