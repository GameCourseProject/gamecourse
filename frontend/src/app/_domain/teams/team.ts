export class Team {
  private _id: number;
  private _name: string;
  private _number: number;
  private _members: string;
  private _xp: number;


  constructor(id: number, name: string, number: number,  members: string, xp: number) {

    this.id = id;
    this.name = name;
    this.number = number;
    this.members = members;
    this.xp = xp;

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

  get number(): number {
    return this._number;
  }

  set number(value: number) {
    this._number = value;
  }

  get members(): string {
    return this._members;
  }

  set members(value: string) {
    this._members = value;
  }


  get xp(): number {
    return this._xp;
  }

  set xp(value: number) {
    this._xp = value;
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
      number: this.number,
      members: this.members,
      xp: this.xp,
    };
  }

  static fromDatabase(obj: TeamDatabase): Team {
    return new Team(
      parseInt(obj.id),
      obj.name,
      parseInt(obj.number),
      obj.members,
      parseInt(obj.xp)
    );
  }
}

interface TeamDatabase {
  id: string,
  name: string,
  number: string,
  members?: string,
  xp?: string
}
