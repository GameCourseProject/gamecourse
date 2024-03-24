import {Moment} from "moment";
import {dateFromDatabase} from "../../_utils/misc/misc";
import {Role, RoleDatabase} from "../roles/role";

export class Course {
  private _id: number;
  private _name: string;
  private _short: string;
  private _color: string;
  private _year: string;
  private _startDate: Moment;
  private _endDate: Moment;
  private _landingPage: number;
  private _isActive: boolean;
  private _isVisible: boolean;
  private _roleHierarchy: Role[];
  private _theme: string;
  private _avatars: boolean;
  private _folder?: string;
  private _nrStudents?: number;

  constructor(id: number, name: string, short: string, color: string, year: string, startDate: Moment, endDate: Moment,
              landingPage: number, isActive: boolean, isVisible: boolean, roleHierarchy: Role[], theme: string, avatars: boolean,
              folder?: string, nrStudents?: number) {

    this._id = id;
    this._name = name;
    this._short = short;
    this._color = color;
    this._year = year;
    this._startDate = startDate;
    this._endDate = endDate;
    this._landingPage = landingPage;
    this._isActive = isActive;
    this._isVisible = isVisible;
    this._roleHierarchy = roleHierarchy;
    this._theme = theme;
    this._avatars = avatars;
    if (folder != undefined) this._folder = folder;
    if (nrStudents != undefined) this._nrStudents = nrStudents;
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

  get short(): string {
    return this._short;
  }

  set short(value: string) {
    this._short = value;
  }

  get color(): string {
    return this._color;
  }

  set color(value: string) {
    this._color = value;
  }

  get year(): string {
    return this._year;
  }

  set year(value: string) {
    this._year = value;
  }

  get startDate(): Moment {
    return this._startDate;
  }

  set startDate(value: Moment) {
    this._startDate = value;
  }

  get endDate(): Moment {
    return this._endDate;
  }

  set endDate(value: Moment) {
    this._endDate = value;
  }

  get landingPage(): number {
    return this._landingPage;
  }

  set landingPage(value: number) {
    this._landingPage = value;
  }

  get isActive(): boolean {
    return !!this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get isVisible(): boolean {
    return !!this._isVisible;
  }

  set isVisible(value: boolean) {
    this._isVisible = value;
  }

  get roleHierarchy(): Role[] {
    return this._roleHierarchy;
  }

  set roleHierarchy(value: Role[]) {
    this._roleHierarchy = value;
  }

  get theme(): string {
    return this._theme;
  }

  set theme(value: string) {
    this._theme = value;
  }

  get avatars(): boolean {
    return !!this._avatars;
  }

  set avatars(value: boolean) {
    this._avatars = value;
  }

  get folder(): string {
    return this._folder;
  }

  set folder(value: string) {
    this._folder = value;
  }

  get nrStudents(): number {
    return this._nrStudents;
  }

  set nrStudents(value: number) {
    this._nrStudents = value;
  }

  static fromDatabase(obj: CourseDatabase): Course {
    return new Course(
      obj.id,
      obj.name,
      obj.short,
      obj.color,
      obj.year,
      dateFromDatabase(obj.startDate),
      dateFromDatabase(obj.endDate),
      obj.landingPage,
      obj.isActive,
      obj.isVisible,
      obj.roleHierarchy.map(role => Role.fromDatabase(role)),
      obj.theme,
      obj.avatars,
      obj.folder ?? null,
      obj.nrStudents ?? null
    );
  }
}

export interface CourseDatabase {
  "id": number,
  "name": string,
  "short": string,
  "color": string,
  "year": string,
  "startDate": string,
  "endDate": string,
  "landingPage": number,
  "isActive": boolean,
  "isVisible": boolean,
  "roleHierarchy": RoleDatabase[],
  "theme": string,
  "avatars": boolean,
  "folder"?: string,
  "nrStudents"?: number,
}
