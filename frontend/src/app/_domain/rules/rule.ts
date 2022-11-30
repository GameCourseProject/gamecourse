import { ApiHttpService } from "../../_services/api/api-http.service";

export class Rule {
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


  static fromDatabase(obj: RuleDatabase): Rule {
    return new Rule(
      obj.id,
      obj.name,
    );
  }
}

interface RuleDatabase {
  id: number,
  name: string,
}
