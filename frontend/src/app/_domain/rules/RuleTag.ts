export class RuleTag{
  private _id: number;
  private _name: string;
  private _color: string;


  constructor(id: number, name: string, color: string){
    this._id = id;
    this._name = name;
    this._color = color;
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

  get color(): string {
    return this._color;
  }

  set color(value: string) {
    this._color = value;
  }

  static fromDatabase(obj: RuleTagDatabase): RuleTag {
    return new RuleTag(
      obj.id,
      obj.name,
      obj.color
    );
  }
}

interface RuleTagDatabase {
  id: number,
  name: string,
  color: string,
}
