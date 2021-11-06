import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view/build-view";
import {copyObject} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";
import {viewsAdded, viewTree} from "./build-view-tree/build-view-tree";

export class ViewRow extends View {

  private _children: View[];

  static readonly ROW_CLASS = 'gc-row';
  static readonly ROW_CHILDREN_CLASS = 'gc-row_children';
  static readonly ROW_EMPTY_CLASS = 'gc-row_empty';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, children: View[], loopData?: any,
              variables?: any, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any) {

    super(id, viewId, parentId, ViewType.ROW, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

    this.children = children;
  }

  get children(): View[] {
    return this._children;
  }

  set children(value: View[]) {
    this._children = value;
  }

  updateView(newView: View): ViewRow {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      copy.children = this.children; // Keep same children
      ViewSelectionService.unselect(copy);
      return copy as ViewRow;
    }

    // Check if child
    for (let i = 0; i < this.children.length; i++) {
      const child = this.children[i];
      const newChild = child.updateView(newView);
      if (newChild !== null) {
        this.children[i] = newChild;
        return this;
      }
    }

    return null;
  }

  buildViewTree(options?: 'header' | 'body') {
    if (!viewsAdded.has(this.id)) { // View hasn't been added yet
      const copy = copyObject(this);
      copy.children = []; // Strip children

      if (this.parentId !== null) { // Has parent
        const parent = viewsAdded.get(this.parentId);
        parent.addChildViewToViewTree(copy, options);

      } else viewTree.push(copy); // Is root
      viewsAdded.set(copy.id, copy);
    }

    // Build children into view tree
    for (const child of this.children) {
      child.buildViewTree();
    }
  }

  addChildViewToViewTree(view: View) {
    for (const child of this.children) {
      if ((child as any as View[])[0].viewId === view.viewId) { // Found aspect it belongs
        (child as any as View[]).push(view);
        return;
      }
    }
    (this.children as any as View[][]).push([view]);  // No aspect found
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      children: this.children,
    });
  }

  static fromDatabase(obj: ViewRowDatabase): ViewRow {
    const parsedObj = View.parse(obj);
    return new ViewRow(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      obj.children.map(child => buildView(Object.assign(child, {parentId: obj.id}))),
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.ROW_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events
    );
  }
}

export interface ViewRowDatabase extends ViewDatabase {
  children: ViewDatabase[];
}