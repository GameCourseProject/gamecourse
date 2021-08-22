export class Course {
  private _id: number;
  private _name: string;

  constructor(source: Partial<Course>) {
    Object.keys(source).forEach(key => {
      this[key] = source[key];
    });
    return this;
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
}
