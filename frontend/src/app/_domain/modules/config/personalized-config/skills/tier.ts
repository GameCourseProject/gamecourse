import {environment} from "../../../../../../environments/environment";

export class Tier {
  private _id: number;
  private _skillTreeID: number;
  private _name: string;
  private _reward: number;
  private _position: number;
  private _isActive: boolean;

  static readonly WILDCARD: string = "Wildcard";

  constructor(id: number, skillTreeID: number, name: string, reward: number, position: number, isActive: boolean) {
    this._id = id;
    this._skillTreeID = skillTreeID;
    this._name = name;
    this._reward = reward;
    this._position = position;
    this._isActive = isActive;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get skillTreeID(): number {
    return this._skillTreeID;
  }

  set skillTreeID(value: number) {
    this._skillTreeID = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get reward(): number {
    return this._reward;
  }

  set reward(value: number) {
    this._reward = value;
  }

  get position(): number {
    return this._position;
  }

  set position(value: number) {
    this._position = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  isWildcard(): boolean {
    return this.name === Tier.WILDCARD;
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    return {
      id: this.id,
      skillTree: this.skillTreeID,
      name: this.name,
      reward: this.reward,
      position: this.position,
      isActive: this.isActive,
    };
  }

  static fromDatabase(obj: TierDatabase): Tier {
    return new Tier(
      obj.id,
      obj.skillTree,
      obj.name,
      obj.reward,
      obj.position,
      obj.isActive
    );
  }
}

interface TierDatabase {
  id: number,
  skillTree: number,
  name: string,
  reward: number,
  position: number,
  isActive: boolean
}
