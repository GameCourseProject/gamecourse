import {AuthType} from "../auth/auth-type";
import {Moment} from "moment";
import {RoleDatabase} from "../roles/role";
import {Theme} from "../../_services/theming/themes-available";
import {dateFromDatabase} from "../../_utils/misc/misc";

export class User {
  private _id: number;
  private _name: string;
  private _email: string;
  private _major: string;
  private _nickname: string;
  private _studentNumber: number;
  private _theme: Theme;
  private _isAdmin: boolean;
  private _isActive: boolean;
  private _username: string;
  private _authMethod: AuthType;
  private _photoUrl: string;
  private _avatarUrl: string;
  private _lastLogin: Moment;
  private _nrCourses?: number;

  constructor(id: number, name: string, email: string, major: string, nickname: string, studentNumber: number,
              theme: Theme, isAdmin: boolean, isActive: boolean, username: string, authMethod: AuthType, photoUrl: string,
              avatarUrl: string, lastLogin: Moment, nrCourses?: number) {

    this._id = id;
    this._name = name;
    this._email = email;
    this._major = major;
    this._nickname = nickname;
    this._studentNumber = studentNumber;
    this._theme = theme;
    this._isAdmin = isAdmin;
    this._isActive = isActive;
    this._username = username;
    this._authMethod = authMethod;
    this._photoUrl = photoUrl;
    this._avatarUrl = avatarUrl;
    this._lastLogin = lastLogin;
    if (nrCourses !== undefined) this._nrCourses = nrCourses;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get email(): string {
    return this._email;
  }

  set email(value: string) {
    this._email = value;
  }

  get major(): string {
    return this._major;
  }

  set major(value: string) {
    this._major = value;
  }

  get nickname(): string {
    return this._nickname;
  }

  set nickname(value: string) {
    this._nickname = value;
  }

  get studentNumber(): number {
    return this._studentNumber;
  }

  set studentNumber(value: number) {
    this._studentNumber = value;
  }

  get theme(): Theme {
    return this._theme;
  }

  set theme(value: Theme) {
    this._theme = value;
  }

  get isAdmin(): boolean {
    return !!this._isAdmin;
  }

  set isAdmin(value: boolean) {
    this._isAdmin = value;
  }

  get isActive(): boolean {
    return !!this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get username(): string {
    return this._username;
  }

  set username(value: string) {
    this._username = value;
  }

  get authMethod(): AuthType {
    return this._authMethod;
  }

  set authMethod(value: AuthType) {
    this._authMethod = value;
  }

  get photoUrl(): string {
    return this._photoUrl;
  }

  set photoUrl(value: string) {
    this._photoUrl = value;
  }

  get avatarUrl(): string {
    return this._avatarUrl;
  }

  set avatarUrl(value: string) {
    this._avatarUrl = value;
  }

  get lastLogin(): Moment {
    return this._lastLogin;
  }

  set lastLogin(value: Moment) {
    this._lastLogin = value;
  }

  get nrCourses(): number {
    return this._nrCourses;
  }

  set nrCourses(value: number) {
    this._nrCourses = value;
  }

  static fromDatabase(obj: UserDatabase): User {
    return new User(
      obj.id,
      obj.name,
      obj.email ?? null,
      obj.major,
      obj.nickname,
      obj.studentNumber,
      obj.theme as Theme ?? null,
      obj.isAdmin ?? null,
      obj.isActive ?? null,
      obj.username,
      obj.auth_service as AuthType,
      obj.image,
      obj.avatar,
      dateFromDatabase(obj.lastLogin) ?? null,
      obj.nrCourses ?? null
    );
  }
}

interface UserDatabase {
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
  "lastLogin": string
  "roles"?: RoleDatabase[],
  "nrCourses"?: number,
}
