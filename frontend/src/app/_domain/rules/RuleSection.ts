import {Role, RoleDatabase} from "../roles/role";

export class RuleSection{

  private _id: number;
  private _course: number;
  private _name: string;
  private _position: number;
  private _module: string;
  private _isActive: boolean;
  private _loading: boolean;
  private _roles: Role[];

  constructor(id: number, course: number, name: string, position: number, module: string, isAcive: boolean, roles: Role[]) {
    this._id = id;
    this._course = course;
    this._name = name;
    this._position = position;
    this._module = module;
    this._isActive = isAcive;
    this._roles = roles;

    // for showing spinner individually
    this._loading = false;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get course(): number {
    return this._course;
  }

  set course(value: number) {
    this._course = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get position(): number {
    return this._position;
  }

  set position(value: number) {
    this._position = value;
  }

  get module (): string {
    return this._module;
  }

  set module(value: string) {
    this._module = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get loading(): boolean {
    return this._loading;
  }

  set loading(value: boolean) {
    this._loading = value;
  }

  get roles(): Role[] {
    return this._roles;
  }

  set roles(value: Role[]) {
    this._roles = value;
  }

  static fromDatabase(obj: RuleSectionDatabase): RuleSection {
    return new RuleSection(
      obj.id,
      obj.course,
      obj.name,
      obj.position,
      obj.module,
      obj.isActive,
      obj.roles?.map(role => Role.fromDatabase(role)),
    );
  }
}

interface RuleSectionDatabase {
  id: number,
  course: number,
  name: string,
  position: number,
  module: string,
  isActive: boolean,
  loading: boolean,
  roles: RoleDatabase[]
}
