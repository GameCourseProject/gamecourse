import {View, ViewDatabase, VisibilityType} from "./view";
import {ViewType} from "./view-type";

export class ViewImage extends View {

  private _src: string;
  private _link: string;

  constructor(id: number, viewId: number, parentId: number, role: string, src: string, loopData?: any, variables?: any,
              style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, info?: any, link?: any) {

    super(id, viewId, parentId, ViewType.IMAGE, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, info);

    this.src = src;
    if (link) this.link = link;
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

  static fromDatabase(obj: ViewImageDatabase): ViewImage {
    const parsedObj = View.parse(obj);
    return new ViewImage(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      obj.value,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events,
      parsedObj.info,
      obj.link || null
    );
  }
}

export interface ViewImageDatabase extends ViewDatabase {
  value: string;
  link?: string;
}
