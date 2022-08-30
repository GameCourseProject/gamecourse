import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {copyObject, exists} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";
import {baseFakeId, viewsAdded, viewTree} from "./build-view-tree/build-view-tree";
import {EventType} from "./events/event-type";
import {Event} from "./events/event";
import {Variable} from "./variables/variable";

export class ViewImage extends View {

  private _src: string;
  private _link: string;

  static readonly IMAGE_CLASS = 'gc-image';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, src: string, loopData?: any,
              variables?: {[name: string]: Variable}, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: {[key in EventType]?: Event}, link?: any) {

    super(id, viewId, parentId, ViewType.IMAGE, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

    this.src = src;
    if (link) this.link = link;
  }

  get src(): string {
    return this._src;
  }

  set src(value: string) {
    this._src = value;
  }

  get link(): string {
    return this._link;
  }

  set link(value: string) {
    this._link = value;
  }

  updateView(newView: View): ViewImage {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      ViewSelectionService.unselect(copy);
      return copy as ViewImage;
    }
    return null;
  }

  buildViewTree() {
    if (exists(baseFakeId)) this.replaceWithFakeIds();

    if (!viewsAdded.has(this.id)) { // View hasn't been added yet
      const copy = copyObject(this);
      if (this.parentId !== null) { // Has parent
        const parent = viewsAdded.get(this.parentId);
        parent.addChildViewToViewTree(copy);

      } else viewTree.push(copy); // Is root
      viewsAdded.set(copy.id, copy);
    }
  }

  addChildViewToViewTree(view: View) {
    // Doesn't have children, do nothing
  }

  removeChildView(childViewId: number) {
    // Doesn't have children, do nothing
  }

  replaceWithFakeIds(base?: number) {
    const baseId = exists(base) ? base : baseFakeId;
    this.id = View.calculateFakeId(baseId, this.id);
    this.viewId = View.calculateFakeId(baseId, this.viewId);
    this.parentId = View.calculateFakeId(baseId, this.parentId);
  }

  findParent(parentId: number): View {
    // Doesn't have children, cannot be parent
    return null;
  }

  findView(viewId: number): View {
    if (this.viewId === viewId) return this;
    return null;
  }

  /**
   * Gets a default view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewImage {
    return new ViewImage(id, id, parentId, role, ViewMode.EDIT, "", null, null, null, null,
      View.VIEW_CLASS + ' ' + this.IMAGE_CLASS + (!!cl ? ' ' + cl : ''));
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      src: this.src,
      link: this.link,
    });
  }

  static fromDatabase(obj: ViewImageDatabase): ViewImage {
    const parsedObj = View.parse(obj);
    return new ViewImage(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      obj.src,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.IMAGE_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events,
      obj.link || null
    );
  }
}

export interface ViewImageDatabase extends ViewDatabase {
  src: string;
  link?: string;
}
