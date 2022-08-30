export class SkillTree {
  private _id: number;
  private _courseID: number;
  private _name: string;
  private _maxReward: number;

  constructor(id: number, courseID: number, name: string, maxReward: number) {
    this._id = id;
    this._courseID = courseID;
    this._name = name;
    this._maxReward = maxReward;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get courseID(): number {
    return this._courseID;
  }

  set courseID(value: number) {
    this._courseID = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get maxReward(): number {
    return this._maxReward;
  }

  set maxReward(value: number) {
    this._maxReward = value;
  }


  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    return {
      id: this.id,
      course: this.courseID,
      name: this.name,
      maxReward: this.maxReward
    };
  }

  static fromDatabase(obj: SkillTreeDatabase): SkillTree {
    return new SkillTree(
      obj.id,
      obj.course,
      obj.name,
      obj.maxReward
    );
  }
}

interface SkillTreeDatabase {
  id: number,
  course: number,
  name: string,
  maxReward: number
}
