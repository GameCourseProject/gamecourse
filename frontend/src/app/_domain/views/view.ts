import {Role} from "../roles/role";
import {ViewType} from "./view-type";

export enum ViewMode {
  DISPLAY = 'display',
  EDIT = 'edit'
}

export enum VisibilityType {
  VISIBLE = 'visible',
  INVISIBLE = 'invisible',
  CONDITIONAL = 'conditional'
}

export abstract class View {

  private _id: number;          // Unique view id
  private _viewId: number;      // All aspects of the view have same viewId
  private _parentId: number;
  private _type: ViewType;
  private _role: string;
  private _mode: ViewMode;

  private _loopData?: any;

  private _variables?: any;

  private _style?: any;
  private _cssId?: string;
  private _class?: string;
  private _label?: string;

  private _visibilityType?: VisibilityType;
  private _visibilityCondition?: any;

  private _events?: any;

  static readonly VIEW_CLASS = 'view';


  constructor(id: number, viewId: number, parentId: number, type: ViewType, role: string, mode: ViewMode, loopData?: any,
              variables?: any, style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any) {

    this.id = id;
    this.viewId = viewId;
    this.parentId = parentId;
    this.type = type;
    this.role = role;
    this.mode = mode;
    this.loopData = loopData;
    this.variables = variables;
    this.style = style;
    this.cssId = cssId;
    this.class = cl;
    this.label = label;
    this.visibilityType = visibilityType;
    this.visibilityCondition = visibilityCondition;
    this.events = events;
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

  get role(): string {
    return this._role;
  }

  set role(value: string) {
    this._role = value;
  }

  get mode(): ViewMode {
    return this._mode;
  }

  set mode(value: ViewMode) {
    this._mode = value;
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

  /**
   * Parses a view object into one where all fields are in the
   * correct type and format.
   *
   * @param obj
   */
  static parse(obj: ViewDatabase): {id: number, viewId: number, parentId: number, type: ViewType, role: string, mode: ViewMode,
    loopData?: any, variables?: any, style?: any, cssId?: string, class?: string, label?: string, visibilityType?: VisibilityType,
    visibilityCondition?: any, events?: any} {

    return {
      id: parseInt(obj.id),
      viewId: parseInt(obj.viewId),
      parentId: parseInt(obj.parentId),
      type: obj.partType as ViewType,
      role: Role.parse(obj.role),
      mode: obj.edit ? ViewMode.EDIT : ViewMode.DISPLAY,
      loopData: obj.loopData || null,
      variables: obj.variables || null,
      style: (obj.style && !obj.style.isEmpty()) ? obj.style : null,
      cssId: (obj.cssId && !obj.cssId.isEmpty()) ? obj.cssId : null,
      class: (!obj.class || obj.class.isEmpty()) ? this.VIEW_CLASS : obj.class + ' ' + this.VIEW_CLASS,
      label: (obj.label && !obj.label.isEmpty()) ? obj.label : null,
      visibilityType: obj.visibilityType as VisibilityType || null,
      visibilityCondition: obj.visibilityCondition || null,
      events: obj.events || null,
    }
  }
}

export interface ViewDatabase {
  id: string;
  viewId: string;
  parentId: string;
  partType: string;
  role: string;
  edit: boolean;
  loopData?: string;
  variables?: any;
  style?: string;
  cssId?: string;
  class?: string;
  label?: string;
  visibilityType?: string;
  visibilityCondition?: string;
  events?: any;
}

