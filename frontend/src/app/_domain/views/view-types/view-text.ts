import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import { viewTree, viewsAdded } from "../build-view-tree/build-view-tree";

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

  buildViewTree() {
    const viewForDatabase = ViewText.toDatabase(this);

    if (!viewsAdded.has(this.id)) {
      if (this.parent) {
        const parent = viewsAdded.get(this.parent.id);

        if (this.oldId) {
          const arrayToPut = (parent as any).children.find((e) => e.find((view) => view.id === this.oldId));
          if (arrayToPut) {
            arrayToPut.push(viewForDatabase);
          }
          else {
            (parent as any).children.push([viewForDatabase]);
          }
        }
        else {
          (parent as any).children.push([viewForDatabase]);
        }

      }
      else viewTree.push(viewForDatabase); // Is root
      
      viewsAdded.set(this.id, viewForDatabase);
    }
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

  findView(viewId: number): View {
    if (this.id === viewId) return this;
    else return null;
  }

  switchMode(mode: ViewMode) {
    this.mode = mode;
  }

  /**
   * Gets a default text view.
   */
  static getDefault(id: number, parentId: number = null, parent: View, aspect: Aspect, text: string = ""): ViewText { // TODO: refactor view editor
    return new ViewText(ViewMode.EDIT, id, parentId, parent, aspect, text, null, null, null, null, VisibilityType.VISIBLE, null, null, [], []);
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
      obj.text?.toString(),
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
  
  static toDatabase(obj: ViewText): ViewTextDatabase {
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
      text: obj.text,
      link: obj._link
    }
  }
}

export interface ViewTextDatabase extends ViewDatabase {
  text: number | string;
  link?: string;
}
