import {RuleTag} from "./RuleTag";

export class Rule {
  private _id: number;
  private _course: number;
  private _section: number;
  private _name: string;
  private _description: string;
  private _whenClause: string;
  private _thenClause: string;
  private _position: number;
  private _isActive: boolean;
  private _tags: RuleTag[];

  constructor(id: number, course: number, section: number, name: string, description: string, whenClause: string, thenClause: string,
              position: number, isActive: boolean, tags: RuleTag[]) {
    this._id = id;
    this._course = course;
    this._section = section;
    this._name = name;
    this._description = description;
    this._whenClause = whenClause;
    this._thenClause = thenClause;
    this._position = position;
    this._isActive = isActive;
    this._tags = tags;
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

  get section(): number {
    return this._section;
  }

  set section(value: number) {
    this._section = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get description(): string {
    return this._description;
  }

  set description(value: string) {
    this._description = value;
  }

  get whenClause(): string {
    return this._whenClause;
  }

  set whenClause(value: string) {
    this._whenClause = value;
  }

  get thenClause(): string {
    return this._thenClause;
  }

  set thenClause(value: string) {
    this._thenClause = value;
  }

  get position(): number {
    return this._position;
  }

  set position(value: number) {
    this._position = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get tags(): RuleTag[] {
    return this._tags;
  }

  set tags(value: RuleTag[]) {
    this._tags = value;
  }

  static fromDatabase(obj: RuleDatabase): Rule {
    return new Rule(
      obj.id,
      obj.course,
      obj.section,
      obj.name,
      obj.description,
      obj.whenClause,
      obj.thenClause,
      obj.position,
      obj.isActive ?? null,
      obj.tags
    );
  }
}

export interface RuleDatabase {
  id: number,
  course: number,
  section: number,
  name: string,
  description: string,
  whenClause: string,
  thenClause: string,
  position: number,
  isActive: boolean,
  tags: RuleTag[]
}
