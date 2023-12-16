import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";

import {buildView} from "../build-view/build-view";
import { getFakeId, groupedChildren, selectedAspect, viewTree, viewsAdded } from "../build-view-tree/build-view-tree";

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

  buildViewTree() {
    const viewForDatabase = ViewBlock.toDatabase(this);

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

    // Build children into view tree
    for (const child of this.children) {
      child.buildViewTree();
    }
  }

  addChildViewToViewTree(view: View) {
    view.parent = this;
    this.children.push(view);
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    const index = this.children.findIndex(child => child.id === childViewId);
    this.children.splice(index, 1);
  }

  replaceWithFakeIds() {
    this.id = getFakeId();
    // Replace IDs in children
    for (const child of this.children) {
      child.replaceWithFakeIds();
    }
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

  findView(viewId: number): View {
    if (this.id === viewId) return this;

    // Look for view in children
    for (const child of this.children) {
      const found = child.findView(viewId);
      if (found) return found;
    }
    return null;
  }

  replaceView(viewId: number, view: View) {
    // Look for view in children
    let index = 0;
    for (const child of this.children) {
      if (child.id === viewId) {
        this.children.splice(index, 1, view);
      }
      child.replaceView(viewId, view);
      index += 1;
    }
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
  static getDefault(parent: View, viewRoot: number, id?: number, aspect?: Aspect): ViewBlock {
    return new ViewBlock(ViewMode.EDIT, id ?? getFakeId(), viewRoot, parent, aspect ?? selectedAspect, BlockDirection.VERTICAL, null, true, []);
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
      children: groupedChildren.get(obj.id)
    }
  }
}

export interface ViewBlockDatabase extends ViewDatabase {
  direction: string,
  columns?: number,
  responsive?: boolean,
  children?: ViewDatabase[] | (number | ViewDatabase)[][];
}

export enum BlockDirection {
  VERTICAL = 'vertical',
  HORIZONTAL = 'horizontal'
}
