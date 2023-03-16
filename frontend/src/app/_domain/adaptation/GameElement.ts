export class GameElement {
  private _id: number;
  private _course: number;
  private _module: string;
  private _isActive: boolean;
  private _notify: boolean;
  private _description: string;

  constructor(id: number, course: number, module: string, isActive: boolean, notify: boolean, description: string) {
    this._id = id;
    this._course = course;
    this._module = module;
    this._isActive = isActive;
    this._notify = notify;
    this._description = description;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get course(): number {
    return this._course;
  }

  set course(value: number) {
    this._course = value;
  }

  get module(): string {
    return this._module;
  }

  set module(value: string) {
    this._module = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get notify(): boolean {
    return this._notify;
  }

  set notify(value: boolean) {
    this._notify = value;
  }

  get description(): string {
    return this._description;
  }

  set description(value: string){
    this._description = value;
  }

  static fromDatabase(obj: GameElementDatabase): GameElement {
    return new GameElement(
      obj.id,
      obj.course,
      obj.module,
      obj.isActive,
      obj.notify,
      obj.description
    );
  }
}

interface GameElementDatabase {
  id: number,
  course: number,
  module: string,
  isActive: boolean,
  notify: boolean,
  description: string
}
