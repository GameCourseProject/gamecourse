import {Moment} from "moment";
import {CourseDatabase} from "./Course";

export class Role { // FIXME: verify fields
  private _id?: number;
  private _name?: string;
  private _landingPage?: string;
  private _courseID?: number;
  private _isCourseAdmin?: boolean;
  private _createdAt?: Moment;
  private _updatedAt?: Moment;

  constructor(id?: number, name?: string, landingPage?: string, courseId?: number, isCourseAdmin?: boolean,
              createdAt?: Moment, updatedAt?: Moment) {

    this._id = id;
    this._name = name;
    this._landingPage = landingPage;
    this._courseID = courseId;
    this._isCourseAdmin = isCourseAdmin;
    this._createdAt = createdAt;
    this._updatedAt = updatedAt;
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

  get landingPage(): string {
    return this._landingPage;
  }

  set landingPage(value: string) {
    this._landingPage = value;
  }

  get courseID(): number {
    return this._courseID;
  }

  set courseID(value: number) {
    this._courseID = value;
  }

  get isCourseAdmin(): boolean {
    return !!this._isCourseAdmin;
  }

  set isCourseAdmin(value: boolean) {
    this._isCourseAdmin = value;
  }

  get createdAt(): Moment {
    return this._createdAt;
  }

  set createdAt(value: Moment) {
    this._createdAt = value;
  }

  get updatedAt(): Moment {
    return this._updatedAt;
  }

  set updatedAt(value: Moment) {
    this._updatedAt = value;
  }

  static fromDatabase(obj: RoleDatabase): Role {
    return new Role(null, obj.name);
  }
}

interface RoleDatabase {
  name: string
}
