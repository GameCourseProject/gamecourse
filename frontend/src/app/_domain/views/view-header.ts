import {ViewImage, ViewImageDatabase} from "./view-image";
import {ViewText, ViewTextDatabase} from "./view-text";
import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view/build-view";
import {copyObject} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";
import {viewsAdded, viewTree} from "./build-view-tree/build-view-tree";

export class ViewHeader extends View{

  private _image: ViewImage;
  private _title: ViewText;

  static readonly HEADER_CLASS = 'gc-header';
  static readonly IMAGE_CLASS = 'gc-header_image';
  static readonly TITLE_CLASS = 'gc-header_title';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, image: ViewImage, title: ViewText,
              loopData?: any, variables?: any, style?: string, cssId?: string, cl?: string, label?: string,
              visibilityType?: VisibilityType, visibilityCondition?: any, events?: any) {

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
