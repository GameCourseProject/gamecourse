import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import { buildViewTree } from "src/app/_views/restricted/courses/course/settings/views/views-editor/views-editor.component";
import { buildView } from "../build-view/build-view";


export class ViewRow extends View {
  private _rowType: RowType;
  private _children: View[];

  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, rowType: RowType, children: View[],
              cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType, visibilityCondition?: string | boolean,
              loopData?: string, variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.ROW, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    this.rowType = rowType;
    this.children = children;
  }


  get rowType(): RowType {
    return this._rowType;
  }

  set rowType(value: RowType) {
    this._rowType = value;
  }

  get children(): View[] {
    return this._children;
  }

  set children(value: View[]) {
    this._children = value;
  }


  updateView(newView: View): ViewRow { // TODO: refactor view editor
    // if (this.id === newView.id) {
    //   const copy = copyObject(newView);
    //   copy.children = this.children; // Keep same children
    //   ViewSelectionService.unselect(copy);
    //   return copy as ViewRow;
    // }
    //
    // // Check if child
    // for (let i = 0; i < this.children.length; i++) {
    //   const child = this.children[i];
    //   const newChild = child.updateView(newView);
    //   if (newChild !== null) {
    //     this.children[i] = newChild;
    //     return this;
    //   }
    // }

    return null;
  }

  buildViewTree(options?: 'header' | 'body') { // TODO: refactor view editor
    // if (exists(baseFakeId)) this.replaceWithFakeIds();
    //
    // if (!viewsAdded.has(this.id)) { // View hasn't been added yet
    //   const copy = copyObject(this);
    //   copy.children = []; // Strip children
    //
    //   if (this.parentId !== null) { // Has parent
    //     const parent = viewsAdded.get(this.parentId);
    //     parent.addChildViewToViewTree(copy, options);
    //
    //   } else viewTree.push(copy); // Is root
    //   viewsAdded.set(copy.id, copy);
    // }
    //
    // // Build children into view tree
    // for (const child of this.children) {
    //   child.buildViewTree();
    // }
  }

  addChildViewToViewTree(view: View) { // TODO: refactor view editor
    // for (const child of this.children) {
    //   if ((child as any as View[])[0].viewId === view.viewId) { // Found aspect it belongs
    //     (child as any as View[]).push(view);
    //     return;
    //   }
    // }
    // (this.children as any as View[][]).push([view]);  // No aspect found
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    // const index = this.children.findIndex(child => child.viewId === childViewId);
    // this.children.splice(index, 1);
  }

  replaceWithFakeIds(base?: number) { // TODO: refactor view editor
    // // Replace IDs in children
    // for (const child of this.children) {
    //   child.replaceWithFakeIds(exists(base) ? base : null);
    // }
    //
    // const baseId = exists(base) ? base : baseFakeId;
    // this.id = View.calculateFakeId(baseId, this.id);
    // this.viewId = View.calculateFakeId(baseId, this.viewId);
    // this.parentId = View.calculateFakeId(baseId, this.parentId);
  }

  findParent(parentId: number): View { // TODO: refactor view editor
    // if (this.id === parentId)  // Found parent
    //   return this;
    //
    // // Look for parent in children
    // for (const child of this.children) {
    //   const parent = child.findParent(parentId);
    //   if (parent) return parent;
    // }
    return null;
  }

  findView(viewId: number): View { // TODO: refactor view editor
    // if (this.viewId === viewId) return this;
    //
    // // Look for view in children
    // for (const child of this.children) {
    //   const found = child.findView(viewId);
    //   if (found) return child;
    // }
    return null;
  }

  switchMode(mode: ViewMode) {
    this.mode = mode;
  }


  /**
   * Gets a default row view.
   */
  static getDefault(table: View, parentId: number = null, aspect: Aspect, type: RowType): ViewRow { // TODO: refactor view editor
    return new ViewRow(ViewMode.DISPLAY, null, parentId, table, aspect, type, [],
      null, null, null, null, null, null, null);
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

  static fromDatabase(obj: ViewRowDatabase, edit: boolean): ViewRow {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type row
    const row: ViewRow = new ViewRow(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      (obj.rowType || RowType.BODY) as RowType,
      obj.children ? edit ? obj.children.map(child => buildView(child[0], true)) : obj.children.map(child => buildView(child)) : [],
      parsedObj.cssId,
      parsedObj.classList,
      parsedObj.styles,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.events
    );

    // Update children's parent
    if (row.children.length > 0)
      row.children = row.children.map(child => { child.parent = row; return child; });

    return row;
  }

  static toDatabase(obj: ViewRow): ViewRowDatabase {
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
      events: obj.events,
      rowType: obj.rowType,
      children: obj.children.map(child => buildViewTree(child))
    }
  }
}

export interface ViewRowDatabase extends ViewDatabase {
  rowType: RowType;
  children?: ViewDatabase[] | ViewDatabase[][];
}

export enum RowType {
  HEADER = 'header',
  BODY = 'body'
}
