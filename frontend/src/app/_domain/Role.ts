export class Role {
  private _id: number;
  private _name: string;
  private _landingPage: string;
  private _courseID: number;
  private _isCourseAdmin: boolean;
  private _createdAt: Date;
  private _updatedAt: Date;

  constructor(source: Partial<Role>) {
    // const keysConverter = new ObjectKeysConverter();
    // source = keysConverter.keysToCamelCase(source);
    //
    // const typesConverter = new TypesConverter();
    // Object.keys(source).forEach(key => {
    //   if (source.hasOwnProperty(key)) {
    //     this[key] = typesConverter.fromDatabase(source[key]);
    //   }
    // });
    //
    // return this;
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

  get courseID(): number {
    return this._courseID;
  }

  set courseID(value: number) {
    this._courseID = value;
  }

  get isCourseAdmin(): boolean {
    return !!this._isCourseAdmin;
  }

  set isCourseAdmin(value: boolean) {
    this._isCourseAdmin = value;
  }

  get createdAt(): Date {
    return this._createdAt;
  }

  set createdAt(value: Date) {
    this._createdAt = value;
  }

  get updatedAt(): Date {
    return this._updatedAt;
  }

  set updatedAt(value: Date) {
    this._updatedAt = value;
  }
}
