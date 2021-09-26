import {RoleType} from "./RoleType";

export class Template {
  private _id: number;
  private _name: string;
  private _courseId: number;
  private _isGlobal: boolean;
  private _roleTypeId: string;
  private _viewId: number;
  private _role: string; // FIXME: should be something else

  constructor(id: number, name: string, courseId: number, isGlobal: boolean, roleTypeId: string, viewId: number,
              role: string) {

    this._id = id;
    this._name = name;
    this._courseId = courseId;
    this._isGlobal = isGlobal;
    this._roleTypeId = roleTypeId;
    this._viewId = viewId;
    this._role = role;
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

  get courseId(): number {
    return this._courseId;
  }

  set courseId(value: number) {
    this._courseId = value;
  }

  get isGlobal(): boolean {
    return this._isGlobal;
  }

  set isGlobal(value: boolean) {
    this._isGlobal = value;
  }

  get roleTypeId(): string {
    return this._roleTypeId;
  }

  set roleTypeId(value: string) {
    this._roleTypeId = value;
  }

  get viewId(): number {
    return this._viewId;
  }

  set viewId(value: number) {
    this._viewId = value;
  }

  get role(): string {
    return this._role;
  }

  set role(value: string) {
    this._role = value;
  }

  static fromDatabase(obj: TemplateDatabase): Template {
    return new Template(
      parseInt(obj.id) || null,
      obj.name,
      parseInt(obj.course) || null,
      !!parseInt(obj.isGlobal),
      obj.roleType,
      parseInt(obj.viewId) || null,
      obj.role
    );
  }
}

interface TemplateDatabase {
  id: string;
  name: string;
  course: string;
  isGlobal: string;
  roleType: string;
  viewId: string;
  role: string;
}
