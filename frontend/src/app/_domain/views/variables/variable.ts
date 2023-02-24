export class Variable {

  private _name: string;
  private _value: string;
  private _position: number;

  constructor(name: string, value: string, position: number) {
    this.name = name;
    this.value = value;
    this.position = position;
  }


  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get value(): string {
    return this._value;
  }

  set value(value: string) {
    this._value = value;
  }

  get position(): number {
    return this._position;
  }

  set position(value: number) {
    this._position = value;
  }


  static fromDatabase(obj: VariableDatabase): Variable {
    // TODO
    return null;
  }
}

export interface VariableDatabase {
  // TODO
}
