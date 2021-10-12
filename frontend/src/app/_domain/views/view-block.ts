import {View, ViewDatabase, VisibilityType, } from "./view";
import {ViewHeader, ViewHeaderDatabase} from "./view-header";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";

export class ViewBlock extends View {

  private _children: View[];
  private _header?: ViewHeader; // FIXME: should be a children as well

  constructor(id: number, viewId: number, parentId: number, role: string, children: View[], loopData?: any,
              variables?: any, style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, info?: any, header?: ViewHeader) {

    super(id, viewId, parentId, ViewType.BLOCK, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, info);

    this.children = children;
    if (header) this.header = header;
  }

  get children(): View[] {
    return this._children;
  }

  set children(value: View[]) {
    this._children = value;
  }

  get header(): ViewHeader {
    return this._header;
  }

  set header(value: ViewHeader) {
    this._header = value;
  }

  static fromDatabase(obj: ViewBlockDatabase): ViewBlock {
    const parsedObj = View.parse(obj);
    return new ViewBlock(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      obj.children.map(child => buildView(child)),
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
      obj.header ? buildView(obj.header) as ViewHeader : null
    );
  }
}

export interface ViewBlockDatabase extends ViewDatabase {
  children: ViewDatabase[];
  header?: ViewHeaderDatabase
}
