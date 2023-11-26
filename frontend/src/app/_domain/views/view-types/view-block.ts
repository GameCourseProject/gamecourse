import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";

import {buildView} from "../build-view/build-view";
import { copyObject, exists } from "src/app/_utils/misc/misc";
import { baseFakeId, viewTree, viewsAdded } from "../build-view-tree/build-view-tree";
import { buildViewTree } from "src/app/_views/restricted/courses/course/settings/views/views-editor/views-editor.component";

export class ViewBlock extends View {
  private _direction: BlockDirection;
  private _columns: number;
  private _responsive: boolean;
  private _children: View[];


  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, direction: BlockDirection,
              columns: number, responsive: boolean, children: View[], cssId?: string, classList?: string, styles?: string,
              visibilityType?: VisibilityType, visibilityCondition?: string | boolean, loopData?: string,
              variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.BLOCK, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    this.direction = direction;
    this.columns = columns;
    this.responsive = responsive;
    this.children = children;
  }


  get direction(): BlockDirection {
    return this._direction;
  }

  set direction(value: BlockDirection) {
    this._direction = value;
  }

  get columns(): number {
    return this._columns;
  }

  set columns(value: number) {
    this._columns = value;
  }

  get responsive(): boolean {
    return this._responsive;
  }

  set responsive(value: boolean) {
    this._responsive = value;
  }

  get children(): View[] {
    return this._children;
  }

  set children(value: View[]) {
    this._children = value;
  }


  updateView(newView: View): ViewBlock { // TODO: refactor view editor
    // if (this.id === newView.id) {
    //   const copy = copyObject(newView);
    //   copy.children = this.children; // Keep same children
    //   ViewSelectionService.unselect(copy);
    //   return copy as ViewBlock;
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

  buildViewTree() { // TODO: refactor view editor
    if (exists(baseFakeId)) this.replaceWithFakeIds();

    if (!viewsAdded.has(this.id)) { // View hasn't been added yet
      const copy = copyObject(this);
      copy.children = []; // Strip children
  
      if (this.parent) { // Has parent
        const parent = viewsAdded.get(this.parent.id);
        parent.addChildViewToViewTree(copy);
      }
      else viewTree.push(copy); // Is root
        viewsAdded.set(copy.id, copy);
     }
    
    // // Build children into view tree
    for (const child of this.children) {
      child.buildViewTree();
    }
  }

  addChildViewToViewTree(view: View) { // TODO: refactor view editor
    view.parent = this;
    this.children.push(view);
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    const index = this.children.findIndex(child => child.id === childViewId);
    this.children.splice(index, 1);
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
    for (let child of this.children) {
      child.switchMode(mode);
    }
  }

  /**
   * Gets a default block view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewBlock { // TODO: refactor view editor
    return null;
    // return new ViewBlock(id, id, parentId, role, ViewMode.EDIT, [], null, null, null, null,
    //   View.VIEW_CLASS + ' ' + this.BLOCK_CLASS + (!!cl ? ' ' + cl : ''));
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      direction: this.direction,
      columns: this.columns,
      responsive: this.responsive,
      children: this.children
    });
  }

  static fromDatabase(obj: ViewBlockDatabase, edit: boolean): ViewBlock {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type block
    const block: ViewBlock = new ViewBlock(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      (obj.direction || BlockDirection.VERTICAL) as BlockDirection,
      obj.columns || null,
      obj.responsive,
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
    if (block.children.length > 0)
      block.children = block.children.map(child => { child.parent = block; return child; });

    return block;
  }

  static toDatabase(obj: ViewBlock): ViewBlockDatabase {
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
      direction: obj.direction,
      columns: obj.columns,
      responsive: obj.responsive,
      children: obj.children.map(child => buildViewTree(child))
    }
  }
}

export interface ViewBlockDatabase extends ViewDatabase {
  direction: string,
  columns?: number,
  responsive?: boolean,
  children?: ViewDatabase[] | ViewDatabase[][];
}

export enum BlockDirection {
  VERTICAL = 'vertical',
  HORIZONTAL = 'horizontal'
}
