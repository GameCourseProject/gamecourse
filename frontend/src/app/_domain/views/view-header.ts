import {ViewImage, ViewImageDatabase} from "./view-image";
import {ViewText, ViewTextDatabase} from "./view-text";
import {View, ViewDatabase, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";

export class ViewHeader extends View{

  private _image: ViewImage;
  private _title: ViewText;

  constructor(id: number, viewId: number, parentId: number, role: string, image: ViewImage, title: ViewText, loopData?: any,
              variables?: any, style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, info?: any) {

    super(id, viewId, parentId, ViewType.HEADER, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, info);

    this.image = image;
    this.title = title;
  }

  get image(): ViewImage {
    return this._image;
  }

  set image(value: ViewImage) {
    this._image = value;
  }

  get title(): ViewText {
    return this._title;
  }

  set title(value: ViewText) {
    this._title = value;
  }

  static fromDatabase(obj: ViewHeaderDatabase): ViewHeader {
    const parsedObj = View.parse(obj);
    return new ViewHeader(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      buildView(obj.image) as ViewImage,
      buildView(obj.title) as ViewText,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events,
      parsedObj.info
    );
  }
}

export interface ViewHeaderDatabase extends ViewDatabase {
  image: ViewImageDatabase;
  title: ViewTextDatabase;
}
