export class Tier {
  private _id: number;
  private _skillTreeID: number;
  private _name: string;
  private _reward: number;
  private _costType: 'fixed' | 'incremental' | 'exponential';
  private _cost: number;
  private _increment: number;
  private _minRating: number;
  private _position: number;
  private _isActive: boolean;

  static readonly WILDCARD: string = "Wildcard";

  constructor(id: number, skillTreeID: number, name: string, reward: number, costType: 'fixed' | 'incremental' | 'exponential', cost: number,
              increment: number, minRating: number, position: number, isActive: boolean) {
    this._id = id;
    this._skillTreeID = skillTreeID;
    this._name = name;
    this._reward = reward;
    this._costType = costType;
    this._cost = cost;
    this._increment = increment;
    this._minRating = minRating;
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

  get costType(): "fixed" | "incremental" | "exponential" {
    return this._costType;
  }

  set costType(value: "fixed" | "incremental" | "exponential") {
    this._costType = value;
  }

  get cost(): number {
    return this._cost;
  }

  set cost(value: number) {
    this._cost = value;
  }

  get increment(): number {
    return this._increment;
  }

  set increment(value: number) {
    this._increment = value;
  }

  get minRating(): number {
    return this._minRating;
  }

  set minRating(value: number) {
    this._minRating = value;
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
      costType: this.costType,
      cost: this.cost,
      increment: this.increment,
      minRating: this.minRating,
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
      obj.costType,
      obj.cost,
      obj.increment,
      obj.minRating,
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
  costType: 'fixed' | 'incremental' | 'exponential',
  cost: number,
  increment: number,
  minRating: number,
  position: number,
  isActive: boolean
}
