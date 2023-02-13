import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";

export class ViewText extends View {
  private _text: string;
  private _link?: string;


  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, text: string, link?: string,
              cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType, visibilityCondition?: string | boolean,
              loopData?: string, variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.TEXT, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    this.text = text;
    this.link = link;
  }


  get text(): string {
    return this._text;
  }

  set text(value: string) {
    this._text = value;
  }

  get link(): string {
    return this._link;
  }

  set link(value: string) {
    this._link = value;
  }


  updateView(newView: View): ViewText { // TODO: refactor view editor
    // if (this.id === newView.id) {
    //   const copy = copyObject(newView);
    //   ViewSelectionService.unselect(copy);
    //   return copy as ViewText;
    // }
    return null;
  }

  buildViewTree() { // TODO: refactor view editor
    // if (exists(baseFakeId)) this.replaceWithFakeIds();
    //
    // if (!viewsAdded.has(this.id)) { // View hasn't been added yet
    //   const copy = copyObject(this);
    //   if (this.parentId !== null) { // Has parent
    //     const parent = viewsAdded.get(this.parentId);
    //     parent.addChildViewToViewTree(copy);
    //
    //   } else viewTree.push(copy); // Is root
    //   viewsAdded.set(copy.id, copy);
    // }
  }

  addChildViewToViewTree(view: View) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  replaceWithFakeIds(base?: number) { // TODO: refactor view editor
    // const baseId = exists(base) ? base : baseFakeId;
    // this.id = View.calculateFakeId(baseId, this.id);
    // this.viewId = View.calculateFakeId(baseId, this.viewId);
    // this.parentId = View.calculateFakeId(baseId, this.parentId);
  }

  findParent(parentId: number): View { // TODO: refactor view editor
    // Doesn't have children, cannot be parent
    return null;
  }

  findView(viewId: number): View { // TODO: refactor view editor
    // if (this.viewId === viewId) return this;
    return null;
  }

  /**
   * Gets a default text view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewText { // TODO: refactor view editor
    return null;
    // return new ViewText(id, id, parentId, role, ViewMode.EDIT, "", null, null, null, null,
    //   View.VIEW_CLASS + ' ' + this.TEXT_CLASS + (!!cl ? ' ' + cl : ''));
  }


  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      text: this.text,
      link: this.link,
    });
  }

  static fromDatabase(obj: ViewTextDatabase): ViewText {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type text
    return new ViewText(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      obj.text,
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
}

export interface ViewTextDatabase extends ViewDatabase {
  text: string;
  link?: string;
}
