export class Module {
  private _id: string;
  private _name: string;
  private _directory: string;
  private _version: string;
  private _dependencies: {id: string, mode?: string, enabled?: boolean}[];
  private _description: string;
  private _enabled: boolean;
  private _canBeEnabled: boolean;
  private _hasConfiguration: boolean;

  constructor(id: string, name: string, directory: string, version: string, dependencies: {id: string, mode?: string, enabled?: boolean}[],
              description: string, enabled: boolean, canBeEnabled: boolean, hasConfiguration: boolean) {

    this._id = id;
    this._name = name;
    this._directory = directory;
    this._version = version;
    this._dependencies = dependencies;
    this._description = description;
    this._enabled = enabled;
    this._canBeEnabled = canBeEnabled;
    this._hasConfiguration = hasConfiguration;
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

  get dependencies(): { id: string; mode?: string, enabled?: boolean }[] {
    return this._dependencies;
  }

  set dependencies(value: { id: string; mode?: string, enabled?: boolean }[]) {
    this._dependencies = value;
  }

  get description(): string {
    return this._description;
  }

  set description(value: string) {
    this._description = value;
  }

  get enabled(): boolean {
    return this._enabled;
  }

  set enabled(value: boolean) {
    this._enabled = value;
  }

  get canBeEnabled(): boolean {
    return this._canBeEnabled;
  }

  set canBeEnabled(value: boolean) {
    this._canBeEnabled = value;
  }

  get hasConfiguration(): boolean {
    return this._hasConfiguration;
  }

  set hasConfiguration(value: boolean) {
    this._hasConfiguration = value;
  }

  static fromDatabase(obj: ModuleDatabase): Module {
    return new Module(
      obj.id,
      obj.name,
      obj.dir,
      obj.version,
      obj.dependencies,
      obj.description,
      obj.enabled !== null && obj.enabled !== undefined ? !!obj.enabled : null,
      obj.canBeEnabled !== null && obj.canBeEnabled !== undefined ? !!obj.canBeEnabled : null,
      obj.hasConfiguration !== null && obj.hasConfiguration !== undefined ? !!obj.hasConfiguration : null
    );
  }
}

interface ModuleDatabase {
  id: string,
  name: string,
  dir: string,
  version: string,
  dependencies: {id: string, mode?: string, enabled?: boolean}[],
  description: string;
  enabled?: boolean;
  canBeEnabled?: boolean;
  hasConfiguration?: boolean;
}
