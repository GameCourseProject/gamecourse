import {ViewImage, ViewImageDatabase} from "./view-image";
import {ViewText, ViewTextDatabase} from "./view-text";
import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view/build-view";
import {copyObject, exists} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";
import {baseFakeId, viewsAdded, viewTree} from "./build-view-tree/build-view-tree";
import {EventType} from "../events/event-type";
import {Event} from "../events/event";
import {Variable} from "../variables/variable";

export class ViewHeader extends View{

  private _image: ViewImage;
  private _title: ViewText;

  static readonly HEADER_CLASS = 'gc-header';
  static readonly IMAGE_CLASS = 'gc-header_image';
  static readonly TITLE_CLASS = 'gc-header_title';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, image: ViewImage, title: ViewText,
              loopData?: any, variables?: {[name: string]: Variable}, style?: string, cssId?: string, cl?: string, label?: string,
              visibilityType?: VisibilityType, visibilityCondition?: any, events?: {[key in EventType]?: Event}) {

    super(id, viewId, parentId, ViewType.HEADER, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

    this.image = image;
    this.title = title;
  }

  get image(): ViewImage {
    return this._image;
  }

  set image(value: ViewImage) {
    this._image = value;
  }

  get title(): ViewText {
    return this._title;
  }

  set title(value: ViewText) {
    this._title = value;
  }

  updateView(newView: View): ViewHeader {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      copy.image = this.image; // Keep same image
      copy.title = this.title; // Keep same title
      ViewSelectionService.unselect(copy);
      return copy as ViewHeader;
    }

    // Check if image
    const newImage = this.image.updateView(newView);
    if (newImage !== null) {
      this.image = newImage;
      return this;
    }

    // Check if title
    const newTitle = this.title.updateView(newView);
    if (newTitle !== null) {
      this.title = newTitle;
      return this;
    }

    return null;
  }

  buildViewTree() {
    if (exists(baseFakeId)) this.replaceWithFakeIds();

    if (!viewsAdded.has(this.id)) { // View hasn't been added yet
      const copy = copyObject(this);

      // Strip children
      copy.image = [];
      copy.title = [];

      if (this.parentId !== null) { // Has parent
        const parent = viewsAdded.get(this.parentId);
        parent.addChildViewToViewTree(copy);

      } else viewTree.push(copy); // Is root
      viewsAdded.set(copy.id, copy);
    }

    // Build image & title into view tree
    this.image.buildViewTree();
    this.title.buildViewTree();
  }

  addChildViewToViewTree(view: View) {
    if (view.type === ViewType.IMAGE)
      (this.image as any as View[]).push(view);

    if (view.type === ViewType.TEXT)
      (this.title as any as View[]).push(view);
  }

  removeChildView(childViewId: number) {
    // Doesn't have children, do nothing
  }

  replaceWithFakeIds(base?: number) {
    // Replace IDs in image & title
    this.image.replaceWithFakeIds(exists(base) ? base : null);
    this.title.replaceWithFakeIds(exists(base) ? base : null);

    const baseId = exists(base) ? base : baseFakeId;
    this.id = View.calculateFakeId(baseId, this.id);
    this.viewId = View.calculateFakeId(baseId, this.viewId);
    this.parentId = View.calculateFakeId(baseId, this.parentId);
  }

  findParent(parentId: number): View {
    if (this.id === parentId)  // Found parent
      return this;

    // Look for parent in image & title
    let parent = this.image.findParent(parentId);
    if (parent) return parent;

    parent = this.title.findParent(parentId);
    if (parent) return parent;

    return null;
  }

  findView(viewId: number): View {
    if (this.viewId === viewId) return this;

    // Look for view in image & title
    let found = this.image.findView(viewId);
    if (found) return this.image;

    found = this.title.findView(viewId);
    if (found) return this.title;

    return null;
  }

  /**
   * Gets a default view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewHeader {
    return new ViewHeader(id, id, parentId, role, ViewMode.EDIT,
      ViewImage.getDefault(id - 1, id, role, this.IMAGE_CLASS),
      ViewText.getDefault(id - 2, id, role, this.TITLE_CLASS),
      null, null, null, null,
      View.VIEW_CLASS + ' ' + this.HEADER_CLASS + (!!cl ? ' ' + cl : ''));
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      image: this.image,
      title: this.title,
    });
  }

  static fromDatabase(obj: ViewHeaderDatabase): ViewHeader {
    const parsedObj = View.parse(obj);

    const image = buildView(Object.assign(obj.image, {parentId: obj.id})) as ViewImage;
    image.class += ' ' + this.IMAGE_CLASS;
    const title = buildView(Object.assign(obj.title, {parentId: obj.id})) as ViewText;
    title.class += ' ' + this.TITLE_CLASS;

    return new ViewHeader(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      image,
      title,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.HEADER_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events
    );
  }
}

export interface ViewHeaderDatabase extends ViewDatabase {
  image: ViewImageDatabase;
  title: ViewTextDatabase;
}
