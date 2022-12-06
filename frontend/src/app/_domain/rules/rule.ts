import { ApiHttpService } from "../../_services/api/api-http.service";
import { RuleSection } from "./RuleSection";

export class Rule {
  private _id: number;
  private _name: string;
  private _section: RuleSection;

  constructor(id: number, name: string, section: RuleSection) {
    this._id = id;
    this._name = name;
    this._section = section;
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

  get section(): RuleSection{
    return this._section;
  }

  set section(value: RuleSection){
    this._section = value;
  }

  static fromDatabase(obj: RuleDatabase): Rule {
    return new Rule(
      obj.id,
      obj.name,
      obj.section,
    );
  }
}

interface RuleDatabase {
  id: number,
  name: string,
  section: RuleSection
}
