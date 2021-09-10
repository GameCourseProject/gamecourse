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

import {TypesConverter} from "../../_utils/types-converter";

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

  public createUser(data: { [key: string]: any} ) {
    const url = this.apiEndpoint.createUrl('users');

    const typesConverter = new TypesConverter();
    data = typesConverter.toDatabase(data);

    return this.post(url, data);
  }

  public updateUser(userID: number, data: { [key: string]: any }) {
    const url = this.apiEndpoint.createUrlWithPathVariables('users', [userID]);
    return this.post(url, data);
  }

  public deleteUser(userID: number) {
    const url = this.apiEndpoint.createUrl('users/' + userID + '/delete');
    return this.post(url, userID);
  }

  public getUserByID(userID: number): Observable<User> {
    const url = this.apiEndpoint.createUrlWithPathVariables('users', [userID]);
    return this.get(url).pipe(
      map(value => {
        const res: {[key: string]: any} = JSON.parse(JSON.stringify(value));
        return new User(res);
      }),
      catchError(error => throwError(error))
    )
  }

  public getUserByName(name: string): Observable<User> {
    const url = this.apiEndpoint.createUrlWithQueryParameters(
      'users',
      (qs: QueryStringParameters) => {
        qs.push('name', name);
      });

    return this.get(url).pipe(
      map(value => {
        const res: [ {[key: string]: any} ] = JSON.parse(JSON.stringify(value));
        if (res.length > 1) {
          throwError('There are more than one user with the name \'' + name + '\'');
          return null;
        }
        else return new User(res[0]);
      }),
      catchError(error => throwError(error))
    )
  }

  public getAllUsers(queries?: (queryStringParameters: QueryStringParameters) => void): Observable<User[]> {
    const url = this.apiEndpoint.createUrlWithQueryParameters('users', queries);
    return this.get(url).pipe(
      map(value => {
        const res: [ {[key: string]: any} ] = JSON.parse(JSON.stringify(value));
        const users: User[] = [];

        res.forEach(user => users.push(new User(user)));
        return users;
      }),
      catchError(error => throwError(error))
    );
  }

  public getAllUserCourses(userID: number, queries?: (queryStringParameters: QueryStringParameters) => void): Observable<Course[]> {
    const url = this.apiEndpoint.createUrlWithQueryParameters('users/' + userID + '/courses', queries);
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

  public getAllUserRoles(userID: number, queries?: (queryStringParameters: QueryStringParameters) => void): Observable<Role[]> {
    const url = this.apiEndpoint.createUrlWithQueryParameters('users/' + userID + '/roles', queries);
    return this.get(url).pipe(
      map(value => {
        const res: [ {[key: string]: any} ] = JSON.parse(JSON.stringify(value));
        const roles: Role[] = [];

        res.forEach(role => roles.push(new Role(role)));
        return roles;
      }),
      catchError(error => throwError(error))
    );
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Course related --------------- ***/
  /*** --------------------------------------------- ***/

  public createCourse(data: { [key: string]: any} ) {
    const url = this.apiEndpoint.createUrl('courses');

    const typesConverter = new TypesConverter();
    data = typesConverter.toDatabase(data);

    return this.post(url, data);
  }

  public updateCourse(courseID: number, data: { [key: string]: any }) {
    const url = this.apiEndpoint.createUrlWithPathVariables('courses', [courseID]);
    return this.post(url, data);
  }

  public deleteCourse(courseID: number) {
    const url = this.apiEndpoint.createUrl('courses/' + courseID + '/delete');
    return this.post(url, courseID);
  }

  public getCourseByID(courseID: number): Observable<Course> {
    const url = this.apiEndpoint.createUrlWithPathVariables('courses', [courseID]);
    return this.get(url).pipe(
      map(value => {
        const res: {[key: string]: any} = JSON.parse(JSON.stringify(value));
        return new Course(res);
      }),
      catchError(error => throwError(error))
    )
  }

  public getCourseByName(name: string): Observable<Course> {
    const url = this.apiEndpoint.createUrlWithQueryParameters(
      'courses',
      (qs: QueryStringParameters) => {
        qs.push('name', name);
      });

    return this.get(url).pipe(
      map(value => {
        const res: [ {[key: string]: any} ] = JSON.parse(JSON.stringify(value));
        if (res.length > 1) {
          throwError('There are more than one course with the name \'' + name + '\'');
          return null;
        }
        else return new Course(res[0]);
      }),
      catchError(error => throwError(error))
    )
  }

  public getAllCourses(queries?: (queryStringParameters: QueryStringParameters) => void): Observable<Course[]> {
    const url = this.apiEndpoint.createUrlWithQueryParameters('courses', queries);
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

  public getAllCourseStudents(courseID: number): Observable<User[]> {
    const url = this.apiEndpoint.createUrl('courses/' + courseID + '/students');
    return this.get(url).pipe(
      map(value => {
        const res: [ {[key: string]: any} ] = JSON.parse(JSON.stringify(value));
        const students: User[] = [];

        res.forEach(student => students.push(new User(student)));
        return students;
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
