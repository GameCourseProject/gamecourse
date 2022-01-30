import {RoleTypeId} from "../roles/role-type";
import {Role} from "../roles/role";

export class Template {
  private _id: number;
  private _name: string;
  private _courseId: number;
  private _isGlobal: boolean;
  private _roleType: RoleTypeId;
  private _viewId: number;
  private _roles?: {viewerRole: Role, userRole?: Role}[];

  constructor(id: number, name: string, courseId: number, isGlobal: boolean, roleType: RoleTypeId, viewId: number, roles?: {viewerRole: Role, userRole?: Role}[]) {
    this.id = id;
    this.name = name;
    this.courseId = courseId;
    this.isGlobal = isGlobal;
    this.roleType = roleType;
    this.viewId = viewId;
    if (roles)  this.roles = roles;
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

  get roles(): {viewerRole: Role, userRole?: Role}[] {
    return this._roles;
  }

  set roles(value: {viewerRole: Role, userRole?: Role}[]) {
    this._roles = value;
  }

  static fromDatabase(obj: TemplateDatabase): Template {
    return new Template(
      parseInt(obj.id),
      obj.name,
      parseInt(obj.course),
      !!parseInt(obj.isGlobal),
      obj.roleType as RoleTypeId,
      parseInt(obj.viewId),
      obj.roles ? this.parseRoles(obj.roles, obj.roleType) : null
    );
  }

  static parseRoles(roles: string[], roleType: string): {viewerRole: Role, userRole?: Role}[] {
    const parsedRoles: {viewerRole: Role, userRole?: Role}[] = [];
    roles.forEach(role => {
      let roleObj: {viewerRole: Role, userRole?: Role };
      if (roleType === RoleTypeId.ROLE_SINGLE) {
        roleObj = { viewerRole: Role.fromDatabase({name: role}) };

      } else if (roleType === RoleTypeId.ROLE_INTERACTION) {
        roleObj = {
          viewerRole: Role.fromDatabase({name: role.split('>')[1]}),
          userRole: Role.fromDatabase({name: role.split('>')[0]})
        };
      }
      parsedRoles.push(roleObj);
    });
    return parsedRoles;
  }
}

export interface TemplateDatabase {
  id: string;
  name: string;
  course: string;
  isGlobal: string;
  roleType: string;
  viewId: string;
  roles?: string[];
}
