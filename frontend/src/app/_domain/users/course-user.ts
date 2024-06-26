import {User} from "./user";
import {Role, RoleDatabase} from "../roles/role";
import {AuthType} from "../auth/auth-type";
import {Theme} from "../../_services/theming/themes-available";
import {Moment} from "moment";
import {dateFromDatabase} from "../../_utils/misc/misc";
import {LoadingState} from "../modules/module";
import {ApiHttpService} from "../../_services/api/api-http.service";
import {ErrorService} from "../../_services/error.service";

export class CourseUser extends User {
  private _roles: Role[];
  private _lastActivity: Moment;
  private _isActiveInCourse: boolean;

  static activityRefreshState: Map<number, LoadingState> = new Map<number, LoadingState>();

  constructor(id: number, name: string, email: string, major: string, nickname: string, studentNumber: number, theme: Theme,
              isAdmin: boolean, isActive: boolean, username: string, authMethod: AuthType, photoUrl: string, avatarUrl: string,
              lastLogin: Moment, roles: Role[], lastActivity: Moment, isActiveInCourse: boolean) {

    super(id, name, email, major, nickname, studentNumber, theme, isAdmin, isActive, username, authMethod, photoUrl, avatarUrl, lastLogin);

    this._roles = roles;
    this._lastActivity = lastActivity;
    this._isActiveInCourse = isActiveInCourse;
  }

  get roles(): Role[] {
    return this._roles;
  }

  set roles(value: Role[]) {
    this._roles = value;
  }

  get lastActivity(): Moment {
    return this._lastActivity;
  }

  set lastActivity(value: Moment) {
    this._lastActivity = value;
  }

  get isActiveInCourse(): boolean {
    return this._isActiveInCourse;
  }

  set isActiveInCourse(value: boolean) {
    this._isActiveInCourse = value;
  }

  /**
   * Refreshes course user activity.
   *
   * @param courseID
   */
  static refreshActivity(courseID: number): void {
    this.activityRefreshState.set(courseID, LoadingState.PENDING);

    ApiHttpService.refreshCourseUserActivity(courseID)
      .subscribe(
        lastActivity => this.activityRefreshState.delete(courseID),
          error => ErrorService.set(error)
      );
  }

  static fromDatabase(obj: CourseUserDatabase): CourseUser {
    return new CourseUser(
      obj.id,
      obj.name,
      obj.email,
      obj.major,
      obj.nickname,
      obj.studentNumber,
      obj.theme as Theme ?? null,
      obj.isAdmin,
      obj.isActive,
      obj.username,
      obj.auth_service as AuthType,
      obj.image,
      obj.avatar,
      dateFromDatabase(obj.lastLogin),
      obj.roles.map(role => Role.fromDatabase(role)),
      dateFromDatabase(obj.lastActivity),
      obj.isActiveInCourse
    );
  }
}

interface CourseUserDatabase {
  "id": number,
  "name": string,
  "email": string,
  "major": string,
  "nickname": string,
  "studentNumber": number,
  "theme": string,
  "isAdmin": boolean,
  "isActive": boolean,
  "username": string,
  "auth_service": string,
  "image": string,
  "avatar": string,
  "lastLogin": string,
  "roles": RoleDatabase[],
  "lastActivity": string,
  "isActiveInCourse": boolean
}
