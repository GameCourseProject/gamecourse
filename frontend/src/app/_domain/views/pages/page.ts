import {RoleTypeId} from "../../roles/role-type";
import {Moment} from "moment";
import {dateFromDatabase} from "../../../_utils/misc/misc";

export class Page {
  private _id: number;
  private _course: number;
  private _name: string;
  private _isVisible: boolean;
  private _viewRoot: number;
  private _creationTimestamp: Moment;
  private _updateTimestamp: Moment;
  private _visibleFrom: Moment;
  private _visibleUntil: Moment;
  private _position: number;
  private _isPublic: boolean;
  //private _roleType: RoleTypeId;
  //private _seqId: number;
  //private _theme: string;

  constructor(id: number, course: number, name: string, isVisible: boolean, viewRoot: number, creationTimestamp: Moment, updateTimestamp: Moment,
              visibleFrom: Moment, visibleUntil: Moment, position: number, isPublic: boolean) {
    this._id = id;
    this._course = course;
    this._name = name;
    this._isVisible = isVisible;
    this._viewRoot = viewRoot;
    this._creationTimestamp = creationTimestamp;
    this._updateTimestamp = updateTimestamp;
    this._visibleFrom = visibleFrom;
    this._visibleUntil = visibleUntil;
    this._position = position;
    this._isPublic = isPublic;
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
    return this._viewRoot;
  }

  set viewId(value: number) {
    this._viewRoot = value;
  }

  get creationTimestamp(): Moment {
    return this._creationTimestamp;
  }

  set creationTimestamp(value: Moment) {
    this._creationTimestamp = value;
  }

  get updateTimestamp(): Moment {
    return this._updateTimestamp;
  }

  set updateTimestamp(value: Moment) {
    this._updateTimestamp = value;
  }

  get visibleFrom(): Moment {
    return this._visibleFrom;
  }

  set visibleFrom(value: Moment) {
    this._visibleFrom = value;
  }

  get visibleUntil(): Moment {
    return this._visibleUntil;
  }

  set visibleUntil(value: Moment) {
    this._visibleUntil = value;
  }

  get position(){
    return this._position;
  }

  set position(value: number){
    this._position = value;
  }

  get isPublic(){
    return this._isPublic;
  }

  set isPublic(value: boolean){
    this._isPublic = value;
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
      obj.viewRoot ?? null,
      dateFromDatabase(obj.creationTimestamp),
      dateFromDatabase(obj.updateTimestamp),
      dateFromDatabase(obj.visibleFrom),
      dateFromDatabase(obj.visibleUntil),
      obj.position,
      obj.isPublic
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
  viewRoot: number;
  //roleType?: string;
  //seqId?: string;
  //theme?: string;
  creationTimestamp: string;
  updateTimestamp: string;
  visibleFrom: string;
  visibleUntil: string;
  position: number;
  isPublic: boolean;
}
