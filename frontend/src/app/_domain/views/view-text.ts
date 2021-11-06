import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {copyObject} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";
import {viewsAdded, viewTree} from "./build-view-tree/build-view-tree";

export class ViewText extends View {

  private _value: string;
  private _link?: string;

  static readonly TEXT_CLASS = 'gc-text';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, value: string, loopData?: any,
              variables?: any, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, link?: any) {

    super(id, viewId, parentId, ViewType.TEXT, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

    this.value = value;
    if (link) this.link = link;
  }

  get value(): string {
    return this._value;
  }

  set value(value: string) {
    this._value = value;
  }

  get link(): string {
    return this._link;
  }

  set link(value: string) {
    this._link = value;
  }

  updateView(newView: View): ViewText {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      ViewSelectionService.unselect(copy);
      return copy as ViewText;
    }
    return null;
  }

  buildViewTree() {
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

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      value: this.value,
      link: this.link,
    });
  }

  static fromDatabase(obj: ViewTextDatabase): ViewText {
    const parsedObj = View.parse(obj);
    return new ViewText(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      obj.value,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.TEXT_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events,
      obj.link || null
    );
  }
}

export interface ViewTextDatabase extends ViewDatabase {
  value: string;
  link?: string;
}
