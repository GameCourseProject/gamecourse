import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";

export class ViewRow extends View {

  private _values: View[];

  static readonly ROW_CLASS = 'row';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, values: View[], loopData?: any,
              variables?: any, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any) {

    super(id, viewId, parentId, ViewType.ROW, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

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
      parsedObj.role,
      parsedObj.mode,
      obj.values.map(view => buildView(view.value)),
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events
    );
  }
}

export interface ViewRowDatabase extends ViewDatabase {
  values: {value: ViewDatabase}[];
}
