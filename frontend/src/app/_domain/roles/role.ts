export class Role {
  private _id: number;
  private _name: string;
  private _landingPage: number | null;
  private _children?: Role[];
  private _module?: string;

  constructor(id: number, name: string, landingPage: number | null, children?: Role[], module?: string) {
    this._id = id;
    this._name = name;
    this._landingPage = landingPage;
    if (children !== undefined) this._children = children;
    if (module !== undefined) this._module = module;
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

  get landingPage(): number | null{
    return this._landingPage;
  }

  set landingPage(value: number | null) {
    this._landingPage = value;
  }

  get children(): Role[] {
    return this._children;
  }

  set children(value: Role[]) {
    this._children = value;
  }

  get module(): string {
    return this._module;
  }

  set module(value: string) {
    this._module = value;
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON() {
    return {
      id: this.id,
      name: this.name,
      landingPage: this.landingPage,
      children: this.children
    }
  }

  /**
   * Parses a role from the format 'role.Default' to 'Default',
   * or 'role.Default>role.Default' to 'Default>Default'
   *
   * @param role
   */
  static parse(role: string): string {
    if (role.includes(">")) {
      const viewer = role.split(">")[1].split(".")[1];
      const user = role.split(">")[0].split(".")[1];
      return user + ">" + viewer;

    } else return role.split(".")[1];
  }

  /**
   * Unparses a role from the format 'Default' to 'role.Default',
   * or 'Default>Default' to 'role.Default>role.Default'
   *
   * @param role
   */
  static unparse(role: string): string {
    if (role.includes(">")) {
      const viewer = role.split(">")[1];
      const user = role.split(">")[0];
      return 'role.' + user + ">" + 'role.' + viewer;

    } else return 'role.' + role;
  }

  static fromDatabase(obj: RoleDatabase): Role {
    return new Role(
      obj.id,
      obj.name,
      obj.landingPage,
      obj.children ? obj.children.map(child => Role.fromDatabase(child)) : null,
      obj.module
    );
  }
}

export interface RoleDatabase {
  id: number,
  name: string,
  landingPage: number | null,
  children?: RoleDatabase[]
  module: string,
}
