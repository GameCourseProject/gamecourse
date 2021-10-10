import {Role} from "../roles/role";
import {ViewBlock, ViewBlockDatabase} from "./view-block";
import {ViewText, ViewTextDatabase} from "./view-text";
import {ViewImage, ViewImageDatabase} from "./view-image";
import {ViewTable, ViewTableDatabase} from "./view-table";
import {ViewRow, ViewRowDatabase} from "./view-row";

export enum ViewType {
  TEXT = 'text',
  IMAGE = 'image',
  TABLE = 'table',
  TABLE_HEADER_ROW = 'headerRow',
  TABLE_ROW = 'row',
  BLOCK = 'block'
}

export enum VisibilityType {
  VISIBLE = 'visible',
  INVISIBLE = 'invisible',
  CONDITIONAL = 'conditional'
}

export abstract class View {

  private _id: number;                    // Unique view id
  private _viewId: number;                // All aspects of the view have same viewId
  private _parentId: number;
  private _type: ViewType;
  private _role: Role;

  private _loopData?: any;

  private _variables?: any;

  private _style?: any;
  private _cssId?: string;
  private _class?: string;
  private _label?: string;

  private _visibilityType?: VisibilityType;
  private _visibilityCondition?: any;

  private _events?: any;

  private _link?: any;
  private _info?: any;


  constructor(id: number, viewId: number, parentId: number, type: ViewType, role: Role, loopData?: any,
              variables?: any, style?: any, cssId?: string, cl?: string, label?: string,
              visibilityType?: VisibilityType, visibilityCondition?: any, events?: any, link?: any, info?: any) {

    this.id = id;
    this.viewId = viewId;
    this.parentId = parentId;
    this.type = type;
    this.role = role;
    this.loopData = loopData;
    this.variables = variables;
    this.style = style;
    this.cssId = cssId;
    this.class = cl;
    this.label = label;
    this.visibilityType = visibilityType;
    this.visibilityCondition = this.visibilityCondition;
    this.events = events;
    this.link = link;
    this.info = info;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get viewId(): number {
    return this._viewId;
  }

  set viewId(value: number) {
    this._viewId = value;
  }

  get parentId(): number {
    return this._parentId;
  }

  set parentId(value: number) {
    this._parentId = value;
  }

  get type(): ViewType {
    return this._type;
  }

  set type(value: ViewType) {
    this._type = value;
  }

  get role(): Role {
    return this._role;
  }

  set role(value: Role) {
    this._role = value;
  }

  get loopData(): any {
    return this._loopData;
  }

  set loopData(value: any) {
    this._loopData = value;
  }

  get variables(): any {
    return this._variables;
  }

  set variables(value: any) {
    this._variables = value;
  }

  get style(): any {
    return this._style;
  }

  set style(value: any) {
    this._style = value;
  }

  get cssId(): string {
    return this._cssId;
  }

  set cssId(value: string) {
    this._cssId = value;
  }

  get class(): string {
    return this._class;
  }

  set class(value: string) {
    this._class = value;
  }

  get label(): string {
    return this._label;
  }

  set label(value: string) {
    this._label = value;
  }

  get visibilityType(): VisibilityType {
    return this._visibilityType;
  }

  set visibilityType(value: VisibilityType) {
    this._visibilityType = value;
  }

  get visibilityCondition(): any {
    return this._visibilityCondition;
  }

  set visibilityCondition(value: any) {
    this._visibilityCondition = value;
  }

  get events(): any {
    return this._events;
  }

  set events(value: any) {
    this._events = value;
  }

  get link(): any {
    return this._link;
  }

  set link(value: any) {
    this._link = value;
  }

  get info(): any {
    return this._info;
  }

  set info(value: any) {
    this._info = value;
  }

  static fromDatabase(obj: ViewDatabase): View {
    const type = obj.partType;
    if (type === ViewType.TEXT) return ViewText.fromDatabase(obj as ViewTextDatabase);
    else if (type === ViewType.IMAGE) return ViewImage.fromDatabase(obj as ViewImageDatabase);
    else if (type === ViewType.TABLE) return ViewTable.fromDatabase(obj as ViewTableDatabase);
    else if (type === ViewType.TABLE_HEADER_ROW || type === ViewType.TABLE_HEADER_ROW) return ViewRow.fromDatabase(obj as ViewRowDatabase);
    else if (type === ViewType.BLOCK) return ViewBlock.fromDatabase(obj as ViewBlockDatabase);
    return null;
  }

  /**
   * Parses a view object into one where all fields
   * are in the correct type and format.
   *
   * @param obj
   */
  static parse(obj: ViewDatabase): {id: number, viewId: number, parentId: number, type: ViewType, role: Role, loopData?: any,
    variables?: any, style?: any, cssId?: string, class?: string, label?: string,
    visibilityType?: VisibilityType, visibilityCondition?: any, events?: any, link?: any, info?: any} {

    return {
      id: parseInt(obj.id),
      viewId: parseInt(obj.viewId),
      parentId: parseInt(obj.parentId),
      type: ViewType[obj.partType],
      role: Role.fromDatabase({name: obj.role.replace('role.', '')}),
      loopData: obj.loopData || null,
      variables: obj.variables || null,
      style: obj.style || null,
      cssId: obj.cssId || null,
      class: obj.class || null,
      label: obj.label || null,
      visibilityType: VisibilityType[obj.visibilityType] || null,
      visibilityCondition: obj.visibilityCondition || null,
      events: obj.events || null,
      link: obj.link || null,
      info: obj.info || null
    }
  }
}

export interface ViewDatabase {
  id: string;
  viewId: string;
  parentId: string;
  role: string;
  partType: string;
  label: string;
  loopData: string;
  variables: string;
  class: string;
  cssId: string;
  style: string;
  link: string;
  visibilityCondition: string;
  visibilityType: string;
  events: string;
  info: string;
}
