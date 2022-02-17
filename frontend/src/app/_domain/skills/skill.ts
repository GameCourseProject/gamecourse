export class Skill {
  private _id: number;
  private _name: string;
  private _color: string;
  private _description: string;
  private _tier: string;
  private _seqId: number;
  private _xp: number;
  private _isActive: boolean;
  private _dependencies: string;
  private _allActive: boolean;
  private _dependenciesList: string[][];

  constructor(id: number, name: string, color: string, description: string, tier: string, seqId: number, xp: number,
              isActive: boolean, dependencies: string, allActive: boolean, dependenciesList: string[][]) {

    this.id = id;
    this.name = name;
    this.color = color;
    this.description = description;
    this.tier = tier;
    this.seqId = seqId;
    this.xp = xp;
    this.isActive = isActive;
    this.dependencies = dependencies;
    this.allActive = allActive;
    this.dependenciesList = dependenciesList;
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

  get color(): string {
    return this._color;
  }

  set color(value: string) {
    this._color = value;
  }

  get description(): string {
    return this._description;
  }

  set description(value: string) {
    this._description = value;
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

  get xp(): number {
    return this._xp;
  }

  set xp(value: number) {
    this._xp = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get dependencies(): string {
    return this._dependencies;
  }

  set dependencies(value: string) {
    this._dependencies = value;
  }

  get allActive(): boolean {
    return this._allActive;
  }

  set allActive(value: boolean) {
    this._allActive = value;
  }

  get dependenciesList(): string[][] {
    return this._dependenciesList;
  }

  set dependenciesList(value: string[][]) {
    this._dependenciesList = value;
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
      color: this.color,
      description: this.description,
      tier: this.tier,
      seqId: this.seqId,
      xp: this.xp,
      isActive: this.isActive,
      dependencies: this.dependencies,
      allActive: this.allActive,
      dependenciesList: this.dependenciesList,
    };
  }

  static fromDatabase(obj: SkillDatabase): Skill {
    return new Skill(
      parseInt(obj.id),
      obj.name,
      obj.color,
      obj.description,
      obj.tier,
      parseInt(obj.seqId),
      parseInt(obj.xp),
      !!obj.isActive,
      obj.dependencies,
      !!obj.allActive,
      obj.dependenciesList
    );
  }
}

interface SkillDatabase {
  id: string,
  name: string,
  color: string,
  description: string,
  tier: string,
  seqId: string,
  xp: string,
  isActive: boolean,
  dependencies: string,
  allActive: boolean,
  dependenciesList: string[][],
}
