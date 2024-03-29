export class RoleType {
  private _id: RoleTypeId;
  private _name?: string;

  constructor(id: string, name?: string) {
    this.id = RoleTypeId[id];
    if (name) this.name = name;
  }

  get id(): RoleTypeId {
    return this._id;
  }

  set id(value: RoleTypeId) {
    this._id = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  static fromDatabase(obj: RoleTypeDatabase): RoleType {
    return new RoleType(
      obj.id,
      obj.name || null
    );
  }
}

export enum RoleTypeId {
  ROLE_SINGLE = 'ROLE_SINGLE',
  ROLE_INTERACTION = 'ROLE_INTERACTION'
}

interface RoleTypeDatabase {
  id: string;
  name?: string;
}
