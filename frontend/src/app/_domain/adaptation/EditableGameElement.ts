export class EditableGameElement {
  private _id: number;
  private _course: number;
  private _module: string;
  private _isEditable: boolean;
  private _nDays: number;
  private _notify: boolean;
  private _usersMode: string;
  private _users: string[];

  constructor(id: number, course: number, module: string, isEditable: boolean, nDays: number, notify: boolean, usersMode: string, users?: string[]) {
    this._id = id;
    this._course = course;
    this._module = module;
    this._isEditable = isEditable;
    this._nDays = nDays;
    this._notify = notify;
    this._usersMode = usersMode.toString();
    if (users) this._users = users;
    else this._users = [];
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

  get isEditable(): boolean {
    return this._isEditable;
  }

  set isEditable(value: boolean) {
    this._isEditable = value;
  }

  get nDays(): number {
    return this._nDays;
  }

  set nDays(value: number) {
    this._nDays = value;
  }

  get notify(): boolean {
    return this._notify;
  }

  set notify(value: boolean) {
    this._notify = value;
  }

  get usersMode(): string {
    return this._usersMode;
  }

  set usersMode(value: string) {
    this._usersMode = value;
  }

  get users(): string[] {
    return this._users;
  }

  set users(value: string[]){
    this._users = value;
  }

  static fromDatabase(obj: EditableGameElementDB): EditableGameElement {
    return new EditableGameElement(
      obj.id,
      obj.course,
      obj.module,
      obj.isEditable,
      obj.nDays,
      obj.notify,
      obj.usersMode,
      obj.users
    );
  }
}

interface EditableGameElementDB {
  id: number,
  course: number,
  module: string,
  isEditable: boolean,
  nDays: number,
  notify: boolean,
  usersMode: string,
  users: string[]
}
