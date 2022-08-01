import {AuthType} from "../auth/auth-type";
import {Moment} from "moment";
import {RoleDatabase} from "../roles/role";
import {dateFromDatabase} from "../../_utils/misc/misc";

export class User {
  private _id: number;
  private _name: string;
  private _email: string;
  private _major: string;
  private _nickname: string;
  private _studentNumber: number;
  private _isAdmin: boolean;
  private _isActive: boolean;
  private _username: string;
  private _authMethod: AuthType;
  private _photoUrl: string;
  private _lastLogin: Moment;
  private _nrCourses?: number;

  constructor(id: number, name: string, email: string, major: string, nickname: string, studentNumber: number,
              isAdmin: boolean, isActive: boolean, username: string, authMethod: AuthType, photoUrl: string,
              lastLogin: Moment, nrCourses?: number) {

    this._id = id;
    this._name = name;
    this._email = email;
    this._major = major;
    this._nickname = nickname;
    this._studentNumber = studentNumber;
    this._isAdmin = isAdmin;
    this._isActive = isActive;
    this._username = username;
    this._authMethod = authMethod;
    this._photoUrl = photoUrl;
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
      obj.email,
      obj.major,
      obj.nickname,
      obj.studentNumber,
      obj.isAdmin,
      obj.isActive,
      obj.username,
      obj.auth_service as AuthType,
      obj.image,
      dateFromDatabase(obj.lastLogin),
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
  "isAdmin": boolean,
  "isActive": boolean,
  "username": string,
  "auth_service": string,
  "image": string,
  "lastLogin": string
  "roles"?: RoleDatabase[],
  "nrCourses"?: number,
}
