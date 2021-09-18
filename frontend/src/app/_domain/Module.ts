export class Module {
  private _id: string;
  private _name: string;
  private _directory: string;
  private _version: string;
  private _dependencies: {id: string, mode: string}[];
  private _description: string;

  constructor(id: string, name: string, directory: string, version: string, dependencies: {id: string, mode: string}[],
              description: string) {

    this._id = id;
    this._name = name;
    this._directory = directory;
    this._version = version;
    this._dependencies = dependencies;
    this._description = description;
  }

  get id(): string {
    return this._id;
  }

  set id(value: string) {
    this._id = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get directory(): string {
    return this._directory;
  }

  set directory(value: string) {
    this._directory = value;
  }

  get version(): string {
    return this._version;
  }

  set version(value: string) {
    this._version = value;
  }

  get dependencies(): { id: string; mode: string }[] {
    return this._dependencies;
  }

  set dependencies(value: { id: string; mode: string }[]) {
    this._dependencies = value;
  }

  get description(): string {
    return this._description;
  }

  set description(value: string) {
    this._description = value;
  }

  static fromDatabase(obj: ModuleDatabase): Module {
    return new Module(
      obj.id,
      obj.name,
      obj.dir,
      obj.version,
      obj.dependencies,
      obj.description
    );
  }
}

interface ModuleDatabase {
  id: string,
  name: string,
  dir: string,
  version: string,
  dependencies: {id: string, mode: string}[],
  description: string;
}
