import {Role} from "../roles/role";
import {EventType} from "../events/event-type";
import {ViewType} from "./view-type";
import {Event} from "../events/event";
import {buildEvent} from "../events/build-event";
import {objectMap} from "../../_utils/misc/misc";

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
  private _parentId: number;  // FIXME: prob not using for anything in frontend, delete
  private _type: ViewType;
  private _role: string;
  private _mode: ViewMode;

  private _style?: string;
  private _cssId?: string;
  private _class?: string;
  private _label?: string;  // FIXME: use label in events or delete

  private _visibilityType?: VisibilityType;
  private _events?: {[key in EventType]?: Event};

  // Edit only params
  private _loopData?: any;
  private _variables?: any;
  private _visibilityCondition?: any;

  static readonly VIEW_CLASS = 'gc-view';


  protected constructor(id: number, viewId: number, parentId: number, type: ViewType, role: string, mode: ViewMode, loopData?: any,
                        variables?: any, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
                        visibilityCondition?: any, events?: {[key in EventType]?: Event}) {

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

  get style(): string {
    return this._style;
  }

  set style(value: string) {
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

  get events(): {[key in EventType]?: Event} {
    return this._events;
  }

  set events(value: {[key in EventType]?: Event}) {
    this._events = value;
  }

  abstract updateView(newView: View);

  abstract buildViewTree();

  abstract addChildViewToViewTree(view: View, options?: any);

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  static toJson(view: View) {
    return {
      id: view.id > 0 ? view.id : null, // don't send fake ids used only for editing
      viewId: view.viewId > 0 ? view.viewId : null,
      type: view.type,
      role: Role.unparse(view.role),
      style: view.style || null,
      cssId: view.cssId || null,
      class: view.class.split(' ').filter(cl => !cl.startsWith('gc-')).join(' ') || null,
      label: view.label || null,
      visibilityType: view.visibilityType,
      events: view.events ? objectMap(view.events, event => '{actions.' + (event as Event).print() + '}') : null,
      loopData: view.loopData,
      variables: view.variables,
      visibilityCondition: view.visibilityCondition
    }
  }

  /**
   * Parses a view object into one where all fields are in the
   * correct type and format.
   *
   * @param obj
   */
  static parse(obj: ViewDatabase): {id: number, viewId: number, parentId: number, type: ViewType, role: string, mode: ViewMode,
    loopData?: any, variables?: any, style?: string, cssId?: string, class?: string, label?: string, visibilityType?: VisibilityType,
    visibilityCondition?: any, events?: {[key in EventType]?: Event}} {

    return {
      id: parseInt(obj.id),
      viewId: parseInt(obj.viewId),
      parentId: parseInt(obj.parentId) || null,
      type: obj.type as ViewType,
      role: Role.parse(obj.role),
      mode: obj.edit ? ViewMode.EDIT : ViewMode.DISPLAY,
      loopData: obj.loopData || null,
      variables: obj.variables || null,
      style: (obj.style && !obj.style.isEmpty()) ? obj.style : null,
      cssId: (obj.cssId && !obj.cssId.isEmpty()) ? obj.cssId : null,
      class: (!obj.class || obj.class.isEmpty()) ? this.VIEW_CLASS : obj.class + ' ' + this.VIEW_CLASS,
      label: (obj.label && !obj.label.isEmpty()) ? obj.label : null,
      visibilityType: obj.visibilityType ? obj.visibilityType as VisibilityType : VisibilityType.VISIBLE,
      visibilityCondition: obj.visibilityType === VisibilityType.CONDITIONAL && obj.visibilityCondition && !obj.visibilityCondition.isEmpty() ? obj.visibilityCondition : null,
      events: obj.events && !!Object.keys(obj.events).length ?
              objectMap(obj.events, (eventStr, type) => buildEvent(type as EventType, eventStr)) : null,
    }
  }
}

export interface ViewDatabase {
  id: string;
  viewId: string;
  parentId: string;
  type: string;
  role: string;
  edit?: boolean;
  loopData?: string;
  variables?: any;
  style?: string;
  cssId?: string;
  class?: string;
  label?: string;
  visibilityType?: string;
  visibilityCondition?: string;
  events?: {[key in EventType]?: string};
}

