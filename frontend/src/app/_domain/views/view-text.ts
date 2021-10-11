import {View, ViewDatabase, VisibilityType} from "./view";
import {ViewType} from "./view-type";

export class ViewText extends View {

  private _value: string;

  constructor(id: number, viewId: number, parentId: number, role: string, value: string, loopData?: any, variables?: any,
              style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, link?: any, info?: any) {

    super(id, viewId, parentId, ViewType.TEXT, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, link, info);

    this.value = value;
  }

  get value(): string {
    return this._value;
  }

  set value(value: string) {
    this._value = value;
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
      parsedObj.link,
      parsedObj.info
    );
  }
}

export interface ViewTextDatabase extends ViewDatabase {
  value: string;
}
