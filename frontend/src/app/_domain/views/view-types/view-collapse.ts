import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";

import {buildView} from "../build-view/build-view";

import {ErrorService} from "../../../_services/error.service";
import { buildViewTree } from "src/app/_views/restricted/courses/course/settings/views/views-editor/views-editor.component";

export class ViewCollapse extends View {
  private _icon: CollapseIcon;
  private _header: View;
  private _content: View;


  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, icon: CollapseIcon,
              children: View[], cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType,
              visibilityCondition?: string | boolean, loopData?: string, variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.COLLAPSE, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    check(children);

    this.icon = icon;
    this.header = children[0];
    this.content = children[1];

    function check(children: View[]) {
      if (children.length < 1)
        ErrorService.set('Error: Couldn\'t create collapse - no header found. (view-collapse.ts)');

      else if (children.length < 2)
        ErrorService.set('Error: Couldn\'t create collapse - no content found. (view-collapse.ts)');
    }
  }


  get icon(): CollapseIcon {
    return this._icon;
  }

  set icon(value: CollapseIcon) {
    this._icon = value;
  }

  get header(): View {
    return this._header;
  }

  set header(value: View) {
    this._header = value;
  }

  get content(): View {
    return this._content;
  }

  set content(value: View) {
    this._content = value;
  }


  updateView(newView: View): ViewCollapse { // TODO: refactor view editor
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
    // if (exists(baseFakeId)) this.replaceWithFakeIds();
    //
    // if (!viewsAdded.has(this.id)) { // View hasn't been added yet
    //   const copy = copyObject(this);
    //   copy.children = []; // Strip children
    //
    //   if (this.parentId !== null) { // Has parent
    //     const parent = viewsAdded.get(this.parentId);
    //     parent.addChildViewToViewTree(copy);
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
    // TODO
  }

  /**
   * Gets a default collapse view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewCollapse { // TODO: refactor view editor
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
      icon: this.icon || null,
      children: [this.header, this.content]
    });
  }

  static fromDatabase(obj: ViewCollapseDatabase, edit: boolean): ViewCollapse {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type collapse
    const collapse: ViewCollapse = new ViewCollapse(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      (obj.icon as CollapseIcon) || null,
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

    // Update header and content parent
    collapse.header.parent = collapse;
    collapse.content.parent = collapse;

    return collapse;
  }

  static toDatabase(obj: ViewCollapse): ViewCollapseDatabase {
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
      variables: obj.variables.map(variable => Variable.toDatabase(variable)),
      events: obj.events,
      icon: obj.icon,
      children: [buildViewTree(obj.header), buildViewTree(obj.content)]
    }
  }
}

export interface ViewCollapseDatabase extends ViewDatabase {
  icon?: string,
  children?: ViewDatabase[] | ViewDatabase[][];
}

export enum CollapseIcon {
  ARROW = 'arrow',
  PLUS = 'plus'
}
