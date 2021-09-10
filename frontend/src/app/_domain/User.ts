import {AuthType} from "./AuthType";
import {ApiEndpointsService} from "../_services/api/api-endpoints.service";

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

  constructor(id: number, name: string, email: string, major: string, nickname: string, studentNumber: number,
              isAdmin: boolean, isActive: boolean, username: string, authMethod: AuthType, photoUrl: string) {

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

  static fromDatabase(obj: UserDatabase): User {
    return new User(
      parseInt(obj.id),
      obj.name,
      obj.email,
      obj.major,
      obj.nickname,
      parseInt(obj.studentNumber),
      !!obj.isAdmin,
      !!obj.isActive,
      obj.username,
      obj.authenticationService as AuthType,
      ApiEndpointsService.API_ENDPOINT + '/photos/' + obj.username + '.png'
    );
  }
}

interface UserDatabase {
  "id": string,
  "name": string,
  "email": string,
  "major": string,
  "nickname": string,
  "studentNumber": string,
  "isAdmin": string,
  "isActive": string,
  "username": string,
  "authenticationService": string
}
