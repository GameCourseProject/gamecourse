import {View, ViewDatabase, ViewType, VisibilityType} from "./view";
import {Role} from "../roles/role";

export class ViewRow extends View {

  private _values: View[];

  constructor(id: number, viewId: number, parentId: number, type: ViewType, role: Role, values: View[], loopData?: any,
              variables?: any, style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, link?: any, info?: any) {

    super(id, viewId, parentId, type, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, link, info);

    this.values = values;
  }

  get values(): View[] {
    return this._values;
  }

  set values(value: View[]) {
    this._values = value;
  }

  static fromDatabase(obj: ViewRowDatabase): ViewRow {
    const parsedObj = View.parse(obj);
    return new ViewRow(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.type,
      parsedObj.role,
      obj.values.map(value => View.fromDatabase(value.value)),
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

export interface ViewRowDatabase extends ViewDatabase {
  values: {value: ViewDatabase}[]; // FIXME: should be only ViewDatabase
}
