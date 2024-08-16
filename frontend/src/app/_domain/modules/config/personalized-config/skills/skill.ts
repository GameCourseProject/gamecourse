import {Tier} from "./tier";

export class Skill {
  private _id: number;
  private _tierID: number;
  private _name: string;
  private _color?: string;
  private _page?: string;
  private _isCollab: boolean;
  private _isExtra: boolean;
  private _isActive: boolean;
  private _position: number;
  private _ruleID: number;
  private _dependencies: Skill[][];

  constructor(id: number, tierID: number, name: string, isCollab: boolean, isExtra: boolean, isActive: boolean, position: number,
              ruleID: number, dependencies: Skill[][], color?: string, page?: string) {

    this._id = id;
    this._tierID = tierID;
    this._name = name;
    this._isCollab = isCollab;
    this._isExtra = isExtra;
    this._isActive = isActive;
    this._position = position;
    this._ruleID = ruleID;
    this._dependencies = dependencies;
    if (color) this._color = color;
    if (page) this._page = page;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get tierID(): number {
    return this._tierID;
  }

  set tierID(value: number) {
    this._tierID = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get color(): string | null {
    return this._color;
  }

  set color(value: string | null) {
    this._color = value;
  }

  get page(): string | null {
    return this._page;
  }

  set page(value: string | null) {
    this._page = value;
  }

  get isCollab(): boolean {
    return this._isCollab;
  }

  set isCollab(value: boolean) {
    this._isCollab = value;
  }

  get isExtra(): boolean {
    return this._isExtra;
  }

  set isExtra(value: boolean) {
    this._isExtra = value;
  }

  get isActive(): boolean {
    return this._isActive;
  }

  set isActive(value: boolean) {
    this._isActive = value;
  }

  get position(): number {
    return this._position;
  }

  set position(value: number) {
    this._position = value;
  }

  get ruleID(): number {
    return this._ruleID;
  }

  set ruleID(value: number) {
    this._ruleID = value;
  }

  get dependencies(): Skill[][] {
    return this._dependencies;
  }

  set dependencies(value: Skill[][]) {
    this._dependencies = value;
  }

  static getWildcard(wildcardTierID: number): Skill {
    return new Skill(0, wildcardTierID, Tier.WILDCARD, false, false, false, 0, null, []);
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    return {
      id: this.id,
      tier: this.tierID,
      name: this.name,
      color: this.color ?? null,
      page: this.page ?? null,
      isCollab: this.isCollab,
      isExtra: this.isExtra,
      isActive: this.isActive,
      position: this.position,
      rule: this.ruleID,
      dependencies: this.dependencies.map(combo => combo.map(skill => skill.toJSON()))
    }
  }

  static fromDatabase(obj: SkillDatabase): Skill {
    return new Skill(
      obj.id,
      obj.tier,
      obj.name,
      obj.isCollab,
      obj.isExtra,
      obj.isActive,
      obj.position,
      obj.rule,
      obj.dependencies? Object.values(obj.dependencies).map(combo => combo.map(skill => Skill.fromDatabase(skill))) : null,
      obj.color ?? null,
      obj.page ?? null
    );
  }
}

export interface SkillDatabase {
  id: number,
  course: number,
  tier: number,
  name: string,
  color?: string,
  page?: string,
  isCollab: boolean,
  isExtra: boolean,
  isActive: boolean,
  position: number,
  rule: number,
  dependencies?: {[dependencyId: number]: SkillDatabase[]}
}
