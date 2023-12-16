import { Moment } from "moment";
import { dateFromDatabase } from "src/app/_utils/misc/misc";

export class Template {
  private _id: number;
  private _name: string;
  private _viewRoot: number;
  private _isSystem: boolean;
  private _course?: number;
  private _creationTimestamp?: Moment;
  private _updateTimestamp?: Moment;
  private _isPublic?: boolean;
  private _sharedTimestamp?: Moment;

  constructor(id: number, name: string, viewRoot: number, creationTimestamp: Moment, updateTimestamp: Moment, course: number = null, isPublic: boolean = false, sharedTimestamp: Moment = null) {
    this.id = id;
    this.name = name;
    this.viewRoot = viewRoot;
    this.creationTimestamp = creationTimestamp;
    this.updateTimestamp = updateTimestamp;
    this.isPublic = isPublic;
    this.sharedTimestamp = sharedTimestamp;
    this.course = course;

    if (this.creationTimestamp) {
      this.isSystem = false;
    } else {
      this.isSystem = true;
    }
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

  get course(): number {
    return this._course;
  }

  set course(value: number) {
    this._course = value;
  }


  get creationTimestamp(): Moment {
    return this._creationTimestamp;
  }

  set creationTimestamp(value: Moment) {
    this._creationTimestamp = value;
  }

  get updateTimestamp(): Moment {
    return this._updateTimestamp;
  }

  set updateTimestamp(value: Moment) {
    this._updateTimestamp = value;
  }

  get isPublic(): boolean {
    return this._isPublic;
  }

  set isPublic(value: boolean) {
    this._isPublic = value;
  }

  get isSystem(): boolean {
    return this._isSystem;
  }

  set isSystem(value: boolean) {
    this._isSystem = value;
  }
  
  get sharedTimestamp(): Moment {
    return this._sharedTimestamp;
  }

  set sharedTimestamp(value: Moment) {
    this._sharedTimestamp = value;
  }

  static fromDatabase(obj: TemplateDatabase): Template {
    return new Template(
      obj.id,
      obj.name,
      obj.view,
      obj.creationTimestamp ? dateFromDatabase(obj.creationTimestamp) : null,
      obj.updateTimestamp ? dateFromDatabase(obj.updateTimestamp) : null,
      obj.course ? obj.course : null,
      obj.isPublic ? obj.isPublic : null,
      obj.sharedTimestamp ? dateFromDatabase(obj.sharedTimestamp) : null
    );
  }

}

export interface TemplateDatabase {
  id: number;
  name: string;
  course?: number;
  view: number;
  creationTimestamp?: string;
  updateTimestamp?: string;
  isPublic?: boolean;
  sharedTimestamp?: string;
}
