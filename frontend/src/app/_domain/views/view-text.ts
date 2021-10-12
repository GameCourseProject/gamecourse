import {View, ViewDatabase, VisibilityType} from "./view";
import {ViewType} from "./view-type";

export class ViewText extends View {

  private _value: string;
  private _link?: string;

  constructor(id: number, viewId: number, parentId: number, role: string, value: string, loopData?: any, variables?: any,
              style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, info?: any, link?: any) {

    super(id, viewId, parentId, ViewType.TEXT, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, info);

    this.value = value;
    if (link) this.link = link;
  }

  get value(): string {
    return this._value;
  }

  set value(value: string) {
    this._value = value;
  }

  get link(): string {
    return this._link;
  }

  set link(value: string) {
    this._link = value;
  }

  static fromDatabase(obj: ViewTextDatabase): ViewText {
    const parsedObj = View.parse(obj);
    return new ViewText(
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
      obj.link
    );
  }
}

export interface ViewTextDatabase extends ViewDatabase {
  value: string;
  link?: string;
}
