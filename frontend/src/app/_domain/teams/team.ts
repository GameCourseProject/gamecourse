export class Team {
  private _id: number;
  private _teamName: string;
  private _teamNumber: number;
  private _members: string;
  private _xp: number;


  constructor(id: number, teamName: string, teamNumber: number,  members: string, xp: number) {

    this.id = id;
    this.teamName = teamName;
    this.teamNumber = teamNumber;
    this.members = members;
    this.xp = xp;

  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get teamName(): string {
    return this._teamName;
  }

  set teamName(value: string) {
    this._teamName = value;
  }

  get teamNumber(): number {
    return this._teamNumber;
  }

  set teamNumber(value: number) {
    this._teamNumber = value;
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
      teamName: this.teamName,
      teamNumber: this.teamNumber,
      members: this.members,
      xp: this.xp,
    };
  }

  static fromDatabase(obj: TeamDatabase): Team {
    return new Team(
      parseInt(obj.id),
      obj.teamName,
      parseInt(obj.teamNumber),
      obj.members,
      parseInt(obj.xp)
    );
  }
}

interface TeamDatabase {
  id: string,
  teamName: string,
  teamNumber: string,
  members?: string,
  xp?: string
}
