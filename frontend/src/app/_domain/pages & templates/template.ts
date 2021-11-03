import {RoleTypeId} from "../roles/role-type";

export class Template {
  private _id: number;
  private _name: string;
  private _courseId: number;
  private _isGlobal: boolean;
  private _roleType: RoleTypeId;
  private _viewId: number;

  constructor(id: number, name: string, courseId: number, isGlobal: boolean, roleType: RoleTypeId, viewId: number) {
    this.id = id;
    this.name = name;
    this.courseId = courseId;
    this.isGlobal = isGlobal;
    this.roleType = roleType;
    this.viewId = viewId;
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

  get roleType(): RoleTypeId {
    return this._roleType;
  }

  set roleType(value: RoleTypeId) {
    this._roleType = value;
  }

  get viewId(): number {
    return this._viewId;
  }

  set viewId(value: number) {
    this._viewId = value;
  }

  static fromDatabase(obj: TemplateDatabase): Template {
    return new Template(
      parseInt(obj.id),
      obj.name,
      parseInt(obj.course),
      !!parseInt(obj.isGlobal),
      obj.roleType as RoleTypeId,
      parseInt(obj.viewId)
    );
  }
}

export interface TemplateDatabase {
  id: string;
  name: string;
  course: string;
  isGlobal: string;
  roleType: string;
  viewId: string;
}
