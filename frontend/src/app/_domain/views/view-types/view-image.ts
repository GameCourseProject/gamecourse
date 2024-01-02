import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import { getFakeId, selectedAspect, viewTree, viewsAdded } from "../build-view-tree/build-view-tree";

export class ViewImage extends View {
  private _src: string;
  private _link?: string;

  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, src: string, link?: string,
              cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType,
              visibilityCondition?: string | boolean, loopData?: string, variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.IMAGE, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    this.src = src;
    this.link = link;
  }


  get src(): string {
    return this._src;
  }

  set src(value: string) {
    this._src = value;
  }

  get link(): string {
    return this._link;
  }

  set link(value: string) {
    this._link = value;
  }


  updateView(newView: View): ViewImage { // TODO: refactor view editor
    // if (this.id === newView.id) {
    //   const copy = copyObject(newView);
    //   ViewSelectionService.unselect(copy);
    //   return copy as ViewImage;
    // }
    return null;
  }

  buildViewTree() {
    const viewForDatabase = ViewImage.toDatabase(this);

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


  /**
   * Gets a default image view.
   */
  static getDefault(parent: View, viewRoot: number, id?: number, aspect?: Aspect): ViewImage {
    return new ViewImage(ViewMode.EDIT, id ?? getFakeId(), viewRoot, parent, aspect ?? selectedAspect, "assets/imgs/img-dark.png");
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      src: this.src,
      link: this.link,
    });
  }

  static fromDatabase(obj: ViewImageDatabase): ViewImage {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type image
    return new ViewImage(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      obj.src,
      obj.link || null,
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

  static toDatabase(obj: ViewImage): ViewImageDatabase {
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
      src: obj.src,
      link: obj.link
    }
  }
}

export interface ViewImageDatabase extends ViewDatabase {
  src: string;
  link?: string;
}
