import {Injectable} from '@angular/core';
import {HttpClient, HttpHeaders} from "@angular/common/http";
import {Observable, of} from "rxjs";
import {catchError, map} from "rxjs/operators";

import {ApiEndpointsService} from "./api-endpoints.service";

import {QueryStringParameters} from "../../_utils/query-string-parameters";

import {AuthType} from "../../_domain/AuthType";
import {Course, CourseInfo} from "../../_domain/Course";
import {User} from "../../_domain/User";
import {CreationMode} from "../../_domain/CreationMode";
import {SetupData} from "../../_views/setup/setup/setup.component";
import {UserData} from "../../_views/my-info/my-info/my-info.component";
import {CourseData, ImportCoursesData} from "../../_views/courses/courses/courses.component";
import {ImportUsersData} from "../../_views/users/users/users.component";
import {Module} from "../../_domain/Module";
import {ImportModulesData} from "../../_views/settings/modules/modules.component";
import {Moment} from "moment";
import * as moment from "moment";
import {Role, RoleDatabase} from "../../_domain/Role";
import {Page} from "../../_domain/Page";

@Injectable({
  providedIn: 'root'
})
export class ApiHttpService {

  private httpOptions = {
    headers: new HttpHeaders(),
    withCredentials: true
  };

  constructor(
    private http: HttpClient,
    private apiEndpoint: ApiEndpointsService,
  ) { }


  /*** --------------------------------------------- ***/
  /*** ------------------- Setup ------------------- ***/
  /*** --------------------------------------------- ***/

  public doSetup(data: SetupData): Observable<boolean> {
    const formData = new FormData();
    formData.append('course-name', data.courseName);
    formData.append('course-color', data.courseColor);
    formData.append('teacher-id', data.teacherId.toString());
    formData.append('teacher-username', data.teacherUsername);

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
          console.warn(error.error.error);
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
        (res: any) => (res['data']['userActiveCourses']).map(obj => Course.fromDatabase(obj))
      ) );
  }

  public getUserCourses(): Observable<{courses: Course[], myCourses: boolean}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'getCoursesList');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => {
          return {
            courses: (res['data']['courses']).map(obj => Course.fromDatabase(obj)),
            myCourses: res['data']['myCourses']
          }
        }
      ) );
  }

  public editSelfInfo(userData: UserData): Observable<any> {
    const data = {
      userName: userData.name,
      userNickname: userData.nickname,
      userStudentNumber: userData.studentNumber,
      userEmail: userData.email,
      userAuthService: userData.auth,
      userUsername: userData.username,
      userHasImage: !!userData.image,
      userImage: userData.image
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'editSelfInfo');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions).pipe();
  }

  public getUsers(): Observable<User[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'users');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => (res['data']['users']).map(obj => User.fromDatabase(obj))) );
  }

  public setUserAdmin(userID: number, isAdmin: boolean): Observable<void> {
    const data = {
      "user_id": userID,
      "isAdmin": isAdmin ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'setUserAdmin');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setUserActive(userID: number, isActive: boolean): Observable<void> {
    const data = {
      "user_id": userID,
      "isActive": isActive ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'setUserActive');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public createUser(userData: UserData): Observable<User> {
    const data = {
      userName: userData.name,
      userStudentNumber: userData.studentNumber,
      userNickname: userData.nickname,
      userUsername: userData.username,
      userEmail: userData.email,
      userMajor: userData.major,
      userIsActive: userData.isActive ? 1 : 0,
      userIsAdmin: userData.isAdmin ? 1 : 0,
      userAuthService: userData.auth,
      userHasImage: !!userData.image,
      userImage: userData.image,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'createUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => User.fromDatabase(res['data']['user'])) );
  }

  public editUser(userData: UserData): Observable<void> {
    const data = {
      userId: userData.id,
      userName: userData.name,
      userStudentNumber: userData.studentNumber,
      userNickname: userData.nickname,
      userUsername: userData.username,
      userEmail: userData.email,
      userMajor: userData.major,
      userIsActive: userData.isActive ? 1 : 0,
      userIsAdmin: userData.isAdmin ? 1 : 0,
      userAuthService: userData.auth,
      userHasImage: !!userData.image,
      userImage: userData.image,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'editUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteUser(userID: number): Observable<void> {
    const data = { user_id: userID };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'deleteUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public exportUsers(): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'exportUsers');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, null, this.httpOptions)
      .pipe( map((res: any) => 'data:text/csv;charset=utf-8,' + encodeURIComponent(res['data']['users'])) );
  }

  public importUsers(importData: ImportUsersData): Observable<number> {
    const data = {
      file: importData.file,
      replace: importData.replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'importUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => parseInt(res['data']['nUsers'])) );
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Course related --------------- ***/
  /*** --------------------------------------------- ***/

  public getCourse(courseID: number): Observable<Course> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'getCourse');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => Course.fromDatabase(res['data']['course'])) );
  }

  public getCourseInfo(courseID: number): Observable<CourseInfo> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'getCourseInfo');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => res['data']));
  }

  public getCourseUsers(courseID: number): Observable<User[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'courseUsers');
      qs.push('course', courseID);
      qs.push('role', 'allRoles');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => (res['data']['userList']).map(obj => User.fromDatabase(obj))) );
  }

  public getNotCourseUsers(courseID: number): Observable<User[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'notCourseUsers');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => (res['data']['notCourseUsers']).map(obj => User.fromDatabase(obj))) );
  }

  public getCourseRoles(courseID: number): Observable<Role[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'courseRoles');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => (res['data']['courseRoles']).map(role => Role.fromDatabase(role))) );
  }

  public setCourseActive(courseID: number, isActive: boolean): Observable<void> {
    const data = {
      "course_id": courseID,
      "active": isActive ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'setCoursesActive');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setCourseVisible(courseID: number, isVisible: boolean): Observable<void> {
    const data = {
      "course_id": courseID,
      "visibility": isVisible ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'setCoursesvisibility');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setCourseUserActive(courseID: number, userID: number, isActive: boolean): Observable<void> {
    const data = {
      "course": courseID,
      "userId": userID,
      "isActive": isActive ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'activeUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public createCourse(courseData: CourseData, creationMode: CreationMode = CreationMode.BLANk): Observable<Course> {
    const data = {
      courseName: courseData.name + (creationMode === CreationMode.SIMILAR ? ' - Copy' : ''),
      creationMode: creationMode,
      courseShort: courseData.short,
      courseYear: courseData.year,
      courseColor: courseData.color,
      courseIsVisible: courseData.isVisible ? 1 : 0,
      courseIsActive: courseData.isActive ? 1 : 0,
    }

    if (creationMode === CreationMode.SIMILAR)
      data['copyFrom'] = courseData.id;

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'createCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => Course.fromDatabase(res['data']['course'])) );
  }

  public duplicateCourse(courseData: CourseData): Observable<Course> {
    return this.createCourse(courseData, CreationMode.SIMILAR);
  }

  public editCourse(courseData: CourseData): Observable<void> {
    const data = {
      course: courseData.id,
      courseName: courseData.name,
      courseShort: courseData.short,
      courseYear: courseData.year,
      courseColor: courseData.color,
      courseIsVisible: courseData.isVisible ? 1 : 0,
      courseIsActive: courseData.isActive ? 1 : 0,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'editCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteCourse(courseID: number): Observable<void> {
    const data = { course: courseID };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'deleteCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public exportCourses(courseID: number = null, options = null): Observable<string> {
   const data = {
     id: courseID,
     options: options
   }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'exportCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res['data']['courses']) );
  }

  public importCourses(importData: ImportCoursesData): Observable<number> {
    const data = {
      file: importData.file,
      replace: importData.replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'importCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => parseInt(res['data']['nCourses'])) );
  }

  public addUsersToCourse(courseID: number, users: User[], role: string): Observable<void> {
    const data = {
      course: courseID,
      role,
      users: users.map(user => {
        return { id: user.id, name: user.name, studentNumber: user.studentNumber }
      }),
    }
    console.log(data)

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'addUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public createCourseUser(courseID: number, userData: UserData): Observable<void> {
    const data = {
      course: courseID,
      userName: userData.name,
      userStudentNumber: userData.studentNumber,
      userNickname: userData.nickname,
      userUsername: userData.username,
      userEmail: userData.email,
      userMajor: userData.major,
      userRoles: userData.roles,
      userAuthService: userData.auth,
      userHasImage: !!userData.image,
      userImage: userData.image,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'createUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public editCourseUser(courseID: number, userData: UserData): Observable<void> {
    const data = {
      course: courseID,
      userId: userData.id,
      userName: userData.name,
      userStudentNumber: userData.studentNumber,
      userNickname: userData.nickname,
      userUsername: userData.username,
      userEmail: userData.email,
      userMajor: userData.major,
      userRoles: userData.roles,
      userAuthService: userData.auth,
      userHasImage: !!userData.image,
      userImage: userData.image,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'editUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteCourseUser(courseID: number, userID: number): Observable<void> {
    const data = { course: courseID, user_id: userID };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'removeUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public exportCourseUsers(courseID: number): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'exportUsers');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, null, this.httpOptions)
      .pipe( map((res: any) => 'data:text/csv;charset=utf-8,' + encodeURIComponent(res['data']['courseUsers'])) );
  }

  public importCourseUsers(courseID: number, importData: ImportUsersData): Observable<number> {
    const data = {
      course: courseID,
      file: importData.file,
      replace: importData.replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'course');
      qs.push('request', 'importUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => parseInt(res['data']['nUsers'])) );
  }

  public hasViewsEnabled(courseID: number): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'courseTabs');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => !!res['data'].find(el => el.text === 'Views')) );
  }


  /*** --------------------------------------------- ***/
  /*** ---------------- Rules System --------------- ***/
  /*** --------------------------------------------- ***/

  public getRulesSystemLastRun(courseID: number): Observable<Moment> {
    return this.getCourseInfo(courseID)
      .pipe( map((res: CourseInfo) => moment(res.ruleSystemLastRun).subtract(1, 'hours')) );
  }


  /*** --------------------------------------------- ***/
  /*** ------------- General Settings -------------- ***/
  /*** --------------------------------------------- ***/

  public getSettingsGlobal(): Observable<{theme: string, themes: {name: string, preview: boolean}[]}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'global');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getSettingsModules(): Observable<Module[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'modules');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => res['data'].map(module => Module.fromDatabase(module))) );
  }

  public importModule(importData: ImportModulesData): Observable<void> {
    const data = {
      file: importData.file,
      fileName: importData.fileName
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'importModule');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public exportModules(): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'core');
      qs.push('request', 'exportModule');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, null, this.httpOptions)
      .pipe( map((res: any) => res['data']['file']) );
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Course Settings -------------- ***/
  /*** --------------------------------------------- ***/

  public getStyleFile(courseID: number): Observable<{styleFile: string, url: string}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'getStyleFile');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => { return {styleFile: res['data']['styleFile'] === false ? '' : res['data']['styleFile'], url: res['data']['url']} }) );
  }

  public getCourseGlobal(courseID: number): Observable<{ name: string, activeUsers: number, awards: number, participations: number }> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'courseGlobal');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => {
        return {
          name: res['data']['name'],
          activeUsers: res['data']['activeUsers'],
          awards: res['data']['awards'],
          participations: res['data']['participations'],
        };
      }) );
  }

  public getTableData(courseID: number, table: string): Observable<{entries: any[], columns: any}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'getTableData');
      qs.push('course', courseID);
      qs.push('table', table);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getRoles(courseID: number): Observable<{ pages: Page[], roles: Role[], rolesHierarchy: Role[] }> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'roles');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => {
        const allRoles: Role[] = res['data']['roles_obj'].map(obj => Role.fromDatabase(obj));
        const roles = parseRoles(res['data']['rolesHierarchy'], allRoles);
        return {pages: res['data']['pages'].map(obj => Page.fromDatabase(obj)), roles: allRoles, rolesHierarchy: roles}
      }) );

    function parseRoles(hierarchy: RoleDatabase[], allRoles: Role[]): Role[] {
      return hierarchy.map(obj => {
        const role = allRoles.find(el => el.id === parseInt(obj.id));
        if (obj.children) {
          role.children = parseRoles(obj.children, allRoles);
        }
        return role;
      })
    }
  }

  public saveRoles(courseID: number, roles: Role[], hierarchy: any): Observable<void> {
    const data = {
      course: courseID,
      updateRoleHierarchy: true,
      hierarchy,
      roles: roles.map(role => {
        return {name: role.name, id: role.id ? role.id.toString() : null, landingPage: role.landingPage}
      })
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'roles');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public createStyleFile(courseID: number): Observable<string> {
    const data = {
      course: courseID
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'createStyleFile');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res['data']['url']) );
  }

  public updateStyleFile(courseID: number, content: string): Observable<string> {
    const data = {
      course: courseID,
      content: content
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'updateStyleFile');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res['data']['url']) );
  }

  public getCourseModules(courseID: number): Observable<Module[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'courseModules');
      qs.push('course', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => res['data'].map(module => Module.fromDatabase(module))) );
  }

  public setModuleEnabled(courseID: number, moduleID: string, isEnabled: boolean): Observable<void> {
    const data = {
      "course": courseID,
      "module": moduleID,
      "enabled": isEnabled
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', 'settings');
      qs.push('request', 'saveCourseModule');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, this.httpOptions)
      .pipe( map((res: any) => res) );
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Docs -------------------- ***/
  /*** --------------------------------------------- ***/

  // FIXME: finish up when can enable modules
  public getSchema() {
    const params = (qs: QueryStringParameters) => {
      qs.push('list', true);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('docs/functions/getSchema.php', params);

    return this.get(url, this.httpOptions)
      .pipe( map((res: any) => res[0]) )
      .subscribe(courseID => {  // vai buscar o primeiro course identificado pelo id

        const params = (qs: QueryStringParameters) => {
          qs.push('course', courseID);
        };

        const url = this.apiEndpoint.createUrlWithQueryParameters('docs/functions/getSchema.php', params);

        return this.get(url, this.httpOptions)
          .pipe( map((res: any) => res[0]) )
          .subscribe(libraries => {
            console.log(libraries)
          })
      });
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
