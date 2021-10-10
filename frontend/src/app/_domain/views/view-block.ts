import {View, ViewDatabase, ViewType, VisibilityType} from "./view";
import {ViewImage, ViewImageDatabase} from "./view-image";
import {ViewText, ViewTextDatabase} from "./view-text";
import {Role} from "../roles/role";

export class ViewBlock extends View {

  private _children: View[];
  private _header?: { image: ViewImage, title: ViewText } // FIXME: should be a view on its own

  constructor(id: number, viewId: number, parentId: number, role: Role, children: View[], loopData?: any,
              variables?: any, style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, link?: any, info?: any, header?: {image: ViewImage, title: ViewText}) {

    super(id, viewId, parentId, ViewType.BLOCK, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, link, info);

    this.children = children;
    if (header) {
      this.header.image = header.image;
      this.header.title = header.title;
    }
  }

  get children(): View[] {
    return this._children;
  }

  set children(value: View[]) {
    this._children = value;
  }

  get header(): { image: ViewImage; title: ViewText } {
    return this._header;
  }

  set header(value: { image: ViewImage; title: ViewText }) {
    this._header = value;
  }

  static fromDatabase(obj: ViewBlockDatabase): ViewBlock {
    const parsedObj = View.parse(obj);
    return new ViewBlock(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      obj.children.map(child => View.fromDatabase(child)),
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
      parsedObj.info,
      { image: ViewImage.fromDatabase(obj.header.image), title: ViewText.fromDatabase(obj.header.title)}
    );
  }
}

export interface ViewBlockDatabase extends ViewDatabase {
  children: ViewDatabase[];
  header?: { image: ViewImageDatabase, title: ViewTextDatabase }
}
