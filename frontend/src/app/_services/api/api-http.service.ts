import {Injectable} from '@angular/core';
import {HttpClient, HttpHeaders, HttpXhrBackend} from "@angular/common/http";
import {Observable, throwError} from "rxjs";
import {catchError, map} from "rxjs/operators";

import {ApiEndpointsService} from "./api-endpoints.service";

import {QueryStringParameters} from "../../_utils/api/query-string-parameters";

import {Notification} from "../../_domain/notifications/notification";
import {AuthType} from "../../_domain/auth/auth-type";
import {Course} from "../../_domain/courses/course";
import {User} from "../../_domain/users/user";
import {CourseManageData, ImportCoursesData} from "../../_views/restricted/courses/courses/courses.component";
import {UserManageData} from "../../_views/restricted/users/users/users.component";
import {Module} from "../../_domain/modules/module";
import {ImportModulesData} from "../../_views/restricted/settings/modules/modules.component";
import {Moment} from "moment/moment";
import {Role} from "../../_domain/roles/role";
import {Page} from "../../_domain/views/pages/page";
import {Template} from "../../_domain/views/templates/template";
import {RoleType} from "../../_domain/roles/role-type";
import {View} from "../../_domain/views/view";
import {buildView} from "../../_domain/views/build-view/build-view";
import {dateFromDatabase, exists} from "../../_utils/misc/misc";
import {
  ConfigInputItem,
  ConfigSection,
  List, PersonalizedConfig
} from "../../_views/restricted/courses/course/settings/modules/config/config/config.component";
import {Tier} from "../../_domain/modules/config/personalized-config/skills/tier";
import {
  TierManageData,
  SkillManageData
} from "../../_views/restricted/courses/course/settings/modules/config/personalized-config/skills/skills.component";
import {Skill} from "../../_domain/modules/config/personalized-config/skills/skill";
import {ContentItem} from "../../_components/modals/file-picker-modal/file-picker-modal.component";
import {
  Credentials, GoogleSheetsConfig,
} from "../../_views/restricted/courses/course/settings/modules/config/personalized-config/googlesheets/googlesheets.component";
import {
  ProgressReportConfig
} from "../../_views/restricted/courses/course/settings/modules/config/personalized-config/progress-report/progress-report.component";
import {
  ProfilingHistory,
  ProfilingNode
} from "../../_views/restricted/courses/course/settings/modules/config/personalized-config/profiling/profiling.component";
import {ErrorService} from "../error.service";
import {Router} from "@angular/router";
import {CourseUser} from "../../_domain/users/course-user";
import {Action} from "../../_domain/modules/config/Action";
import {SkillTree} from "../../_domain/modules/config/personalized-config/skills/skill-tree";
import {CourseUserManageData} from "../../_views/restricted/courses/course/settings/users/users.component";
import { Theme } from '../theming/themes-available';
import {
  QRCode,
  QRParticipation
} from 'src/app/_views/restricted/courses/course/settings/modules/config/personalized-config/qr/qr.component';
import {SetupData} from "../../_views/setup/setup/setup.component";
import {
  DataSourceStatus
} from "../../_views/restricted/courses/course/settings/modules/config/data-source-status/data-source-status.component";
import { Streak } from 'src/app/_views/restricted/courses/course/page/page.component';

@Injectable({
  providedIn: 'root'
})
export class ApiHttpService {

  private static readonly httpOptions = {
    headers: new HttpHeaders(),
    withCredentials: true
  };

  static readonly AUTOGAME: string = 'AutoGame';
  static readonly CORE: string = 'Core';
  static readonly COURSE: string = 'Course';
  static readonly DOCS: string = 'Docs';
  static readonly MODULE: string = 'Module';
  static readonly THEME: string = 'Theme';
  static readonly USER: string = 'User';
  static readonly PAGE: string = 'Page';
  static readonly NOTIFICATION_SYSTEM: string = 'Notification';
  // NOTE: insert here new controllers & update cache dependencies

  static readonly GOOGLESHEETS: string = 'GoogleSheets';
  static readonly PROGRESS_REPORT: string = 'ProgressReport';
  static readonly PROFILING: string = 'Profiling';
  static readonly QR: string = 'QR';
  static readonly SKILLS: string = 'Skills';
  static readonly VIRTUAL_CURRENCY: string = 'VirtualCurrency';
  // FIXME: should be compartimentalized


  constructor(
    private http: HttpClient,
    private apiEndpoint: ApiEndpointsService,
    private router: Router
  ) { }


  /*** --------------------------------------------- ***/
  /*** ------------------- Setup ------------------- ***/
  /*** --------------------------------------------- ***/

  public doSetup(setupData: SetupData): Observable<boolean> {
    const formData = new FormData();
    formData.append('course-name', setupData.courseName);
    formData.append('course-color', setupData.courseColor);
    formData.append('admin-id', setupData.adminId.toString());
    formData.append('admin-username', setupData.adminUsername);

    const url = this.apiEndpoint.createUrl('setup/setup.php');

    return this.post(url, formData, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['setup']) );
  }

  public isSetupDone(): Observable<boolean> {
    const url = this.apiEndpoint.createUrl('setup/setup.php');

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['isSetupDone']) );
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Core ------------------- ***/
  /*** --------------------------------------------- ***/

  public cleanAfterDownloading(path: string, courseID?: number): Observable<void> {
    const data = { path }
    if (courseID) data['courseId'] = courseID;

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.CORE);
      qs.push('request', 'cleanAfterDownloading');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }



  /*** --------------------------------------------- ***/
  /*** -------------- Authentication --------------- ***/
  /*** --------------------------------------------- ***/

  public doLogin(type: AuthType): void {
    const formData = new FormData();
    formData.append('loginType', type);

    const url = this.apiEndpoint.createUrl('auth/login.php');

    this.post(url, formData, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['redirectURL']) )
      .subscribe(url => window.open(url,"_self"));
  }

  public isLoggedIn(): Observable<boolean> {
    const url = this.apiEndpoint.createUrl('auth/login.php');

    return this.get(url, ApiHttpService.httpOptions, true)
      .pipe( map((res: any) => res['isLoggedIn']) );
  }

  public logout(): Observable<boolean> {
    const formData = new FormData();
    formData.append('logout', '');

    const url = this.apiEndpoint.createUrl('auth/login.php');

    return this.post(url, formData, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['isLoggedIn']) );
  }



  /*** --------------------------------------------- ***/
  /*** -------------------- User ------------------- ***/
  /*** --------------------------------------------- ***/

  // Logged User
  public getLoggedUser(): Observable<User> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'getLoggedUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => User.fromDatabase(res['data'])) );
  }


  // General
  public getUserById(userID: number): Observable<User> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'getUserById');
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (User.fromDatabase(res['data'])) ));
  }

  public getUsers(isActive?: boolean, isAdmin?: boolean): Observable<User[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'getUsers');
      if (isActive !== undefined) qs.push('isActive', isActive);
      if (isAdmin !== undefined) qs.push('isAdmin', isAdmin);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']).map(obj => User.fromDatabase(obj))) );
  }

  public getUserCourses(userID: number, isActive?: boolean, isVisible?: boolean): Observable<Course[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'getUserCourses');
      qs.push('userId', userID);
      if (isActive !== undefined && isActive !== null) qs.push('isActive', isActive);
      if (isVisible !== undefined && isVisible !== null) qs.push('isVisible', isVisible);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => Course.fromDatabase(obj))) );
  }


  // User Manipulation
  public createUser(userData: UserManageData): Observable<User> {
    const data = {
      name: userData.name,
      studentNumber: userData.studentNr,
      nickname: userData.nickname,
      username: userData.username,
      email: userData.email,
      major: userData.major,
      authService: userData.authService,
      image: userData.photoBase64
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'createUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => User.fromDatabase(res['data'])) );
  }

  public editUser(userData: UserManageData): Observable<User> {
    const data = {
      userId: userData.id,
      name: userData.name,
      studentNumber: userData.studentNr,
      nickname: userData.nickname,
      username: userData.username,
      email: userData.email,
      major: userData.major,
      authService: userData.authService,
      image: userData.photoBase64
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'editUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => User.fromDatabase(res['data'])) );
  }

  public deleteUser(userID: number): Observable<void> {
    const data = {
      userId: userID
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'deleteUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setUserAdmin(userID: number, isAdmin: boolean): Observable<void> {
    const data = {
      "userId": userID,
      "isAdmin": isAdmin
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'setAdmin');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setUserActive(userID: number, isActive: boolean): Observable<void> {
    const data = {
      "userId": userID,
      "isActive": isActive
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'setActive');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Courses

  public isATeacher(userID: number): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'isATeacher');
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public isAStudent(userID: number): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'isAStudent');
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }


  // Import/Export

  public importUsers(file: string | ArrayBuffer, replace: boolean): Observable<number> {
    const data = {file, replace};

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'importUsers');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data'])) );
  }

  public exportUsers(userIDs: number[]): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'exportUsers');
      qs.push('userIds', userIDs);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => 'data:text/csv;charset=utf-8,%EF%BB%BF' + encodeURIComponent(res['data'])) );
  }



  /*** --------------------------------------------- ***/
  /*** ------------------ Course ------------------- ***/
  /*** --------------------------------------------- ***/

  // General
  public getCourseById(courseID: number): Observable<Course> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseById');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Course.fromDatabase(res['data'])) );
  }

  public getCourses(isActive?: boolean, isVisible?: boolean): Observable<Course[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourses');
      if (isActive !== undefined) qs.push('isActive', isActive);
      if (isVisible !== undefined) qs.push('isVisible', isVisible);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']).map(obj => Course.fromDatabase(obj))) );
  }


  // Course Manipulation
  public createCourse(courseData: CourseManageData): Observable<Course> {
    const data = {
      name: courseData.name,
      short: courseData.short,
      year: courseData.year,
      color: courseData.color,
      startDate: courseData.startDate ? courseData.startDate + ' 00:00:00' : null,
      endDate: courseData.endDate ? courseData.endDate + ' 23:59:59' : null
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'createCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Course.fromDatabase(res['data'])) );
  }

  public duplicateCourse(courseID: number): Observable<Course> {
    const data = { courseId: courseID }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'duplicateCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Course.fromDatabase(res['data'])) );
  }

  public editCourse(courseData: CourseManageData): Observable<Course> {
    const data = {
      courseId: courseData.id,
      name: courseData.name,
      short: courseData.short,
      year: courseData.year,
      color: courseData.color,
      startDate: courseData.startDate ? courseData.startDate + ' 00:00:00' : null,
      endDate: courseData.endDate ? courseData.endDate + ' 23:59:59' : null
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'editCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Course.fromDatabase(res['data'])) );
  }

  public deleteCourse(courseID: number): Observable<void> {
    const data = {
      courseId: courseID
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'deleteCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setCourseActive(courseID: number, isActive: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      isActive
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'setActive');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setCourseVisible(courseID: number, isVisible: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      isVisible
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'setVisible');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Course Users

  public getCourseUsers(courseID: number, active?: boolean): Observable<CourseUser[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseUsers');
      qs.push('courseId', courseID);
      if (active !== undefined) qs.push('active', active);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => CourseUser.fromDatabase(obj))) );
  }

  public getCourseUsersWithRole(courseID: number, roleName: string, active?: boolean): Observable<CourseUser[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseUsersWithRole');
      qs.push('courseId', courseID);
      qs.push('role', roleName);
      if (active !== undefined) qs.push('active', active);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => CourseUser.fromDatabase(obj))) );
  }

  public getUsersNotInCourse(courseID: number, active?: boolean): Observable<User[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getUsersNotInCourse');
      qs.push('courseId', courseID);
      if (active !== undefined) qs.push('active', active);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => User.fromDatabase(obj))) );
  }

  public createCourseUser(courseID: number, userData: CourseUserManageData): Observable<CourseUser> {
    const data = {
      courseId: courseID,
      name: userData.name,
      studentNumber: userData.studentNr,
      nickname: userData.nickname,
      username: userData.username,
      email: userData.email,
      major: userData.major,
      roles: userData.roleNames,
      authService: userData.authService,
      image: userData.photoBase64
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'createCourseUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => CourseUser.fromDatabase(res['data'])) );
  }

  public addUsersToCourse(courseID: number, userIDs: number[], roleNames: string[]): Observable<CourseUser[]> {
    const data = {
      courseId: courseID,
      users: userIDs,
      roles: roleNames,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'addUsersToCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => CourseUser.fromDatabase(obj))) );
  }

  public editCourseUser(courseID: number, userData: CourseUserManageData): Observable<CourseUser> {
    const data = {
      courseId: courseID,
      userId: userData.id,
      name: userData.name,
      studentNumber: userData.studentNr,
      nickname: userData.nickname,
      username: userData.username,
      email: userData.email,
      major: userData.major,
      roles: userData.roleNames,
      authService: userData.authService,
      image: userData.photoBase64
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'editCourseUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => CourseUser.fromDatabase(res['data'])) );
  }

  public deleteCourseUser(courseID: number, userID: number): Observable<void> {
    const data = { courseId: courseID, userId: userID };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'removeUserFromCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setCourseUserActive(courseID: number, userID: number, isActive: boolean): Observable<void> {
    const data = {
      "courseId": courseID,
      "userId": userID,
      "isActive": isActive
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'setCourseUserActive');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public isCourseUser(courseID: number, userID: number): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'isCourseUser');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public isTeacher(courseID: number, userID: number): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'isTeacher');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public isStudent(courseID: number, userID: number): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'isStudent');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public static refreshCourseUserActivity(courseID: number): Observable<Moment> {
    const data = { courseId: courseID };
    const module = ApiHttpService.COURSE;
    const request = 'refreshCourseUserActivity';

    const url = ApiEndpointsService.API_ENDPOINT + '/?module=' + module + '&request=' + request;

    const httpClient = new HttpClient(new HttpXhrBackend({ build: () => new XMLHttpRequest() }));
    return httpClient.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => dateFromDatabase(res)));
  }

  public importCourseUsers(courseID: number, file: string | ArrayBuffer, replace: boolean): Observable<number> {
    const data = {courseId: courseID, file, replace};

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'importCourseUsers');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data'])) );
  }

  public exportCourseUsers(courseID: number, userIDs: number[]): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'exportCourseUsers');
      qs.push('courseId', courseID);
      qs.push('userIds', userIDs);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, null, ApiHttpService.httpOptions)
      .pipe( map((res: any) => 'data:text/csv;charset=utf-8,%EF%BB%BF' + encodeURIComponent(res['data'])) );
  }


  // Roles

  public getDefaultRoles(courseID: number): Observable<string[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getDefaultRoles');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getRoles(courseID: number, onlyNames?: boolean, sortByHierarchy?: boolean): Observable<string[] | Role[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getRoles');
      qs.push('courseId', courseID);
      if (onlyNames !== undefined) qs.push('onlyNames', onlyNames);
      if (sortByHierarchy !== undefined) qs.push('sortByHierarchy', sortByHierarchy);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        if (res['data'].length > 0 && typeof res['data'][0] !== 'string') {
          return res['data'].map(obj => Role.fromDatabase(obj));
        } else return res['data'];
      }) );
  }

  public updateRoles(courseID: number, roles: Role[], hierarchy: any): Observable<void> {
    const data = {
      courseId: courseID,
      roles,
      hierarchy
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'updateRoles');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Modules

  public getCourseModuleById(courseID: number, moduleID: string): Observable<Module> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getModuleById');
      qs.push('courseId', courseID);
      qs.push('moduleId', moduleID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Module.fromDatabase(res['data'])) );
  }

  public getCourseModules(courseID: number, enabled?: boolean): Observable<Module[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getModules');
      qs.push('courseId', courseID);
      if (enabled !== undefined) qs.push('enabled', enabled);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(module => Module.fromDatabase(module))) );
  }

  public static getModulesResources(courseID: number, enabled?: boolean): Observable<{[moduleId: string]: {[key: string]: string[]}}> {
    const module = ApiHttpService.COURSE;
    const request = 'getModulesResources';

    let url = ApiEndpointsService.API_ENDPOINT + '/?module=' + module + '&request=' + request + '&courseId=' + courseID;
    if (enabled !== undefined) url += '&enabled=' + enabled;

    const httpClient = new HttpClient(new HttpXhrBackend({ build: () => new XMLHttpRequest() }));
    return httpClient.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']));
  }

  public setModuleState(courseID: number, moduleID: string, isEnabled: boolean): Observable<void> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
      "state": isEnabled
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'setModuleState');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Course Data

  public getCourseDataFolderContents(courseID: number): Observable<ContentItem[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseDataFolderContents');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public uploadFileToCourse(courseID: number, file: string | ArrayBuffer, folder: string, fileName: string): Observable<string> {
    const data = {
      courseId: courseID,
      file,
      folder,
      fileName
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'uploadFile');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public deleteFileFromCourse(courseID: number, folder: string, fileName: string, deleteIfEmpty: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      folder,
      fileName,
      deleteIfEmpty
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'deleteFile');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Styling

  public getCourseStyles(courseID: number): Observable<{path: string, contents: string} | null> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getStyles');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public updateCourseStyles(courseID: number, styles: string): Observable<void> {
    const data = {
      courseId: courseID,
      styles
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'updateStyles');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Import / Export
  // TODO: refactor
  public importCourses(importData: ImportCoursesData): Observable<number> {
    const data = {
      file: importData.file,
      replace: importData.replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'importCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data'])) );
  }

  // TODO: refactor
  public exportCourses(courses: Course[], options = null): Observable<string> {
    const data = {
      courses: courses.map(course => course.id),
      options: options
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'exportCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }



  /*** --------------------------------------------- ***/
  /*** ------------------- Module ------------------ ***/
  /*** --------------------------------------------- ***/

  // General
  // TODO: refactor
  public getModules(): Observable<Module[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'getModules');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(module => Module.fromDatabase(module))) );
  }

  /*** ---------------------------------------------------------- ***/
  /*** ------------------- NOTIFICATION SYSTEM ------------------ ***/
  /*** ---------------------------------------------------------- ***/

  /* FOR FUTURE USE
  public createNotification(notificationData: NotificationManageData): Observable<Notification> {
    const data = {
      course: notificationData.course,
      user: notificationData.user,
      message: notificationData.message,
      isShowed: notificationData.isShowed
    }
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.NOTIFICATION_SYSTEM);
      qs.push('request', 'createNotification');
    };
    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe(map((res:any) => Notification.fromDatabase(res['data'])));
  }
  public editNotification(notificationData: NotificationManageData): Observable<Notification> {
    const data = {
      id: notificationData.id,
      course: notificationData.course,
      user: notificationData.user,
      message: notificationData.message,
      isShowed: notificationData.isShowed
    }
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.NOTIFICATION_SYSTEM);
      qs.push('request', 'editNotification');
    }
    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe(map((res:any) => Notification.fromDatabase(res['data'])));
  }
  public deleteNotification(notificationID: number): Observable<void> {
    const data = {notificationId : notificationID};
    const params = (qs:QueryStringParameters) => {
      qs.push('module', ApiHttpService.NOTIFICATION_SYSTEM);
      qs.push('request', 'removeNotification');
    };
    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe(map((res:any) => res));
  }
  */

  public getNotificationsByUser(userId: number): Observable<Notification[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.NOTIFICATION_SYSTEM);
      qs.push('request', 'getNotificationsByUser');
      qs.push('userId', userId);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe(map((res:any) => res['data'].map(obj => Notification.fromDatabase(obj))));
  }

  public getNotifications(isShowed?: boolean): Observable<Notification[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.NOTIFICATION_SYSTEM);
      qs.push('request', 'getNotifications');
      if (isShowed !== undefined) qs.push('isShowed', isShowed);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe(map((res:any) => res['data'].map(obj => Notification.fromDatabase(obj))));
  }

  public notificationSetShowed(notificationID: number, isShowed: boolean): Observable<Notification> {
    const data = {
      notificationId: notificationID,
      isShowed: isShowed
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.NOTIFICATION_SYSTEM);
      qs.push('request', 'setShowed');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe(map((res: any) => Notification.fromDatabase(res['data'])));
  }

  // Configuration

  public getModuleConfig(courseID: number, moduleID: string): Observable<{generalInputs: ConfigSection[] | null, lists: List[] | null, personalizedConfig: PersonalizedConfig | null}> {

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'getConfig');
      qs.push('courseId', courseID);
      qs.push('moduleId', moduleID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getDataSourceStatus(courseID: number, moduleID: string): Observable<DataSourceStatus> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'getDataSourceStatus');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        res['data']['startedRunning'] = dateFromDatabase(res['data']['startedRunning']);
        res['data']['finishedRunning'] = dateFromDatabase(res['data']['finishedRunning']);
        return res['data'];
      }) );
  }

  public saveModuleConfig(courseID: number, moduleID: string, generalInputs?: ConfigInputItem[], listingItem?: any,
                          listName?: string, action?: Action | string): Observable<string> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
    }

    if (generalInputs) data['generalInputs'] = generalInputs;
    if (listingItem) {
      data['listingItem'] = listingItem;
      data['listName'] = listName;
      data['action'] = action;
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'saveConfig');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res ? res['data'] : null) );
  }

  public changeDataSourceStatus(courseID: number, moduleID: string, status: boolean): Observable<void> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
      status
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'changeDataSourceStatus');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res ) );
  }

  // TODO: refactor
  public toggleItemParam(courseID: number, moduleID: string, itemID: number, param: string): Observable<void> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
      "itemId": itemID,
      "param": param
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'toggleItemParam');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public changeItemSequence(courseID: number, moduleID: string, itemID: number, oldSeq: number, newSeq: number, table: string): Observable<void> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
      "itemId": itemID,
      oldSeq,
      newSeq,
      table
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'changeItemSequence');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public importModuleItems(courseID: number, moduleID: string, listName: string, file: string | ArrayBuffer, replace: boolean): Observable<number> {
    const data = {
      courseId: courseID,
      moduleId: moduleID,
      listName,
      file,
      replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'importItems');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data'])) );
  }

  public exportModuleItems(courseID: number, moduleID: string, listName: string, items?: number[]): Observable<{extension: string, file?: string, path?: string}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'exportItems');
      qs.push('courseId', courseID);
      qs.push('moduleId', moduleID);
      qs.push('listName', listName);
      qs.push('items', items);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        if (res['data']['extension'] === '.csv') return {extension: res['data']['extension'], file: 'data:text/csv;charset=utf-8,%EF%BB%BF' + encodeURIComponent(res['data']['file'])};
        if (res['data']['extension'] === '.txt') return {extension: res['data']['extension'], file: 'data:text/txt;charset=utf-8,%EF%BB%BF' + encodeURIComponent(res['data']['file'])};
        return {extension: res['data']['extension'], path: res['data']['path']};
      }));
  }


  // Import / Export
  // TODO: refactor
  public importModule(importData: ImportModulesData): Observable<void> {
    const data = {
      file: importData.file,
      fileName: importData.fileName
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'importModule');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  // TODO: refactor
  public exportModules(): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'exportModule');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, null, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['file']) );
  }


  // Google Sheets

  public getGoogleSheetsConfig(courseID: number): Observable<{config: GoogleSheetsConfig, needsAuth: boolean}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.GOOGLESHEETS);
      qs.push('request', 'getConfig');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public setGoogleSheetsConfig(courseID: number, spreadsheetId: string, sheetNames: string[], ownerNames: string[]): Observable<void> {
    const data = {
      courseId: courseID,
      spreadsheetId,
      sheetNames,
      ownerNames
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.GOOGLESHEETS);
      qs.push('request', 'saveConfig');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public authenticateGoogleSheets(courseID: number, credentials: Credentials): Observable<string> {
    const data = {
      courseId: courseID,
      credentials
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.GOOGLESHEETS);
      qs.push('request', 'authenticate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }


  // Progress Report

  public getProgressReportConfig(courseID: number): Observable<ProgressReportConfig> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROGRESS_REPORT);
      qs.push('request', 'getProgressReportConfig');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public saveProgressReportConfig(courseID: number, config: ProgressReportConfig): Observable<void> {
    const data = {
      courseId: courseID,
      progressReport: config
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROGRESS_REPORT);
      qs.push('request', 'saveProgressReportConfig');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public getProgressReports(courseID: number): Observable<{seqNr: number, reportsSent: number, periodStart: Moment, periodEnd: Moment, dateSent: Moment}[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROGRESS_REPORT);
      qs.push('request', 'getReports');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        return res['data'].map(report => {
          return {
            seqNr: report.seqNr,
            reportsSent: report.reportsSent,
            periodStart: dateFromDatabase(report.periodStart),
            periodEnd: dateFromDatabase(report.periodEnd),
            dateSent: dateFromDatabase(report.dateSent),
          }
        });
      }) );
  }

  public getStudentsWithProgressReports(courseID: number, seqNr: number): Observable<{user: User, totalXP: number,
    periodXP: number, diffXP: number, timeLeft: number, prediction: number, pieChart: string, areaChart: string,
    emailSend: string, dateSent: Moment}[]> {

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROGRESS_REPORT);
      qs.push('request', 'getStudentsWithReport');
      qs.push('courseId', courseID);
      qs.push('seqNr', seqNr);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        return res['data'].map(user => {
          return {
            user: User.fromDatabase(user.user),
            totalXP: user.totalXP,
            periodXP: user.periodXP,
            diffXP: user.diffXP,
            timeLeft: user.timeLeft,
            prediction: user.prediction,
            pieChart: user.pieChart,
            areaChart: user.areaChart,
            emailSend: user.emailSend,
            dateSent: dateFromDatabase(user.dateSent),
          }
        });
      }) );
  }

  public getStudentProgressReport(courseID: number, userID: number, seqNr: number): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROGRESS_REPORT);
      qs.push('request', 'getStudentProgressReport');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
      qs.push('seqNr', seqNr);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }


  // Profiling

  public getHistory(courseID: number): Observable<{days: string[], history: ProfilingHistory[], nodes: ProfilingNode[], data: (string|number)[][]}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'getHistory');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getLastRun(courseID: number): Observable<Moment> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'getLastRun');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => dateFromDatabase(res['data'])) );
  }

  public getSavedClusters(courseID: number): Observable<{names: string[], saved: {[studentId: number]: string}[]}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'getSavedClusters');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public runPredictor(courseID: number, method: string, endDate: string): Observable<void> {
    const data = {
      courseId: courseID,
      method,
      endDate
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'runPredictor');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public runProfiler(courseID: number, nrClusters: number, minClusterSize: number, endDate: string): Observable<void> {
    const data = {
      courseId: courseID,
      nrClusters,
      minSize: minClusterSize,
      endDate
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'runProfiler');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public saveClusters(courseID: number, clusters: {[studentId: number]: string}): Observable<void> {
    const data = {
      courseId: courseID,
      clusters
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'saveClusters');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteSavedClusters(courseID: number): Observable<void> {
    const data = {
      courseId: courseID
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'deleteSavedClusters');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public commitClusters(courseID: number, clusters: {[studentId: string]: string}): Observable<void> {
    const data = {
      courseId: courseID,
      clusters
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'commitClusters');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public checkProfilerStatus(courseID: number): Observable<boolean | {clusters: {[studentNr: string]: {name: string, cluster: string}}, names: string[]}> {
    const data = {
      courseId: courseID
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'checkProfilerStatus');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].hasOwnProperty('running') ? res['data']['running'] : res['data']) );
  }

  public checkPredictorStatus(courseID: number): Observable<boolean | number> {
    const data = {
      courseId: courseID
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PROFILING);
      qs.push('request', 'checkPredictorStatus');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].hasOwnProperty('predicting') ? res['data']['predicting'] : parseInt(res['data']['nrClusters'])) );
  }


  // QR

  public getTypesOfClass(): Observable<string[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'getClassTypes');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getUnusedQRCodes(courseID: number): Observable<QRCode[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'getUnusedQRCodes');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getClassParticipations(courseID: number): Observable<QRParticipation[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'getClassParticipations');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        const participations: QRParticipation[] = [];
        for (const participation of res['data']) {
          participations.push({
            id: participation.id,
            qrKey: participation.qrkey,
            user: User.fromDatabase(participation.user),
            classNr: participation.classNumber,
            classType: participation.classType,
            date: dateFromDatabase(participation.date)
          });
        }
        return participations;
      }) );
  }

  public getQRCodeErrors(courseID: number): Observable<{qrKey: string, user: User, message: string, date: Moment}[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'getQRCodeErrors');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        const errors: {qrKey: string, user: User, message: string, date: Moment}[] = [];
        for (const error of res['data']) {
          errors.push({
            qrKey: error.qrkey,
            user: User.fromDatabase(error.user),
            message: error.msg,
            date: dateFromDatabase(error.date)
          });
        }
        return errors;
      }) );
  }

  public generateQRCodes(courseID: number, nrCodes: number): Observable<{key: string, qr: string, url: string}[]> {
    const data = {
      courseId: courseID,
      nrCodes
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'generateQRCodes');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public deleteQRCode(courseID: number, key: string): Observable<void> {
    const data = {
      courseId: courseID,
      key
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'deleteQRCode');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public addQRParticipation(courseID: number, userID: number, lectureNr: number, typeOfClass: string, key: string = null): Observable<void> {
    const data = {
      courseId: courseID,
      userId: userID,
      lectureNr,
      typeOfClass,
    }
    if (key !== null) data['key'] = key;

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'addQRParticipation');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public editQRParticipation(courseID: number, key: string, lectureNr: number, typeOfClass: string): Observable<void> {
    const data = {
      courseId: courseID,
      key,
      lectureNr,
      typeOfClass,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'editQRParticipation');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteQRParticipation(courseID: number, key: string): Observable<void> {
    const data = {
      courseId: courseID,
      key
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'deleteQRParticipation');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Skills

  public getSkillTrees(courseID: number): Observable<SkillTree[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getSkillTrees');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => SkillTree.fromDatabase(obj))) );
  }

  public getTiersOfSkillTree(skillTreeID: number, active: boolean): Observable<Tier[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getTiersOfSkillTree');
      qs.push('skillTreeId', skillTreeID);
      if (active !== null) qs.push('active', active);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => Tier.fromDatabase(obj))) );
  }

  public getSkillsOfSkillTree(skillTreeID: number, active: boolean, extra: boolean, collab: boolean): Observable<Skill[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getSkillsOfSkillTree');
      qs.push('skillTreeId', skillTreeID);
      if (active !== null) qs.push('active', active);
      if (extra !== null) qs.push('extra', extra);
      if (collab !== null) qs.push('collab', collab);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => Skill.fromDatabase(obj))) );
  }

  public createTier(courseID: number, skillTreeID: number, tierData: TierManageData): Observable<void> {
    const data = {
      courseId: courseID,
      skillTreeId: skillTreeID,
      name: tierData.name,
      reward: tierData.reward,
      costType: tierData.costType,
      cost: tierData.cost,
      increment: tierData.increment,
      minRating: tierData.minRating
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'createTier');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public editTier(courseID: number, tier: Tier): Observable<void> {
    const data = {
      courseId: courseID,
      tierId: tier.id,
      name: tier.name,
      reward: tier.reward,
      costType: tier.costType,
      cost: tier.cost,
      increment: tier.increment,
      minRating: tier.minRating,
      position: tier.position,
      isActive: tier.isActive
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'editTier');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteTier(courseID: number, tierID: number): Observable<void> {
    const data = {
      courseId: courseID,
      tierId: tierID
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'deleteTier');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public getSkillById(skillID: number): Observable<Skill> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getSkillById');
      qs.push('skillId', skillID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Skill.fromDatabase(res['data'])) );
  }

  public createSkill(courseID: number, skillData: SkillManageData): Observable<void> {
    const data = {
      courseId: courseID,
      tierId: parseInt(skillData.tierID.substring(3)),
      name: skillData.name,
      color: skillData.color ?? null,
      page: skillData.page ?? null,
      dependencies: skillData.dependencies.map(combo => combo.map(skill => skill.id))
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'createSkill');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public editSkill(courseID: number, skill: Skill): Observable<void> {
    const data = {
      courseId: courseID,
      skillId: skill.id,
      tierId: skill.tierID,
      name: skill.name,
      color: skill.color,
      page: skill.page,
      isCollab: skill.isCollab,
      isExtra: skill.isExtra,
      isActive: skill.isActive,
      position: skill.position,
      dependencies: skill.dependencies? skill.dependencies.map(combo => combo.map(skill => skill.id)) : []
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'editSkill');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteSkill(courseID: number, skillID: number): Observable<void> {
    const data = {
      courseId: courseID,
      skillId: skillID
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'deleteSkill');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Virtual Currency

  public getVCName(courseID: number): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIRTUAL_CURRENCY);
      qs.push('request', 'getVCName');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getUserTokens(courseID: number, userID: number): Observable<number> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIRTUAL_CURRENCY);
      qs.push('request', 'getUserTokens');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public exchangeUserTokens(courseID: number, userIds: number[], ratio: string, threshold: number): Observable<void | number> {
    const data = {
      courseId: courseID,
      users: userIds,
      ratio,
      threshold
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIRTUAL_CURRENCY);
      qs.push('request', 'exchangeUserTokens');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }



  /*** --------------------------------------------- ***/
  /*** ------------------ Themes ------------------- ***/
  /*** --------------------------------------------- ***/

  // General
  // TODO: refactor
  public getThemes(): Observable<{themes: string[], current: string}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.THEME);
      qs.push('request', 'getThemes');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public getUserTheme(userID: number): Observable<Theme> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.THEME);
      qs.push('request', 'getUserTheme');
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'] as Theme) );
  }

  public setUserTheme(userID: number, theme: Theme): Observable<void> {
    const data = {userId: userID, theme};

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.THEME);
      qs.push('request', 'setUserTheme');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }



  /*** --------------------------------------------- ***/
  /*** ------------------- Views ------------------- ***/
  /*** --------------------------------------------- ***/

  // General
  // TODO: refactor
  public getViewsList(courseID: number): Observable<{pages: Page[], templates: Template[], globals: Template[], types: RoleType[]}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'listViews');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        return {
          pages: Object.values(res['data']['pages']).map(obj => Page.fromDatabase(obj as any)),
          templates: res['data']['templates'].map(obj => Template.fromDatabase(obj)),
          globals: res['data']['globals'].map(obj => Template.fromDatabase(obj)),
          types: res['data']['types'].map(obj => RoleType.fromDatabase(obj))
        }
      }) );
  }


  // Pages

  public getPageById(pageID: number): Observable<Page> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'getPageById');
      qs.push('pageId', pageID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Page.fromDatabase(res['data'])) );
  }

  public getUserLandingPage(courseID: number, userID: number): Observable<Page> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'getUserLandingPage');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        if (res['data']) return Page.fromDatabase(res['data']);
        return null;
      }) );
  }

  public getCoursePages(courseID: number, isVisible?: boolean): Observable<Page[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'getCoursePages');
      qs.push('courseId', courseID);
      if (isVisible !== undefined) qs.push('isVisible', isVisible);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => Page.fromDatabase(obj))) );
  }

  public getUserPages(courseID: number, userID: number, isVisible?: boolean): Observable<Page[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'getUserPages');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
      if (isVisible !== undefined) qs.push('isVisible', isVisible);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => Page.fromDatabase(obj))) );
  }

  // TODO: refactor
  public renderPage(courseID: number, pageID: number,  userID: number = null): Observable<View> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'renderPage');
      qs.push('courseId', courseID);
      qs.push('pageId', pageID);
      if (exists(userID)) qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe(map((res: any) => buildView(res['data'])));
  }

  // TODO: refactor
  public createPage(courseID: number, page: Partial<Page>): Observable<void> {
    const data = {
      courseId: courseID,
      pageName: page.name,
      viewId: page.viewId,
      isEnabled: page.isEnabled ? 1 : 0
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'createPage');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public editPage(courseID: number, page: Page): Observable<void> {
    const data = {
      courseId: courseID,
      pageId: page.id,
      pageName: page.name,
      viewId: page.viewId,
      isEnabled: page.isEnabled
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'editPage');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public deletePage(courseID: number, page: Page): Observable<any> {
    const data = {
      courseId: courseID,
      pageId: page.id,
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'deletePage');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public importPages(courseID: number, file: string | ArrayBuffer, replace: boolean): Observable<number> {
    const data = {courseId: courseID, file, replace};

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'importPages');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data'])) );
  }


  // Templates
  // TODO: refactor
  public getTemplate(courseID: number, templateID: number): Observable<Template> {
    const data = {
      courseId: courseID,
      templateId: templateID
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'getTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Template.fromDatabase(res['data']['template'])) );
  }

  // TODO: refactor
  public createTemplate(courseID: number, template: Partial<Template>): Observable<void> {
    const data = {
      courseId: courseID,
      templateName: template.name,
      roleType: template.roleType,
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'createTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public editTemplateBasicInfo(courseID: number, template: Template): Observable<void> {
    const data = {
      courseId: courseID,
      templateId: template.id,
      templateName: template.name,
      roleType: template.roleType,
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'editTemplateBasicInfo');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public deleteTemplate(courseID: number, template: Template): Observable<any> {
    const data = {
      courseId: courseID,
      templateId: template.id,
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'deleteTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public globalizeTemplate(courseID: number, template: Template): Observable<void> {
    const data = {
      courseId: courseID,
      templateId: template.id,
      isGlobal: template.isGlobal
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'setGlobalState');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public importTemplate(courseID: number, file: string | ArrayBuffer): Observable<void> {
    const data = {
      courseId: courseID,
      file,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'importTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public exportTemplate(courseID: number, templateId: number): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'exportTemplate');
      qs.push('courseId', courseID);
      qs.push('templateId', templateId);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => 'data:text;charset=utf-8,%EF%BB%BF' + encodeURIComponent(res['data']['template'])) );
  }


  // Editor
  // TODO: refactor
  // public getTemplateEditInfo(courseID: number, templateID: number):
  //   Observable<{courseRoles: Role[], rolesHierarchy: Role[], templateRoles: string[], templateViewsByAspect: {[key: string]: View},
  //               enabledModules: string[]}> {
  //
  //   const params = (qs: QueryStringParameters) => {
  //     qs.push('module', ApiHttpService.PAGE);
  //     qs.push('request', 'getTemplateEditInfo');
  //     qs.push('courseId', courseID);
  //     qs.push('templateId', templateID);
  //   };
  //
  //   const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
  //
  //   return this.get(url, ApiHttpService.httpOptions)
  //     .pipe( map((res: any) => {
  //       const courseRoles: Role[] = (res['data']['courseRoles']).map(obj => Role.fromDatabase(obj));
  //       const rolesHierarchy: Role[] = Role.parseHierarchy(res['data']['rolesHierarchy'], courseRoles);
  //       const templateRoles = res['data']['templateRoles'];
  //       const templateViewsByAspect = objectMap(res['data']['templateViewsByAspect'], (view) => buildView(view));
  //       const enabledModules = res['data']['enabledModules'];
  //       return {courseRoles, rolesHierarchy, templateRoles, templateViewsByAspect, enabledModules}
  //     }) );
  // }

  // TODO: refactor
  public previewTemplate(courseID: number, templateID: number, viewerRole: string, userRole?: string): Observable<View> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'previewTemplate');
      qs.push('courseId', courseID);
      qs.push('templateId', templateID);
      qs.push('viewerRole', viewerRole);
      if (userRole) qs.push('userRole', userRole);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => buildView(res['data']['view'])) );
  }

  // TODO: refactor
  public saveTemplate(courseID: number, templateID: number, viewTree, viewsDeleted?: number[]): Observable<void> {
    const data = {
      courseId: courseID,
      templateId: templateID,
      template: viewTree
    }
    if (viewsDeleted?.length > 0) data['viewsDeleted'] = viewsDeleted;

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'saveTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  // TODO: refactor
  public saveViewAsTemplate(courseID: number, templateName: string, viewTree, roleType: string, isRef: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      templateName,
      view: viewTree,
      roleType,
      isRef
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.PAGE);
      qs.push('request', 'saveViewAsTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // TODO. hard-coded
  public getUserTotalAvailableWildcards(courseID: number, userID: number, skillTreeID: number): Observable<number> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getUserTotalAvailableWildcards');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
      qs.push('skillTreeId', skillTreeID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']));
  }

  public getSkillsExtraInfo(courseID: number, userID: number, skillTreeID: number): Observable<{attempts: {[key: number]: number}, cost: {[key: number]: number}, completed: number[]}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getSkillsExtraInfo');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
      qs.push('skillTreeId', skillTreeID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        const info = res['data'];
        const attempts: {[key: number]: number} = {};
        const cost: {[key: number]: number} = {};
        for (const i of info) {
          attempts[i['id']] = i['attempts'];
          cost[i['id']] = i['cost'];
        }
        const completed: number[] = info.map(skill => skill['id']);
        return {attempts, cost, completed};
      }));
  }

  public getStreaks(courseID: number): Observable<Streak[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getStreaks');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => {
        return {
          id: obj.id,
          name: obj.name,
          description: obj.description,
          color: obj.color,
          image: obj.image,
          svg: obj.svg,
          goal: obj.goal,
          reward: obj.reward,
          tokens: obj.tokens,
          isExtra: obj.isExtra,
          isRepeatable: obj.isRepeatable,
          isPeriodic: obj.isPeriodic
        };
      })));
  }

  public getUserStreaksInfo(courseID: number, userID: number): Observable<{id: number, nrCompletions: number, progress: number, deadline: Moment}[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getUserStreaksInfo');
      qs.push('courseId', courseID);
      qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(obj => {
        return {
          id: obj.id,
          nrCompletions: obj.nrCompletions,
          progress: obj.progress,
          deadline: dateFromDatabase(obj.deadline),
        };
      })));
  }


  /*** --------------------------------------------- ***/
  /*** ----------------- AutoGame ------------------ ***/
  /*** --------------------------------------------- ***/

  public getAutoGameLastRun(courseID: number): Observable<Moment> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.AUTOGAME);
      qs.push('request', 'getLastRun');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => dateFromDatabase(res['data'])));
  }



  /*** --------------------------------------------- ***/
  /*** -------------------- Docs -------------------- ***/
  /*** --------------------------------------------- ***/

  // FIXME: finish up when can enable modules
  // TODO: refactor
  public getSchema() {
    const params = (qs: QueryStringParameters) => {
      qs.push('list', true);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('docs/functions/getSchema.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res[0]) )
      .subscribe(courseID => {  // vai buscar o primeiro course identificado pelo id

        const params = (qs: QueryStringParameters) => {
          qs.push('course', courseID);
        };

        const url = this.apiEndpoint.createUrlWithQueryParameters('docs/functions/getSchema.php', params);

        return this.get(url, ApiHttpService.httpOptions)
          .pipe( map((res: any) => res[0]) )
          .subscribe(libraries => {
            console.log(libraries)
          })
      });
  }



  /*** --------------------------------------------- ***/
  /*** -------------- Helper Functions ------------- ***/
  /*** --------------------------------------------- ***/

  public get(url: string, options?: any, skipErrors?: boolean) {
    return this.http.get(url, options)
      .pipe(
        catchError(error => {
          if (error.status === 401)
            this.router.navigate(['/login']);

          if (error.status === 403)
            this.router.navigate(['/no-access']);

          if (error.status === 409)
            this.router.navigate(['/setup']);

          if (!skipErrors) ErrorService.set(error);
          return throwError(error);
        })
      );
  }

  public post(url: string, data: any, options?: any, skipErrors?: boolean) {
    return this.http.post(url, data, options)
      .pipe(
        catchError(error => {
          if (error.status === 401)
            this.router.navigate(['/login']);

          if (error.status === 403)
            this.router.navigate(['/no-access']);

          if (error.status === 409)
            this.router.navigate(['/setup']);

          if (!skipErrors) ErrorService.set(error);
          return throwError(error);
        })
      );
  }
}
