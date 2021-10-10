import {View, ViewDatabase, ViewType, VisibilityType} from "./view";
import {Role} from "../roles/role";

export class ViewImage extends View {

  private _src: string;

  constructor(id: number, viewId: number, parentId: number, role: Role, src: string, loopData?: any, variables?: any,
              style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, link?: any, info?: any) {

    super(id, viewId, parentId, ViewType.IMAGE, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, link, info);

    this.src = src;
  }

  get src(): string {
    return this._src;
  }

  set src(value: string) {
    this._src = value;
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
      parsedObj.link,
      parsedObj.info
    );
  }
}

export interface ViewImageDatabase extends ViewDatabase {
  value: string;
}
