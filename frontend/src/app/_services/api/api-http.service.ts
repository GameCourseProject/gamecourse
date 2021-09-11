import { Injectable } from '@angular/core';
import {HttpClient, HttpHeaders} from "@angular/common/http";

import {ApiEndpointsService} from "./api-endpoints.service";

import {Observable, of, throwError} from "rxjs";
import {catchError, map} from "rxjs/operators";
import {QueryStringParameters} from "../../_utils/query-string-parameters";

import {AuthType} from "../../_domain/AuthType";
import {Course} from "../../_domain/Course";
import {User} from "../../_domain/User";
import {Role} from "../../_domain/Role";
import {Form} from "@angular/forms";

@Injectable({
  providedIn: 'root'
})
export class ApiHttpService {

  private httpOptions = {
    headers: new HttpHeaders({}),
    withCredentials: true
  };

  constructor(
    private http: HttpClient,
    private apiEndpoint: ApiEndpointsService,
  ) { }


  /*** --------------------------------------------- ***/
  /*** ------------------- Setup ------------------- ***/
  /*** --------------------------------------------- ***/

  public doSetup(formData: FormData): Observable<boolean> {
    const url = this.apiEndpoint.createUrl('setup.php');

    return this.post(url, formData, this.httpOptions)
      .pipe( map((res: any) => res['setup']) );
  }

  public isSetupDone(): Observable<boolean> {
    const url = this.apiEndpoint.createUrl('login.php');

    return this.post(url, new FormData(), this.httpOptions)
      .pipe(
        map(res => true),
        catchError(error => {
          console.warn(error);
          if (error.status === 409)
            return of(false)
          return of(true);
        })
      );
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Authentication --------------- ***/
  /*** --------------------------------------------- ***/

  public doLogin(type: AuthType): void {
    const formData = new FormData();
    formData.append('loginType', type);

    const url = this.apiEndpoint.createUrl('login.php');

    this.post(url, formData, this.httpOptions)
      .pipe( map((res: any) => res['redirectURL']) )
      .subscribe(url => window.open(url,"_self"));
  }

  public isLoggedIn(): Observable<boolean> {
    const url = this.apiEndpoint.createUrl('login.php');

    return this.post(url, new FormData(), this.httpOptions)
      .pipe( map((res: any) => res['isLoggedIn']) );
  }

  public logout(): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('logout', true);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('login.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => res['isLoggedIn']) );
  }


  /*** --------------------------------------------- ***/
  /*** ---------------- User related --------------- ***/
  /*** --------------------------------------------- ***/

  public getLoggedUser(): Observable<User> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'getUserInfo');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map(
        (res: any) => User.fromDatabase(res['data']['userInfo'])
      ) );
  }

  public getUserActiveCourses(): Observable<Course[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'getUserActiveCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map(
        (res: any) => (res['data']['userActiveCourses'])
          .map(obj => Course.fromDatabase(obj))
      ) );
  }

  public getUserCourses(): Observable<Course[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'getCoursesList');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map(
        (res: any) => (res['data']['courses'])
          .map(obj => Course.fromDatabase(obj))
      ) );
  }

  public editSelfInfo(formData: FormData): Observable<any> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'editSelfInfo');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, formData, this.httpOptions).pipe();
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Course related --------------- ***/
  /*** --------------------------------------------- ***/


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
