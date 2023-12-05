import {Aspect} from "./aspects/aspect";
import {ViewType} from "./view-types/view-type";
import {VisibilityType} from "./visibility/visibility-type";
import {Variable} from "./variables/variable";
import {Event} from "./events/event";
import {EventType} from "./events/event-type";

import {buildEvent} from "./events/build-event";

export abstract class View {
  private _mode: ViewMode;
  private _type: ViewType;

  private _id: number;            // View ID (might be repeated for views w/ loopData)
  private _viewRoot: number;      // All aspects of view have the same viewRoot
  private _uniqueId: number;      // Unique view ID on frontend
  private _parent: View;
  private _aspect: Aspect;

  private _cssId?: string;
  private _classList?: string;
  private _styles?: string;

  private _visibilityType?: VisibilityType;
  private _visibilityCondition?: string | boolean;

  private _loopData?: string;

  private _variables?: Variable[];
  private _events?: Event[];


  protected constructor(mode: ViewMode, type: ViewType, id: number, viewRoot: number, parent: View, aspect: Aspect,
                        cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType,
                        visibilityCondition?: string | boolean, loopData?: string, variables?: Variable[],
                        events?: Event[]) {

    this.mode = mode;
    this.type = type;

    this.uniqueId = Math.round(Date.now() * Math.random());
    this.id = id;
    this.viewRoot = viewRoot;
    this.parent = parent;
    this.aspect = aspect;

    this.cssId = cssId;
    this.classList = classList;
    this.styles = styles;

    this.visibilityType = visibilityType;
    this.visibilityCondition = visibilityCondition;

    this.loopData = loopData;

    this.variables = variables;
    this.events = events;
  }


  get mode(): ViewMode {
    return this._mode;
  }

  set mode(value: ViewMode) {
    this._mode = value;
  }

  get type(): ViewType {
    return this._type;
  }

  set type(value: ViewType) {
    this._type = value;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get viewRoot(): number {
    return this._viewRoot;
  }

  set viewRoot(value: number) {
    this._viewRoot = value;
  }

  get uniqueId(): number {
    return this._uniqueId;
  }

  set uniqueId(value: number) {
    this._uniqueId = value;
  }

  get parent(): View {
    return this._parent;
  }

  set parent(value: View) {
    this._parent = value;
  }

  get aspect(): Aspect {
    return this._aspect;
  }

  set aspect(value: Aspect) {
    this._aspect = value;
  }

  get cssId(): string {
    return this._cssId;
  }

  set cssId(value: string) {
    this._cssId = value;
  }

  get classList(): string {
    return this._classList;
  }

  set classList(value: string) {
    this._classList = value;
  }

  get styles(): string {
    return this._styles;
  }

  set styles(value: string) {
    this._styles = value;
  }

  get visibilityType(): VisibilityType {
    return this._visibilityType;
  }

  set visibilityType(value: VisibilityType) {
    this._visibilityType = value;
  }

  get visibilityCondition(): string | boolean {
    return this._visibilityCondition;
  }

  set visibilityCondition(value: string | boolean) {
    this._visibilityCondition = value;
  }

  get loopData(): string {
    return this._loopData;
  }

  set loopData(value: string) {
    this._loopData = value;
  }

  get variables(): Variable[] {
    return this._variables;
  }

  set variables(value: Variable[]) {
    this._variables = value;
  }

  get events(): Event[] {
    return this._events;
  }

  set events(value: Event[]) {
    this._events = value;
  }


  abstract updateView(newView: View);

  abstract buildViewTree();

  abstract addChildViewToViewTree(view: View, options?: any);

  abstract removeChildView(childViewId: number);

  abstract replaceWithFakeIds(base?: number);

  abstract findParent(parentId: number): View;

  abstract findView(viewId: number): View;

  abstract switchMode(mode: ViewMode);


  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  static toJson(view: View) {
    return {
      type: view.type,
      id: view.id > 0 ? view.id : null, // NOTE: don't send fake IDs used only for editing
      viewRoot: view.viewRoot > 0 ? view.viewRoot : null,
      aspect: view.aspect,
      cssId: view.cssId || null,
      class: view.classList || null,
      style: view.styles || null,
      visibilityType: view.visibilityType || null,
      visibilityCondition: view.visibilityCondition || null,
      loopData: view.loopData || null,
      variables: view.variables.length > 0 ? view.variables.map(vr => { return {name: vr.name, value: vr.value, position: vr.position} }) : null,
      events: view.events.length > 0 ? view.events.map(ev => { return {type: ev.type, action: ev.print()} }) : null
    }
  }

  /**
   * Parses a view object into one where all fields are in the
   * correct type and format.
   *
   * @param obj
   */
  static parse(obj: ViewDatabase): {mode: ViewMode, type: ViewType, id: number, viewRoot: number, aspect: Aspect,
    cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType, visibilityCondition?: string | boolean,
    loopData?: string, variables?: Variable[], events?: Event[]} {

    const visibilityType = (obj.visibilityType || VisibilityType.VISIBLE) as VisibilityType;
    const visibilityCondition = visibilityType === VisibilityType.CONDITIONAL ? obj.visibilityCondition : null;

    return {
      mode: ViewMode.DISPLAY,
      type: obj.type as ViewType,

      id: obj.id,
      viewRoot: obj.viewRoot,
      aspect: Aspect.fromDatabase(obj.aspect),

      cssId: obj.cssId || null,
      classList: obj.class || null,
      styles: obj.style || null,

      visibilityType,
      visibilityCondition,

      loopData: obj.loopData || null,

      variables: obj.variables ? obj.variables.map(vObj => Variable.fromDatabase(vObj)) : [],
      events: obj.events ? obj.events.map(eObj => buildEvent(eObj.type as EventType, eObj.action)) : []
    }
  }

  /**
   * Calculates a unique fake ID based on the minimum fake ID used
   * so far, and the ID given.
   *
   * @param min
   * @param id
   * @return number
   */
  static calculateFakeId(min: number, id: number): number {
    if (id <= 0) return id;
    return -id + min;
  }
}

export interface ViewDatabase {
  id: number,
  viewRoot: number,
  aspect: {viewerRole: string, userRole: string},
  type: string;
  cssId?: string,
  class?: string,
  style?: string,
  visibilityType?: string,
  visibilityCondition?: string | boolean,
  loopData?: string,
  variables?: {name: string, value: string, position: number}[];
  events?: {type: string, action: string}[];
}

export enum ViewMode {
  DISPLAY = 'display',    // final appearence and behaviour
  PREVIEW = 'preview',    // used in the edit component modal
  EDIT = 'edit',          // default in views editor
  REARRANGE = 'rearrange' // rearrange in views editor
}

