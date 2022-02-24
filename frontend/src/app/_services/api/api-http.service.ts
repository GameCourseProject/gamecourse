import {Injectable} from '@angular/core';
import {HttpClient, HttpHeaders, HttpXhrBackend} from "@angular/common/http";
import {Observable, of} from "rxjs";
import {catchError, map} from "rxjs/operators";

import {ApiEndpointsService} from "./api-endpoints.service";

import {QueryStringParameters} from "../../_utils/api/query-string-parameters";

import {AuthType} from "../../_domain/auth/auth-type";
import {Course} from "../../_domain/courses/course";
import {User} from "../../_domain/users/user";
import {CreationMode} from "../../_domain/courses/creation-mode";
import {SetupData} from "../../_views/setup/setup/setup.component";
import {UserData} from "../../_views/my-info/my-info/my-info.component";
import {CourseData, ImportCoursesData} from "../../_views/courses/courses/courses.component";
import {ImportUsersData} from "../../_views/users/users/users.component";
import {Module} from "../../_domain/modules/module";
import {ImportModulesData} from "../../_views/settings/modules/modules.component";
import {Moment} from "moment/moment";
import {Role} from "../../_domain/roles/role";
import {Page} from "../../_domain/pages & templates/page";
import {Template} from "../../_domain/pages & templates/template";
import {RoleType} from "../../_domain/roles/role-type";
import {View} from "../../_domain/views/view";
import {buildView} from "../../_domain/views/build-view/build-view";
import {dateFromDatabase, exists, objectMap} from "../../_utils/misc/misc";
import {GeneralInput, ListingItems} from "../../_views/courses/course/settings/modules/config/config/config.component";
import {Tier} from "../../_domain/skills/tier";
import {SkillData, TierData} from "../../_views/courses/course/settings/modules/config/skills/skills.component";
import {Skill} from "../../_domain/skills/skill";
import {ContentItem} from "../../_components/modals/file-picker-modal/file-picker-modal.component";
import {Credentials} from "../../_views/courses/course/settings/modules/config/googlesheets/googlesheets.component";
import {MoodleVars} from "../../_views/courses/course/settings/modules/config/moodle/moodle.component";
import {TypeOfClass} from "../../_views/courses/course/page/page.component";

@Injectable({
  providedIn: 'root'
})
export class ApiHttpService {

  private static readonly httpOptions = {
    headers: new HttpHeaders(),
    withCredentials: true
  };

  static readonly CORE: string = 'core';
  static readonly COURSE: string = 'course';
  static readonly MODULE: string = 'module';
  static readonly THEMES: string = 'themes';
  static readonly USER: string = 'user';
  static readonly VIEWS: string = 'views';

  static readonly CLASSCHECK: string = 'classcheck';
  static readonly FENIX: string = 'fenix';
  static readonly GOOGLESHEETS: string = 'googlesheets';
  static readonly MOODLE: string = 'moodle';
  static readonly PROFILING: string = 'profiling';
  static readonly QR: string = 'qr';
  static readonly QUEST: string = 'quest';
  static readonly SKILLS: string = 'skills';


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

    return this.post(url, formData, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['setup']) );
  }

  public isSetupDone(): Observable<boolean> {
    const url = this.apiEndpoint.createUrl('login.php');

    return this.post(url, new FormData(), ApiHttpService.httpOptions)
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

    this.post(url, formData, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['redirectURL']) )
      .subscribe(url => window.open(url,"_self"));
  }

  public isLoggedIn(): Observable<boolean> {
    const url = this.apiEndpoint.createUrl('login.php');

    return this.post(url, new FormData(), ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['isLoggedIn']) );
  }

  public logout(): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('logout', true);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('login.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['isLoggedIn']) );
  }



  /*** --------------------------------------------- ***/
  /*** ------------------ Course ------------------- ***/
  /*** --------------------------------------------- ***/

  // General
  public getCourse(courseID: number): Observable<Course> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourse');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Course.fromDatabase(res['data']['course'])) );
  }

  public getCourseWithInfo(courseID: number): Observable<{course: Course, activePages: Page[]}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseWithInfo');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        return {
          course: Course.fromDatabase(res['data']['course']),
          activePages: res['data']['activePages'].map(page => Page.fromDatabase(page)),
        }
      }));
  }

  public getCourseGlobal(courseID: number): Observable<{ name: string, activeUsers: number, awards: number, participations: number }> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseGlobal');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        return {
          name: res['data']['name'],
          activeUsers: res['data']['activeUsers'],
          awards: res['data']['awards'],
          participations: res['data']['participations'],
        };
      }) );
  }

  public getCourseRoles(courseID: number): Observable<Role[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseRoles');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']['courseRoles']).map(role => Role.fromDatabase(role))) );
  }

  public static getCourseResources(courseID: number): Observable<{name: string, files: string[]}[]> {
    const module = ApiHttpService.COURSE;
    const request = 'getCourseResources';

    const url = ApiEndpointsService.API_ENDPOINT + '/info.php?module=' + module + '&request=' + request + '&courseId=' + courseID;

    const httpClient = new HttpClient(new HttpXhrBackend({ build: () => new XMLHttpRequest() }));
    return httpClient.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['resources']));
  }

  public getCourseDataFolderContents(courseID: number): Observable<ContentItem[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseDataFolderContents');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['contents']) );
  }


  // Course Manipulation
  public createCourse(courseData: CourseData, creationMode: CreationMode = CreationMode.BLANK): Observable<Course> {
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
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'createCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Course.fromDatabase(res['data']['course'])) );
  }

  public duplicateCourse(courseData: CourseData): Observable<Course> {
    return this.createCourse(courseData, CreationMode.SIMILAR);
  }

  public editCourse(courseData: CourseData): Observable<void> {
    const data = {
      courseId: courseData.id,
      courseName: courseData.name,
      courseShort: courseData.short,
      courseYear: courseData.year,
      courseColor: courseData.color,
      courseIsVisible: courseData.isVisible ? 1 : 0,
      courseIsActive: courseData.isActive ? 1 : 0,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'editCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteCourse(courseID: number): Observable<void> {
    const data = { courseId: courseID };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'deleteCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setCourseVisible(courseID: number, isVisible: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      isVisible: isVisible ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'setCourseVisibility');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setCourseActive(courseID: number, isActive: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      isActive: isActive ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'setCourseActiveState');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Import / Export
  public importCourses(importData: ImportCoursesData): Observable<number> {
    const data = {
      file: importData.file,
      replace: importData.replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'importCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data']['nrCourses'])) );
  }

  public exportCourses(courseID: number = null, options = null): Observable<string> {
    const data = {
      courseId: courseID,
      options: options
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'exportCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['courses']) );
  }


  // Course Users
  public getCourseUsers(courseID: number): Observable<User[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseUsers');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']['userList']).map(obj => User.fromDatabase(obj))) );
  }

  public getNotCourseUsers(courseID: number): Observable<User[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseNonUsers');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']['notCourseUsers']).map(obj => User.fromDatabase(obj))) );
  }

  public createCourseUser(courseID: number, userData: UserData): Observable<void> {
    const data = {
      courseId: courseID,
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
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'createCourseUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public addUsersToCourse(courseID: number, users: User[], role: string): Observable<void> {
    const data = {
      courseId: courseID,
      role,
      users: users.map(user => {
        return { id: user.id, name: user.name, studentNumber: user.studentNumber }
      }),
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'addUsersToCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public editCourseUser(courseID: number, userData: UserData): Observable<void> {
    const data = {
      courseId: courseID,
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
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'editCourseUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteCourseUser(courseID: number, userID: number): Observable<void> {
    const data = { courseId: courseID, userId: userID };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'removeCourseUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setCourseUserActive(courseID: number, userID: number, isActive: boolean): Observable<void> {
    const data = {
      "courseId": courseID,
      "userId": userID,
      "isActive": isActive ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'setCourseUserActiveState');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public importCourseUsers(courseID: number, importData: ImportUsersData): Observable<number> {
    const data = {
      courseId: courseID,
      file: importData.file,
      replace: importData.replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'importCourseUsers');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data']['nrUsers'])) );
  }

  public exportCourseUsers(courseID: number): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'exportCourseUsers');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, null, ApiHttpService.httpOptions)
      .pipe( map((res: any) => 'data:text/csv;charset=utf-8,' + encodeURIComponent(res['data']['courseUsers'])) );
  }


  // Course Modules
  public getCourseModules(courseID: number): Observable<Module[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseModules');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(module => Module.fromDatabase(module))) );
  }


  // Roles
  public getRoles(courseID: number): Observable<{ roles: Role[], rolesHierarchy: Role[] }> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'roles');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        const allRoles: Role[] = res['data']['roles_obj'].map(obj => Role.fromDatabase(obj));
        const roles = Role.parseHierarchy(res['data']['rolesHierarchy'], allRoles);
        return {roles: allRoles, rolesHierarchy: roles}
      }) );
  }

  public saveRoles(courseID: number, roles: Role[], hierarchy: any): Observable<void> {
    const data = {
      courseId: courseID,
      updateRoleHierarchy: true,
      hierarchy,
      roles: roles.map(role => {
        return {name: role.name, id: role.id ? role.id.toString() : null, landingPage: role.landingPage}
      })
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'roles');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Rules System
  public getRulesSystemLastRun(courseID: number): Observable<Moment> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getRulesSystemLastRun');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => dateFromDatabase(res['data']['ruleSystemLastRun'])));
  }


  // Styles
  public getCourseStyleFile(courseID: number): Observable<{styleFile: string, url: string}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getCourseStyleFile');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => { return {styleFile: res['data']['styleFile'] === false ? '' : res['data']['styleFile'], url: res['data']['url']} }) );
  }

  public createCourseStyleFile(courseID: number): Observable<string> {
    const data = {
      courseId: courseID
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'createCourseStyleFile');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['url']) );
  }

  public updateCourseStyleFile(courseID: number, content: string): Observable<string> {
    const data = {
      courseId: courseID,
      content: content
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'updateCourseStyleFile');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['url']) );
  }


  // Resources
  public uploadFileToCourse(courseID: number, file: string | ArrayBuffer, folder: string, fileName: string): Observable<string> {
    const data = {
      courseId: courseID,
      file,
      folder,
      fileName
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'uploadFileToCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['path']) );
  }

  public deleteFileFromCourse(courseID: number, filePath: string): Observable<void> {
    const data = {
      courseId: courseID,
      path: filePath,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'deleteFileFromCourse');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Database Manipulation
  public getTableData(courseID: number, table: string): Observable<{entries: any[], columns: any}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.COURSE);
      qs.push('request', 'getTableData');
      qs.push('courseId', courseID);
      qs.push('table', table);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }



  /*** --------------------------------------------- ***/
  /*** ------------------- Module ------------------ ***/
  /*** --------------------------------------------- ***/

  // General
  public getModulesAvailable(): Observable<Module[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'getModules');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data'].map(module => Module.fromDatabase(module))) );
  }


  // Module Manipulation
  public setModuleState(courseID: number, moduleID: string, isEnabled: boolean): Observable<void> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
      "isEnabled": isEnabled
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'setModuleState');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Import / Export
  public importModule(importData: ImportModulesData): Observable<void> {
    const data = {
      file: importData.file,
      fileName: importData.fileName
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'importModule');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }

  public exportModules(): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'exportModule');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, null, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['file']) );
  }


  // Configuration
  public getModuleConfigInfo(courseID: number, moduleID: string):
    Observable<{module: Module, courseFolder: string, generalInputs?: GeneralInput[], listingItems?: ListingItems,
      personalizedConfig?: string}> {

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'getModuleConfigInfo');
      qs.push('courseId', courseID);
      qs.push('moduleId', moduleID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map(
        (res: any) => {
          return {
            module: Module.fromDatabase(res['data']['module']),
            courseFolder: res['data']['courseFolder'],
            generalInputs: res['data']['generalInputs'],
            listingItems: res['data']['listingItems'],
            personalizedConfig: res['data']['personalizedConfig']
          }
        }) );
  }

  public saveModuleConfigInfo(courseID: number, moduleID: string, generalInputs?: {[key: string]: any}, listingItem?: any,
                              actionType?: 'new' | 'edit' | 'delete' | 'duplicate'): Observable<void> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
    }

    if (generalInputs) data['generalInputs'] = generalInputs;
    if (listingItem) {
      data['listingItem'] = listingItem;
      data['actionType'] = actionType;
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'saveModuleConfigInfo');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );

  }

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

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

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

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public importModuleItems(courseID: number, moduleID: string, file: string | ArrayBuffer, replace: boolean): Observable<number> {
    const data = {
      courseId: courseID,
      moduleId: moduleID,
      file,
      replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'importItems');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data']['nrItems'])) );
  }

  public exportModuleItems(courseID: number, moduleID: string, itemID: number): Observable<{fileName: string, contents: string}> {
    const data = {
      "courseId": courseID,
      "moduleId": moduleID,
      "itemId": itemID
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MODULE);
      qs.push('request', 'exportItems');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        return {
          fileName: res['data']['fileName'],
          contents: 'data:text/csv;charset=utf-8,' + encodeURIComponent(res['data']['items'])
        }
      }) );
  }


  // ClassCheck
  public getClassCheckVars(courseID: number): Observable<{tsvCode: string, periodicityNumber: number, periodicityTime: string, isEnabled: boolean}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.CLASSCHECK);
      qs.push('request', 'getClassCheckVars');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['classCheckVars']) );
  }

  public setClassCheckVars(courseID: number, tsvCode: string, periodicityNr: number, periodicityTime: string, isEnabled: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      classCheck: {
        tsvCode,
        periodicityNumber: periodicityNr,
        periodicityTime,
        isEnabled
      }
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.CLASSCHECK);
      qs.push('request', 'setClassCheckVars');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Fenix
  public importFenixStudents(courseID: number, file: string | ArrayBuffer): Observable<number> {
    const data = {
      courseId: courseID,
      file,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.FENIX);
      qs.push('request', 'importFenixStudents');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data']['nrStudents'])) );
  }


  // GoogleSheets
  public getGoogleSheetsVars(courseID: number): Observable<{spreadsheetId: string, sheetName: string[], ownerName: string[], periodicityNumber: number, periodicityTime: string, isEnabled: boolean}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.GOOGLESHEETS);
      qs.push('request', 'getGoogleSheetsVars');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['googleSheetsVars']) );
  }

  public setGoogleSheetsVars(courseID: number, spreadsheetId: string, sheets: {name: string, owner: string}[],  periodicityNr: number, periodicityTime: string, isEnabled: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      googleSheets: {
        spreadsheetId,
        sheetName: sheets.map(sheet => sheet.name),
        ownerName: sheets.map(sheet => sheet.owner),
        periodicityNumber: periodicityNr,
        periodicityTime,
        isEnabled
      }
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.GOOGLESHEETS);
      qs.push('request', 'setGoogleSheetsVars');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setGoogleSheetsCredentials(courseID: number, credentials: Credentials): Observable<string> {
    const data = {
      courseId: courseID,
      credentials
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.GOOGLESHEETS);
      qs.push('request', 'setGoogleSheetsCredentials');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['authUrl']) );
  }


  // Moodle
  public getMoodleVars(courseID: number): Observable<MoodleVars> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MOODLE);
      qs.push('request', 'getMoodleVars');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['moodleVars']) );
  }

  public setMoodleVars(courseID: number, moodleVars: MoodleVars): Observable<void> {
    const data = {
      courseId: courseID,
      moodle: moodleVars
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.MOODLE);
      qs.push('request', 'setMoodleVars');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // QR
  public generateQRCodes(courseID: number, nrCodes: number): Observable<{qr: string, url: string}[]> {
    const data = {
      courseId: courseID,
      nrCodes
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'generateQRCodes');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['QRCodes']) );
  }

  public submitQRParticipation(courseID: number, key: string, lectureNr: number, typeOfClass: TypeOfClass): Observable<void> {
    const data = {
      courseId: courseID,
      key,
      lectureNr,
      typeOfClass
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.QR);
      qs.push('request', 'submitQRParticipation');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Skills
  public getTiers(courseID: number): Observable<Tier[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getTiers');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']['tiers']).map(obj => Tier.fromDatabase(obj))) );
  }

  public createTier(courseID: number, tier: TierData): Observable<Tier> {
    const data = {
      courseId: courseID,
      tier
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'createTier');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Tier.fromDatabase(res['data']['tier'])) );
  }

  public editTier(courseID: number, tier: TierData): Observable<void> {
    const data = {
      courseId: courseID,
      tier
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'editTier');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteTier(courseID: number, tier: TierData): Observable<void> {
    const data = {
      courseId: courseID,
      tier
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'deleteTier');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public getSkills(courseID: number): Observable<Skill[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'getSkills');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']['skills']).map(obj => Skill.fromDatabase(obj))) );
  }

  public createSkill(courseID: number, skill: SkillData): Observable<void> {
    const data = {
      courseId: courseID,
      skill
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'createSkill');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public editSkill(courseID: number, skill: SkillData): Observable<void> {
    const data = {
      courseId: courseID,
      skill
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'editSkill');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
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

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public renderSkillPage(courseID: number, skillID: number): Observable<Skill> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.SKILLS);
      qs.push('request', 'renderSkillPage');
      qs.push('courseId', courseID);
      qs.push('skillId', skillID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Skill.fromDatabase(res['data']['skill'])) );
  }



  /*** --------------------------------------------- ***/
  /*** ------------------ Themes ------------------- ***/
  /*** --------------------------------------------- ***/

  // General
  public getThemeSettings(): Observable<{theme: string, themes: {name: string, preview: boolean}[]}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.THEMES);
      qs.push('request', 'getThemeSettings');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']) );
  }



  /*** --------------------------------------------- ***/
  /*** -------------------- User ------------------- ***/
  /*** --------------------------------------------- ***/

  // Logged User
  public getLoggedUser(): Observable<User> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'getLoggedUserInfo');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map(
        (res: any) => User.fromDatabase(res['data']['userInfo'])
      ) );
  }

  public getUserActiveCourses(): Observable<Course[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'getLoggedUserActiveCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map(
        (res: any) => (res['data']['userActiveCourses']).map(obj => Course.fromDatabase(obj))
      ) );
  }

  public isCourseTeacher(courseID: number): Observable<boolean> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'isCourseTeacher');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res['data']['isTeacher']) );
  }


  // General
  public getUsers(): Observable<User[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'getUsers');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']['users']).map(obj => User.fromDatabase(obj))) );
  }

  public getUserCourses(): Observable<Course[]> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'getUserCourses');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => (res['data']['courses']).map(obj => Course.fromDatabase(obj))) );
  }

  public importUsers(importData: ImportUsersData): Observable<number> {
    const data = {
      file: importData.file,
      replace: importData.replace
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'importUsers');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => parseInt(res['data']['nrUsers'])) );
  }

  public exportUsers(): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'exportUsers');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, null, ApiHttpService.httpOptions)
      .pipe( map((res: any) => 'data:text/csv;charset=utf-8,' + encodeURIComponent(res['data']['users'])) );
  }


  // User Manipulation
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
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'createUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
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
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'editUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
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
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'editSelfInfo');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions).pipe();
  }

  public deleteUser(userID: number): Observable<void> {
    const data = { userId: userID };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'deleteUser');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setUserAdmin(userID: number, isAdmin: boolean): Observable<void> {
    const data = {
      "userId": userID,
      "isAdmin": isAdmin ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'setUserAdminPermission');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public setUserActive(userID: number, isActive: boolean): Observable<void> {
    const data = {
      "userId": userID,
      "isActive": isActive ? 1 : 0
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.USER);
      qs.push('request', 'setUserActiveState');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }



  /*** --------------------------------------------- ***/
  /*** ------------------- Views ------------------- ***/
  /*** --------------------------------------------- ***/

  // General
  public getViewsList(courseID: number): Observable<{pages: Page[], templates: Template[], globals: Template[], types: RoleType[]}> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'listViews');
      qs.push('courseId', courseID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

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
  public renderPage(courseID: number, pageID: number,  userID: number = null): Observable<View> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'renderPage');
      qs.push('courseId', courseID);
      qs.push('pageId', pageID);
      if (exists(userID)) qs.push('userId', userID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe(map((res: any) => buildView(res['data']['view'])));
  }

  public createPage(courseID: number, page: Partial<Page>): Observable<void> {
    const data = {
      courseId: courseID,
      pageName: page.name,
      viewId: page.viewId,
      isEnabled: page.isEnabled ? 1 : 0
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'createPage');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public editPage(courseID: number, page: Page): Observable<void> {
    const data = {
      courseId: courseID,
      pageId: page.id,
      pageName: page.name,
      viewId: page.viewId,
      isEnabled: page.isEnabled
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'editPage');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deletePage(courseID: number, page: Page): Observable<any> {
    const data = {
      courseId: courseID,
      pageId: page.id,
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'deletePage');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }


  // Templates
  public getTemplate(courseID: number, templateID: number): Observable<Template> {
    const data = {
      courseId: courseID,
      templateId: templateID
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'getTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => Template.fromDatabase(res['data']['template'])) );
  }

  public createTemplate(courseID: number, template: Partial<Template>): Observable<void> {
    const data = {
      courseId: courseID,
      templateName: template.name,
      roleType: template.roleType,
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'createTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public editTemplateBasicInfo(courseID: number, template: Template): Observable<void> {
    const data = {
      courseId: courseID,
      templateId: template.id,
      templateName: template.name,
      roleType: template.roleType,
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'editTemplateBasicInfo');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public deleteTemplate(courseID: number, template: Template): Observable<any> {
    const data = {
      courseId: courseID,
      templateId: template.id,
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'deleteTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public globalizeTemplate(courseID: number, template: Template): Observable<void> {
    const data = {
      courseId: courseID,
      templateId: template.id,
      isGlobal: template.isGlobal
    };

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'setGlobalState');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public importTemplate(courseID: number, file: string | ArrayBuffer): Observable<void> {
    const data = {
      courseId: courseID,
      file,
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'importTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public exportTemplate(courseID: number, templateId: number): Observable<string> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'exportTemplate');
      qs.push('courseId', courseID);
      qs.push('templateId', templateId);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => 'data:text;charset=utf-8,' + encodeURIComponent(res['data']['template'])) );
  }


  // Editor
  public getTemplateEditInfo(courseID: number, templateID: number):
    Observable<{courseRoles: Role[], rolesHierarchy: Role[], templateRoles: string[], templateViewsByAspect: {[key: string]: View},
                enabledModules: string[]}> {

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'getTemplateEditInfo');
      qs.push('courseId', courseID);
      qs.push('templateId', templateID);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => {
        const courseRoles: Role[] = (res['data']['courseRoles']).map(obj => Role.fromDatabase(obj));
        const rolesHierarchy: Role[] = Role.parseHierarchy(res['data']['rolesHierarchy'], courseRoles);
        const templateRoles = res['data']['templateRoles'];
        const templateViewsByAspect = objectMap(res['data']['templateViewsByAspect'], (view) => buildView(view));
        const enabledModules = res['data']['enabledModules'];
        return {courseRoles, rolesHierarchy, templateRoles, templateViewsByAspect, enabledModules}
      }) );
  }

  public previewTemplate(courseID: number, templateID: number, viewerRole: string, userRole?: string): Observable<View> {
    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'previewTemplate');
      qs.push('courseId', courseID);
      qs.push('templateId', templateID);
      qs.push('viewerRole', viewerRole);
      if (userRole) qs.push('userRole', userRole);
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);

    return this.get(url, ApiHttpService.httpOptions)
      .pipe( map((res: any) => buildView(res['data']['view'])) );
  }

  public saveTemplate(courseID: number, templateID: number, viewTree, viewsDeleted?: number[]): Observable<void> {
    const data = {
      courseId: courseID,
      templateId: templateID,
      template: viewTree
    }
    if (viewsDeleted?.length > 0) data['viewsDeleted'] = viewsDeleted;

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'saveTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
      .pipe( map((res: any) => res) );
  }

  public saveViewAsTemplate(courseID: number, templateName: string, viewTree, roleType: string, isRef: boolean): Observable<void> {
    const data = {
      courseId: courseID,
      templateName,
      view: viewTree,
      roleType,
      isRef
    }

    const params = (qs: QueryStringParameters) => {
      qs.push('module', ApiHttpService.VIEWS);
      qs.push('request', 'saveViewAsTemplate');
    };

    const url = this.apiEndpoint.createUrlWithQueryParameters('info.php', params);
    return this.post(url, data, ApiHttpService.httpOptions)
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
