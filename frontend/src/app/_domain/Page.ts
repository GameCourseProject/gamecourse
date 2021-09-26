export class Page {
  private _id: number;
  private _name: string;
  private _courseId: number;
  private _viewId: number;
  private _sedId: number;
  private _theme: string;
  private _isEnabled: boolean;

  constructor(id: number, name: string, courseId: number, viewId: number, seqId: number, theme: string, isEnabled: boolean) {
    this._id = id;
    this._name = name;
    this._courseId = courseId;
    this._viewId = viewId;
    this._sedId = seqId;
    this._theme = theme;
    this._isEnabled = isEnabled;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get courseId(): number {
    return this._courseId;
  }

  set courseId(value: number) {
    this._courseId = value;
  }

  get viewId(): number {
    return this._viewId;
  }

  set viewId(value: number) {
    this._viewId = value;
  }

  get sedId(): number {
    return this._sedId;
  }

  set sedId(value: number) {
    this._sedId = value;
  }

  get theme(): string {
    return this._theme;
  }

  set theme(value: string) {
    this._theme = value;
  }

  get isEnabled(): boolean {
    return this._isEnabled;
  }

  set isEnabled(value: boolean) {
    this._isEnabled = value;
  }

  static fromDatabase(obj: PageDatabase): Page {
    return new Page(
      parseInt(obj.id) || null,
      obj.name,
      parseInt(obj.course) || null,
      parseInt(obj.viewId) || null,
      parseInt(obj.seqId) || null,
      obj.theme,
      !!parseInt(obj.isEnabled)
    );
  }
}

interface PageDatabase {
  id?: string;
  name: string;
  course?: string;
  viewId?: string;
  seqId?: string;
  theme?: string;
  isEnabled?: string;
}
