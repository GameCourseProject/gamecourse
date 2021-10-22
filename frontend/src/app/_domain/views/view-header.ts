import {ViewImage, ViewImageDatabase} from "./view-image";
import {ViewText, ViewTextDatabase} from "./view-text";
import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";

export class ViewHeader extends View{

  private _image: ViewImage;
  private _title: ViewText;

  static readonly HEADER_CLASS = 'header';
  static readonly IMAGE_CLASS = 'header_image';
  static readonly TITLE_CLASS = 'header_title';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, image: ViewImage, title: ViewText,
              loopData?: any, variables?: any, style?: string, cssId?: string, cl?: string, label?: string,
              visibilityType?: VisibilityType, visibilityCondition?: any, events?: any) {

    super(id, viewId, parentId, ViewType.HEADER, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

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

    const image = buildView(obj.image) as ViewImage;
    image.class += ' ' + this.IMAGE_CLASS;
    const title = buildView(obj.title) as ViewText;
    title.class += ' ' + this.TITLE_CLASS;

    return new ViewHeader(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      image,
      title,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.HEADER_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events
    );
  }
}

export interface ViewHeaderDatabase extends ViewDatabase {
  image: ViewImageDatabase;
  title: ViewTextDatabase;
}
