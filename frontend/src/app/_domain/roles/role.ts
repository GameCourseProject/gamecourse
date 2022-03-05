export class Role {
  private _id?: number; // FIXME: should use id to deal with roles; is using name
  private _name?: string;
  private _landingPage?: number;
  private _children?: Role[];

  constructor(id?: number, name?: string, landingPage?: number, children?: Role[]) {
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

  get landingPage(): number {
    return this._landingPage;
  }

  set landingPage(value: number) {
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
      obj.name.startsWith('role.') ? obj.name.substr(5) : obj.name,
      obj.landingPage ? parseInt(obj.landingPage) : null,
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
