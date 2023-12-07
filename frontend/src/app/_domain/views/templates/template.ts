export class Template {
  private _id: number;
  private _name: string;
  private _viewRoot: number;

  constructor(id: number, name: string, viewRoot: number) {
    this.id = id;
    this.name = name;
    this.viewRoot = viewRoot;
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

  get viewRoot(): number {
    return this._viewRoot;
  }

  set viewRoot(value: number) {
    this._viewRoot = value;
  }

}

export interface TemplateDatabase {
  id: string;
  name: string;
  viewId: string;
}
