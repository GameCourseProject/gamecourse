import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {copyObject} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";

export class ViewImage extends View {

  private _src: string;
  private _link: string;

  static readonly IMAGE_CLASS = 'image';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, src: string, loopData?: any,
              variables?: any, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, link?: any) {

    super(id, viewId, parentId, ViewType.IMAGE, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

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

  updateView(newView: View): ViewImage {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      ViewSelectionService.unselect(copy);
      return copy as ViewImage;
    }
    return null;
  }

  static fromDatabase(obj: ViewImageDatabase): ViewImage {
    const parsedObj = View.parse(obj);
    return new ViewImage(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      obj.src,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.IMAGE_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events,
      obj.link || null
    );
  }
}

export interface ViewImageDatabase extends ViewDatabase {
  src: string;
  link?: string;
}
