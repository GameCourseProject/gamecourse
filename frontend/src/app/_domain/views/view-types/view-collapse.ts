import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import {buildView} from "../build-view/build-view";
import {ErrorService} from "../../../_services/error.service";
import {
  getFakeId,
  groupedChildren,
  viewTree,
  viewsAdded,
  addVariantToGroupedChildren, addToGroupedChildren
} from "../build-view-tree/build-view-tree";
import { ViewText } from "./view-text";
import { ViewBlock } from "./view-block";
import * as _ from "lodash"
import { buildComponent } from "src/app/_views/restricted/courses/course/settings/views/views-editor/views-editor.component";

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

  buildViewTree() {
    const viewForDatabase = ViewCollapse.toDatabase(this);

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
    this.header.buildViewTree();
    this.content.buildViewTree();
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

  replaceWithFakeIds() {
    this.id = getFakeId();

    this.content.replaceWithFakeIds();
    this.content.parent.id = this.id;

    this.header.replaceWithFakeIds();
    this.header.parent.id = this.id;
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
    if (this.header.id === viewId) return this.header;
    if (this.content.id === viewId) return this.content;

    const foundInHeader = this.header.findView(viewId);
    if (foundInHeader) return foundInHeader;

    const foundInContent = this.content.findView(viewId);
    if (foundInContent) return foundInContent;

    return null;
  }

  replaceView(viewId: number, view: View) {
  }

  switchMode(mode: ViewMode) {
    this.mode = mode;
    this.header.switchMode(mode);
    this.content.switchMode(mode);
  }

  // fixes the entire view to be visible to an aspect
  modifyAspect(aspectsToReplace: Aspect[], newAspect: Aspect) {
    if (aspectsToReplace.filter(e => _.isEqual(this.aspect, e)).length > 0) {
      const oldId = this.id;
      this.replaceWithFakeIds();
      this.aspect = newAspect;
      if (this.parent) addVariantToGroupedChildren(this.parent.id, oldId, this.id);
      addToGroupedChildren(this, this.parent?.id ?? null)
      this.header.replaceAspect(aspectsToReplace, newAspect);
      this.content.replaceAspect(aspectsToReplace, newAspect);
    }
    else {
      this.header.modifyAspect(aspectsToReplace, newAspect);
      this.content.modifyAspect(aspectsToReplace, newAspect);
    }
  }

  // simply replaces without any other change (helper for the function above)
  replaceAspect(aspectsToReplace: Aspect[], newAspect: Aspect) {
    if (aspectsToReplace.filter(e => _.isEqual(this.aspect, e)).length > 0) {
      this.aspect = newAspect;
    }
    this.header.replaceAspect(aspectsToReplace, newAspect);
    this.content.replaceAspect(aspectsToReplace, newAspect);
  }

  /**
   * Gets a default collapse view.
   */
  static getDefault(parent: View, viewRoot: number, id?: number, aspect?: Aspect): ViewCollapse {
    const defaultAspect = new Aspect(null, null);
    return new ViewCollapse(ViewMode.EDIT, id ?? getFakeId(), viewRoot, parent, aspect ?? defaultAspect, CollapseIcon.ARROW,
      [ViewText.getDefault(parent, viewRoot, getFakeId(), aspect ?? defaultAspect), ViewBlock.getDefault(parent, viewRoot, getFakeId(), aspect ?? defaultAspect)]);
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

  static toDatabase(obj: ViewCollapse, component: boolean = false): ViewCollapseDatabase {
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
      children: component ? [buildComponent(obj.header), buildComponent(obj.content)]
        : (groupedChildren.get(obj.id) ?? [])
    }
  }
}

export interface ViewCollapseDatabase extends ViewDatabase {
  icon?: string,
  children?: ViewDatabase[] | (number | ViewDatabase)[][];
}

export enum CollapseIcon {
  ARROW = 'arrow',
  PLUS = 'plus'
}
