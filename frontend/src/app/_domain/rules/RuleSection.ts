export class RuleSection{

  private _id: number;
  private _course: number;
  private _name: string;
  private _position: number;
  private _module: string;

  constructor(id: number, course: number, name: string, position: number, module: string) {
    this._id = id;
    this._course = course;
    this._name = name;
    this._position = position;
    this._module = module;
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

  get module (): string {
    return this._module;
  }

  set module(value: string) {
    this._module = value;
  }

  static fromDatabase(obj: RuleSectionDatabase): RuleSection {
    return new RuleSection(
      obj.id,
      obj.course,
      obj.name,
      obj.position,
      obj.module
    );
  }
}

interface RuleSectionDatabase {
  id: number,
  course: number,
  name: string,
  position: number,
  module: string
}
