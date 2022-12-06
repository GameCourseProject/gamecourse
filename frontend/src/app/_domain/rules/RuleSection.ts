import { Rule } from "./rule";

export class RuleSection{

  private _id: number;
  private _name: string;

  constructor(id: number, name: string) {
    this._id = id;
    this._name = name;
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

  static fromDatabase(obj: RuleSectionDatabase): RuleSection {
    return new RuleSection(
      obj.id,
      obj.name,
    );
  }
}

interface RuleSectionDatabase {
  id: number,
  name: string,
}
