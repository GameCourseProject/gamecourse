export class Tier {
  private _id: number;
  private _tier: string;
  private _seqId: number;
  private _reward: number;
  private _treeId: number;

  constructor(id: number, tier: string, seqId: number, reward: number, treeId: number) {
    this._id = id;
    this._tier = tier;
    this._seqId = seqId;
    this._reward = reward;
    this._treeId = treeId;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get tier(): string {
    return this._tier;
  }

  set tier(value: string) {
    this._tier = value;
  }

  get seqId(): number {
    return this._seqId;
  }

  set seqId(value: number) {
    this._seqId = value;
  }

  get reward(): number {
    return this._reward;
  }

  set reward(value: number) {
    this._reward = value;
  }

  get treeId(): number {
    return this._treeId;
  }

  set treeId(value: number) {
    this._treeId = value;
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    return {
      id: this.id,
      tier: this.tier,
      seqId: this.seqId,
      reward: this.reward,
      treeId: this.treeId,
    };
  }

  static fromDatabase(obj: TierDatabase): Tier {
    return new Tier(
      parseInt(obj.id),
      obj.tier,
      parseInt(obj.seqId),
      parseInt(obj.reward),
      parseInt(obj.treeId)
    );
  }
}

interface TierDatabase {
  id: string,
  tier: string,
  seqId: string,
  reward: string,
  treeId: string
}
