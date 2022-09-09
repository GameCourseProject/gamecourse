import {User, UserDatabase} from "../users/user";
import {Role} from "../roles/role";

export class Team {
  private _id: number;
  private _teamName: string;
  private _teamNumber: number;
  private _members: string;
  private _teamMembers: User[];
  private _xp: number;
  private _level: number;


  constructor(id: number, teamName: string, teamNumber: number,  members: string, teamMembers: User[], xp: number, level: number) {

    this.id = id;
    this.teamName = teamName;
    this.teamNumber = teamNumber;
    this.members = members;
    this.teamMembers = teamMembers;
    this.xp = xp;
    this.level = level;

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

  get teamMembers(): User[] {
    return this._teamMembers;
  }

  set teamMembers(value: User[]) {
    this._teamMembers = value;
  }


  get xp(): number {
    return this._xp;
  }

  set xp(value: number) {
    this._xp = value;
  }

  get level(): number {
    return this._level;
  }

  set level(value: number) {
    this._level = value;
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
      teamMembers: this.teamMembers,
      xp: this.xp,
      level: this.level,
    };
  }

  static fromDatabase(obj: TeamDatabase): Team {
    return new Team(
      parseInt(obj.id),
      obj.teamName,
      parseInt(obj.teamNumber),
      obj.members,
      obj.teamMembers ? obj.teamMembers.map(member => User.fromDatabase(member)) : null,
      parseInt(obj.xp),
      parseInt(obj.level)
    );
  }
}

interface TeamDatabase {
  id: string,
  teamName: string,
  teamNumber: string,
  members?: string,
  teamMembers: UserDatabase[],
  xp?: string
  level?: string
}
