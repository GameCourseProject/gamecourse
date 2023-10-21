import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import { copyObject, exists } from "src/app/_utils/misc/misc";
import { baseFakeId, viewTree, viewsAdded } from "../build-view-tree/build-view-tree";

export class ViewButton extends View {
  private _text: string;
  private _color?: string;
  private _icon?: string;


  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, text: string, color?: string,
              icon?: string, cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType,
              visibilityCondition?: string | boolean, loopData?: string, variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.BUTTON, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    this.text = text;
    this.color = color;
    this.icon = icon;
  }


  get text(): string {
    return this._text;
  }

  set text(value: string) {
    this._text = value;
  }

  get color(): string {
    return this._color;
  }

  set color(value: string) {
    this._color = value;
  }

  get icon(): string {
    return this._icon;
  }

  set icon(value: string) {
    this._icon = value;
  }


  updateView(newView: View): ViewButton { // TODO: refactor view editor
    // if (this.id === newView.id) {
    //   const copy = copyObject(newView);
    //   ViewSelectionService.unselect(copy);
    //   return copy as ViewText;
    // }
    return null;
  }

  buildViewTree() { // TODO: refactor view editor
    if (exists(baseFakeId)) this.replaceWithFakeIds();

    if (!viewsAdded.has(this.id)) { // View hasn't been added yet
      const copy = copyObject(this);
      if (this.parent) { // Has parent
        const parent = viewsAdded.get(this.parent.id);
        parent.addChildViewToViewTree(copy);
      }
      else viewTree.push(copy); // Is root
        viewsAdded.set(copy.id, copy);
     }
  }

  addChildViewToViewTree(view: View) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  replaceWithFakeIds(base?: number) { // TODO: refactor view editor
    // const baseId = exists(base) ? base : baseFakeId;
    // this.id = View.calculateFakeId(baseId, this.id);
    // this.viewId = View.calculateFakeId(baseId, this.viewId);
    // this.parentId = View.calculateFakeId(baseId, this.parentId);
  }

  findParent(parentId: number): View { // TODO: refactor view editor
    // Doesn't have children, cannot be parent
    return null;
  }

  findView(viewId: number): View { // TODO: refactor view editor
    // if (this.viewId === viewId) return this;
    return null;
  }

  switchMode(mode: ViewMode) {
    this.mode = mode;
  }

  /**
   * Gets a default button view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewButton { // TODO: refactor view editor
    return null;
    // return new ViewText(id, id, parentId, role, ViewMode.EDIT, "", null, null, null, null,
    //   View.VIEW_CLASS + ' ' + this.TEXT_CLASS + (!!cl ? ' ' + cl : ''));
  }


  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      text: this.text,
      color: this.color,
      icon: this.icon,
    });
  }

  static fromDatabase(obj: ViewButtonDatabase): ViewButton {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type text
    return new ViewButton(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      obj.text,
      obj.color || null,
      obj.icon || null,
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

  static toDatabase(obj: ViewButton): ViewButtonDatabase {
    return {
      id: obj.id,
      viewRoot: obj.viewRoot,
      aspect: obj.aspect,
      type: obj.type,
      cssId: obj.cssId,
      class: obj.classList,
      style: obj.styles,
      visibilityType: obj.visibilityType,
      visibilityCondition: obj.visibilityCondition,
      loopData: obj.loopData,
      variables: obj.variables,
      events: obj.events,
      text: obj.text,
      color: obj.color,
      icon: obj.icon
    }
  }

}

export interface ViewButtonDatabase extends ViewDatabase {
  text: string;
  color?: string;
  icon?: string;
}
