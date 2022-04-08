export class ActionsToRemove {
  private _id: number;
  private _name: string;
  private _description: string;
  private _type: string;
  private _tokens: number;
  private _isActive: boolean;


  constructor(id: number, _name: string, _description: string, _type: string, _tokens: number, _isActive: boolean) {
    this._id = id;
    this._name = _name;
    this._description = _description;
    this._type = _type;
    this._tokens = _tokens;
    this._isActive = _isActive;
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

  get description(): string {
    return this._description;
  }

  set description(value: string) {
    this._description = value;
  }

  get type(): string {
    return this._type;
  }

  set type(value: string) {
    this._type = value;
  }

  get tokens(): number {
    return this._tokens;
  }

  set tokens(value: number) {
    this._tokens = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    return {
      id: this.id,
      name: this.name,
      description: this.description,
      type: this.type,
      tokens: this.tokens,
      isActive: this.isActive,
    };
  }

  static fromDatabase(obj: ActionsToRemoveDatabase): ActionsToRemove {
    return new ActionsToRemove(
      parseInt(obj.id),
      obj.name,
      obj.description,
      obj.type,
      parseInt(obj.tokens),
      !!obj.isActive,
      );
  }
}

interface ActionsToRemoveDatabase {
  id: string,
  name: string,
  description: string,
  type: string,
  tokens: string,
  isActive: boolean
}
