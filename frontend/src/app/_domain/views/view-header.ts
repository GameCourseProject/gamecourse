import {ViewImage, ViewImageDatabase} from "./view-image";
import {ViewText, ViewTextDatabase} from "./view-text";
import {View, ViewDatabase} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";

export class ViewHeader extends View{

  private _image: ViewImage;
  private _title: ViewText;

  constructor(image: ViewImage, title: ViewText) {

    super(null, null, null, ViewType.HEADER, null, null, null, null,
      null, null, null, null, null, null, null, null);

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
    return new ViewHeader(
      buildView(obj.image) as ViewImage,
      buildView(obj.title) as ViewText,
    );
  }
}

export interface ViewHeaderDatabase extends ViewDatabase {
  image: ViewImageDatabase;
  title: ViewTextDatabase;
}
