import {Rule, RuleDatabase} from "./rule";

export class RuleTag{
  private _id: number;
  private _course: number;
  private _name: string;
  private _color: string;
  private _rules: Rule[];

  constructor(id: number, course: number, name: string, color: string, rules: Rule[]){
    this._id = id;
    this._course = course;
    this._name = name;
    this._color = color;
    this._rules = rules;
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

  get color(): string {
    return this._color;
  }

  set color(value: string) {
    this._color = value;
  }

  get rules(){
    return this._rules;
  }

  set rules(value: Rule[]){
    this._rules = value;
  }

  /**
   * Custom way to stringfy with class
   * This is needed so that the output of JSON.stringfy()
   * doesn't have '_' attributes
   * */
  static toJason (tag: RuleTag) {
    return {
      id: tag.id > 0 ? tag.id : null, //does't send fake ids used only for editing
      course: tag.course > 0 ? tag.course : null,
      name: tag.name || null,
      color: tag.color || null
    }
  }



  static fromDatabase(obj: RuleTagDatabase): RuleTag {
    return new RuleTag(
      obj.id,
      obj.course,
      obj.name,
      obj.color,
      obj.rules.map(rule => Rule.fromDatabase(rule))
    );
  }
}

export interface RuleTagDatabase {
  id: number,
  course: number,
  name: string,
  color: string,
  rules?: RuleDatabase[]
}
