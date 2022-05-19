import {ApiHttpService} from "../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../_services/api/api-endpoints.service";
import {exists} from "../../_utils/misc/misc";
import {ModuleType} from "./ModuleType";
import {ResourceManager} from "../../_utils/resources/resource-manager";
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";

export class Module {
  private _id: string;
  private _name: string;
  private _description: string;
  private _icon: string;
  private _type: ModuleType;
  private _version: string;
  private _compatibility: {project: boolean, api: boolean};
  private _dependencies?: {id: string, mode?: string, enabled?: boolean}[];
  private _enabled?: boolean;
  private _canBeEnabled?: boolean;
  private _hasConfiguration?: boolean;

  static stylesLoaded: Map<number, {state: LoadingState, stylesIds?: string[]}> = new Map<number, {state: LoadingState, stylesIds?: string[]}>();

  constructor(id: string, name: string, description: string, icon: string, type: ModuleType, version: string,
              compatibility: {project: boolean, api: boolean}, dependencies?: {id: string, mode?: string, enabled?: boolean}[],
              enabled?: boolean, canBeEnabled?: boolean, hasConfiguration?: boolean) {

    this._id = id;
    this._name = name;
    this._description = description;
    this._icon = icon;
    this._type = type;
    this._version = version;
    this._compatibility = compatibility;
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

  get icon(): string {
    return this._icon;
  }

  set icon(value: string) {
    this._icon = value;
  }

  get type(): ModuleType {
    return this._type;
  }

  set type(value: ModuleType) {
    this._type = value;
  }

  get version(): string {
    return this._version;
  }

  set version(value: string) {
    this._version = value;
  }

  get compatibility(): { project: boolean; api: boolean } {
    return this._compatibility;
  }

  set compatibility(value: { project: boolean; api: boolean }) {
    this._compatibility = value;
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
   * @param sanitizer
   */
  static loadStyles(courseId: number, sanitizer: DomSanitizer): void {
    Module.stylesLoaded.set(courseId, {state: LoadingState.PENDING});

    ApiHttpService.getCourseResources(courseId)
      .subscribe(resources => {
        const styles: {name: string, path: SafeUrl}[] = [];
        for (const module of resources) {
          for (const resource of module.files) {
            if (resource.includes('.css')) {
              const split = resource.split('/');

              // Prevent unwanted browser caching
              const path = new ResourceManager(sanitizer);
              path.set(ApiEndpointsService.API_ENDPOINT + '/' + resource);

              styles.push({
                name: courseId + '-' + split[split.length - 1].replace('.css', ''),
                path: path.get('URL')
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
   * @param sanitizer
   */
  static reloadStyles(courseId: number, sanitizer: DomSanitizer): void {
    this.stylesLoaded.get(courseId).stylesIds.forEach(id => {
      const style = document.getElementById(id);
      style.remove();
    });

    this.loadStyles(courseId, sanitizer);
  }

  static fromDatabase(obj: ModuleDatabase): Module {
    return new Module(
      obj.id,
      obj.name,
      obj.description,
      obj.icon,
      obj.type as ModuleType,
      obj.version,
      obj.compatibility,
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
  icon: string;
  type: string;
  version: string,
  compatibility: {project: boolean, api: boolean},
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
