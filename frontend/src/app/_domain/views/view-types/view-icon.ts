import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import {getFakeId, viewTree, viewsAdded, addVariantToGroupedChildren} from "../build-view-tree/build-view-tree";
import * as _ from "lodash"

export class ViewIcon extends View {
  private _icon: string;
  private _size?: string;


  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, icon: string, size?: string,
              cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType, visibilityCondition?: string | boolean,
              loopData?: string, variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.ICON, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    this.icon = icon;
    this.size = size;
  }


  get icon(): string {
    return this._icon;
  }

  set icon(value: string) {
    this._icon = value;
  }

  get size(): string {
    return this._size;
  }

  set size(value: string) {
    this._size = value;
  }


  updateView(newView: View): ViewIcon { // TODO: refactor view editor
    // if (this.id === newView.id) {
    //   const copy = copyObject(newView);
    //   ViewSelectionService.unselect(copy);
    //   return copy as ViewText;
    // }
    return null;
  }

  buildViewTree() {
    const viewForDatabase = ViewIcon.toDatabase(this);

    if (!viewsAdded.has(this.id)) {
      if (this.parent) {
        const parent = viewsAdded.get(this.parent.id);
        const group = (parent as any).children.find((e) => e.includes(this.id));
        const index = group.indexOf(this.id);
        if (index != -1) {
          group.splice(index, 1, viewForDatabase);
        }
      }
      else viewTree.push(viewForDatabase); // Is root
    }
    viewsAdded.set(this.id, viewForDatabase);
  }

  addChildViewToViewTree(view: View) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  replaceWithFakeIds() {
    this.id = getFakeId();
  }

  findParent(parentId: number): View { // TODO: refactor view editor
    // Doesn't have children, cannot be parent
    return null;
  }

  findView(viewId: number): View {
    if (this.id === viewId) return this;
    else return null;
  }

  replaceView(viewId: number, view: View) {
  }

  switchMode(mode: ViewMode) {
    this.mode = mode;
  }

  // fixes the entire view to be visible to an aspect
  modifyAspect(aspectsToReplace: Aspect[], newAspect: Aspect) {
    if (aspectsToReplace.filter(e => _.isEqual(this.aspect, e)).length > 0) {
      const oldId = this.id;
      this.replaceWithFakeIds();
      this.aspect = newAspect;
      if (this.parent) addVariantToGroupedChildren(this.parent.id, oldId, this.id);
    }
  }

  // simply replaces without any other change (helper for the function above)
  replaceAspect(aspectsToReplace: Aspect[], newAspect: Aspect) {
    if (aspectsToReplace.filter(e => _.isEqual(this.aspect, e)).length > 0) {
      this.aspect = newAspect;
    }
  }

  /**
   * Gets a default icon view.
   */
  static getDefault(parent: View, viewRoot: number, id?: number, aspect?: Aspect): ViewIcon {
    return new ViewIcon(ViewMode.EDIT, id ?? getFakeId(), viewRoot, parent, aspect ?? new Aspect(null, null), "tabler-question-mark");
  }


  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      icon: this.icon,
      size: this.size,
    });
  }

  static fromDatabase(obj: ViewIconDatabase): ViewIcon {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type text
    return new ViewIcon(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      obj.icon,
      obj.size || null,
      parsedObj.cssId,
      parsedObj.classList,
      parsedObj.styles,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.events
    );
  }

  static toDatabase(obj: ViewIcon): ViewIconDatabase {
    return {
      id: obj.id,
      viewRoot: obj.viewRoot,
      aspect: Aspect.toDatabase(obj.aspect),
      type: obj.type,
      cssId: obj.cssId,
      class: obj.classList,
      style: obj.styles,
      visibilityType: obj.visibilityType,
      visibilityCondition: obj.visibilityCondition,
      loopData: obj.loopData,
      variables: obj.variables.map(variable => Variable.toDatabase(variable)),
      events: obj.events.map(event => Event.toDatabase(event)),
      icon: obj.icon,
      size: obj.size
    }
  }
}

export interface ViewIconDatabase extends ViewDatabase {
  icon: string;
  size?: string;
}
