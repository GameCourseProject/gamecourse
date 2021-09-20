export class Page {
  private _name: string;

  constructor(name: string) {
    this._name = name;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  static fromDatabase(obj: PageDatabase): Page {
    return new Page(obj.name);
  }
}

interface PageDatabase {
  name: string
}
