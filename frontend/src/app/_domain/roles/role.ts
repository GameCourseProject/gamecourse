export class Role {
  private _id?: number;
  private _name?: string;
  private _landingPage?: string;
  private _children?: Role[];

  constructor(id?: number, name?: string, landingPage?: string, children?: Role[]) {

    this._id = id;
    this._name = name;
    this._landingPage = landingPage;
    this._children = children;
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

  get landingPage(): string {
    return this._landingPage;
  }

  set landingPage(value: string) {
    this._landingPage = value;
  }

  get children(): Role[] {
    return this._children;
  }

  set children(value: Role[]) {
    this._children = value;
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
   * Parses roles hierarchy coming from backend to an appropriate
   * format with nested roles.
   *
   * @param hierarchy
   * @param allRoles
   */
  static parseHierarchy(hierarchy: RoleDatabase[], allRoles: Role[]): Role[] {
    return hierarchy.map(obj => {
      const role = allRoles.find(el => el.id === parseInt(obj.id));
      if (obj.children) role.children = Role.parseHierarchy(obj.children, allRoles);
      return role;
    })
  }

  static fromDatabase(obj: RoleDatabase): Role {
    return new Role(
      obj.id ? parseInt(obj.id) : null,
      obj.name,
      obj.landingPage,
      obj.children ? obj.children.map(child => Role.fromDatabase(child)) : null
    );
  }
}

export interface RoleDatabase {
  id?: string,
  name: string,
  landingPage?: string
  children?: RoleDatabase[]
}
