import {RoleTypeId} from "../../roles/role-type";

export class Page {
  private _id: number;
  private _course: number;
  private _name: string;
  private _isVisible: boolean;
  private _viewId: number;
  private _creationTimestamp: Date;
  private _updateTimestamp: Date;
  private _visibleFrom: Date;
  private _visibleUntil: Date;
  private _position: number;
  //private _roleType: RoleTypeId;
  //private _seqId: number;
  //private _theme: string;

  constructor(id: number, course: number, name: string, isVisible: boolean, viewRoot: number, creationTimestamp: string, updateTimestamp: string,
              visibleFrom: string, visibleUntil: string, position: number) {
    this._id = id;
    this._course = course;
    this._name = name;
    this._isVisible = isVisible;
    this._viewId = viewRoot;
    this._creationTimestamp = new Date(creationTimestamp);
    this._updateTimestamp = new Date(updateTimestamp);
    this._visibleFrom = new Date(visibleFrom);
    this._visibleUntil = new Date(visibleUntil);
    this._position = position;
    //this._roleType = roleType;
    //this._seqId = seqId;
    //this._theme = theme;
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

  get isVisible(): boolean {
    return this._isVisible;
  }

  set isVisible(value: boolean) {
    this._isVisible = value;
  }

  get viewId(): number {
    return this._viewId;
  }

  set viewId(value: number) {
    this._viewId = value;
  }

  get creationTimestamp(): Date {
    return this._creationTimestamp;
  }

  set creationTimestamp(value: Date) {
    this._creationTimestamp = value;
  }

  get updateTimestamp(): Date {
    return this._updateTimestamp;
  }

  set updateTimestamp(value: Date) {
    this._updateTimestamp = value;
  }

  get visibleFrom(): Date {
    return this._visibleFrom;
  }

  set visibleFrom(value: Date) {
    this._visibleFrom = value;
  }

  get visibleUntil(): Date {
    return this._visibleUntil;
  }

  set visibleUntil(value: Date) {
    this._visibleUntil = value;
  }

  get position(){
    return this._position;
  }

  set position(value: number){
    this._position = value;
  }

  /*get roleType(): RoleTypeId {
    return this._roleType;
  }

  set roleType(value: RoleTypeId) {
    this._roleType = value;
  }

  get seqId(): number {
    return this._seqId;
  }

  set seqId(value: number) {
    this._seqId = value;
  }

  get theme(): string {
    return this._theme;
  }

  set theme(value: string) {
    this._theme = value;
  }*/



  static fromDatabase(obj: PageDatabase): Page {
    return new Page(
      obj.id,
      obj.course,
      obj.name,
      obj.isVisible,
      obj.viewId ?? null,
      obj.creationTimestamp ?? null,
      obj.updateTimestamp ?? null,
      obj.visibleFrom ?? null,
      obj.visibleUntil ?? null,
      obj.position
      /*parseInt(obj.id) || null,
      obj.name,
      parseInt(obj.course) || null,
      parseInt(obj.viewId) || null,
      obj.roleType as RoleTypeId,
      parseInt(obj.seqId) || null,
      obj.theme,
      !!parseInt(obj.isEnabled)*/
    );
  }
}

export interface PageDatabase {
  id: number;
  name: string;
  course: number;
  isVisible: boolean;
  viewId: number;
  //roleType?: string;
  //seqId?: string;
  //theme?: string;
  creationTimestamp: string;
  updateTimestamp: string;
  visibleFrom: string;
  visibleUntil: string;
  position: number;
}
