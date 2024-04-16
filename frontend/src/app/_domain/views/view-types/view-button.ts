import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import {getFakeId, viewTree, viewsAdded, addVariantToGroupedChildren} from "../build-view-tree/build-view-tree";
import * as _ from "lodash"

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

  buildViewTree() {
    const viewForDatabase = ViewButton.toDatabase(this);

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

  modifyAspect(old: Aspect, newAspect: Aspect, changeId: boolean = false) {
    if (_.isEqual(old, this.aspect)) {
      this.aspect = newAspect;
      if (changeId && this.parent) {
        const oldId = this.id;
        this.id = getFakeId();
        addVariantToGroupedChildren(this.parent.id, oldId, this.id);
      }
    }
  }

  /**
   * Gets a default button view.
   */
  static getDefault(parent: View, viewRoot: number, id?: number, aspect?: Aspect): ViewButton {
    return new ViewButton(ViewMode.EDIT, id ?? getFakeId(), viewRoot, parent, aspect ?? new Aspect(null, null),
      "", null, null, null, null, null, null, null, null, [], []);
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
