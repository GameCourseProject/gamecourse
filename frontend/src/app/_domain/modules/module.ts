import {ApiHttpService} from "../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../_services/api/api-endpoints.service";
import {exists} from "../../_utils/misc/misc";
import {ModuleType} from "./ModuleType";

export class Module {
  private _id: string;
  private _name: string;
  private _description: string;
  private _type: ModuleType;
  private _directory: string;
  private _version: string;
  private _dependencies: {id: string, mode?: string, enabled?: boolean}[];
  private _enabled: boolean;
  private _canBeEnabled: boolean;
  private _hasConfiguration: boolean;

  static stylesLoaded: Map<number, {state: LoadingState, stylesIds?: string[]}> = new Map<number, {state: LoadingState, stylesIds?: string[]}>();

  constructor(id: string, name: string, description: string, type: ModuleType, directory?: string, version?: string,
              dependencies?: {id: string, mode?: string, enabled?: boolean}[], enabled?: boolean, canBeEnabled?: boolean,
              hasConfiguration?: boolean) {

    this._id = id;
    this._name = name;
    this._description = description;
    this._type = type;
    if (exists(directory)) this._directory = directory;
    if (exists(version)) this._version = version;
    if (exists(dependencies)) this._dependencies = dependencies;
    if (exists(enabled)) this._enabled = enabled;
    if (exists(canBeEnabled)) this._canBeEnabled = canBeEnabled;
    if (exists(hasConfiguration)) this._hasConfiguration = hasConfiguration;
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

  get description(): string {
    return this._description;
  }

  set description(value: string) {
    this._description = value;
  }

  get type(): ModuleType {
    return this._type;
  }

  set type(value: ModuleType) {
    this._type = value;
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

  /**
   * Loads course's active modules' styles.
   *
   * @param courseId
   */
  static loadStyles(courseId: number): void {
    Module.stylesLoaded.set(courseId, {state: LoadingState.PENDING});

    ApiHttpService.getCourseResources(courseId)
      .subscribe(resources => {
        const styles: {name: string, path: string}[] = [];
        for (const module of resources) {
          for (const resource of module.files) {
            if (resource.includes('.css')) {
              const split = resource.split('/');
              styles.push({
                name: courseId + '-' + split[split.length - 1].replace('.css', ''),
                path: ApiEndpointsService.API_ENDPOINT + '/' + resource
              });
            }
          }
        }

        const head = document.getElementsByTagName('head')[0];
        const stylesIds: string[] = [];
        styles.forEach(s => {
          const style = document.createElement('link');
          const id = s.name + '-styling';
          style.id = id;
          stylesIds.push(id);
          style.rel = 'stylesheet';
          style.href = `${s.path}`;
          head.appendChild(style);
        });

        this.stylesLoaded.set(courseId, {state: LoadingState.LOADED, stylesIds});
      });
  }

  /**
   * Removes course's modules' styles and reloads them
   *
   * @param courseId
   */
  static reloadStyles(courseId: number): void {
    this.stylesLoaded.get(courseId).stylesIds.forEach(id => {
      const style = document.getElementById(id);
      style.remove();
    });

    this.loadStyles(courseId);
  }

  static fromDatabase(obj: ModuleDatabase): Module {
    return new Module(
      obj.id,
      obj.name,
      obj.description,
      obj.type as ModuleType,
      obj.dir || null,
      obj.version || null,
      obj.dependencies || null,
      exists(obj.enabled) ? !!obj.enabled : null,
      exists(obj.canBeEnabled) ? !!obj.canBeEnabled : null,
      exists(obj.hasConfiguration) ? !!obj.hasConfiguration : null
    );
  }
}

interface ModuleDatabase {
  id: string,
  name: string,
  description: string;
  type: string;
  dir?: string,
  version?: string,
  dependencies?: {id: string, mode?: string, enabled?: boolean}[],
  enabled?: boolean;
  canBeEnabled?: boolean;
  hasConfiguration?: boolean;
}

export enum LoadingState {
  NOT_LOADED,
  LOADED,
  PENDING
}