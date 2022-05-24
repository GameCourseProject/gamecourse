import {User} from "./user";
import {Role, RoleDatabase} from "../roles/role";
import {AuthType} from "../auth/auth-type";
import {Moment} from "moment";
import {dateFromDatabase} from "../../_utils/misc/misc";

export class CourseUser extends User {
  private _roles: Role[];
  private _lastActivity: Moment;
  private _isActiveInCourse: boolean;

  constructor(id: number, name: string, email: string, major: string, nickname: string, studentNumber: number,
              isAdmin: boolean, isActive: boolean, username: string, authMethod: AuthType, photoUrl: string,
              lastLogin: Moment, roles: Role[], lastActivity: Moment, isActiveInCourse: boolean) {

    super(id, name, email, major, nickname, studentNumber, isAdmin, isActive, username, authMethod, photoUrl, lastLogin);

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

  static fromDatabase(obj: CourseUserDatabase): CourseUser {
    return new CourseUser(
      obj.id,
      obj.name,
      obj.email,
      obj.major,
      obj.nickname,
      obj.studentNumber,
      obj.isAdmin,
      obj.isActive,
      obj.username,
      obj.authentication_service as AuthType,
      obj.image,
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
  "isAdmin": boolean,
  "isActive": boolean,
  "username": string,
  "authentication_service": string,
  "image": string,
  "lastLogin": string,
  "roles": RoleDatabase[],
  "lastActivity": string,
  "isActiveInCourse": boolean
}
