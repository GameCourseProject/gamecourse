export class RuleSection{

  private _id: number;
  private _course: number;
  private _name: string;
  private _position: number;
  // private _module: ??

  constructor(id: number, course: number, name: string, position: number) {
    this._id = id;
    this._course = course;
    this._name = name;
    this._position = position;
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

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get position(): number {
    return this._position;
  }

  set position(value: number) {
    this._position = value;
  }

  static fromDatabase(obj: RuleSectionDatabase): RuleSection {
    return new RuleSection(
      obj.id,
      obj.course,
      obj.name,
      obj.position
    );
  }
}

interface RuleSectionDatabase {
  id: number,
  course: number,
  name: string,
  position: number
}
