import {ApiHttpService} from "../../_services/api/api-http.service";
import {exists} from "../../_utils/misc/misc";
import {ModuleType} from "./ModuleType";
import {ResourceManager} from "../../_utils/resources/resource-manager";
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";
import {ErrorService} from "../../_services/error.service";
import {DependencyMode} from "./DependencyMode";

export class Module {
  private _id: string;
  private _name: string;
  private _description: string;
  private _icon: {url: string, svg: string};
  private _type: ModuleType;
  private _version: string;
  private _configurable: boolean;
  private _compatibility: {project: boolean, api: boolean};
  private _dependencies?: Module[];
  private _dependants?: Module[];
  private _enabled?: boolean;
  private _canChangeState?: boolean;
  private _dependencyMode?: DependencyMode;

  static stylesLoaded: Map<number, {state: LoadingState, stylesIds?: string[]}> = new Map<number, {state: LoadingState, stylesIds?: string[]}>();

  constructor(id: string, name: string, description: string, icon: {url: string, svg: string}, type: ModuleType, version: string,
              configurable: boolean, compatibility: {project: boolean, api: boolean}, dependencies?: Module[], dependants?: Module[],
              enabled?: boolean, canChangeState?: boolean, dependencyMode?: DependencyMode) {

    this._id = id;
    this._name = name;
    this._description = description;
    this._icon = icon;
    this._type = type;
    this._version = version;
    this._configurable = configurable;
    this._compatibility = compatibility;
    if (exists(dependencies)) this._dependencies = dependencies;
    if (exists(dependants)) this._dependants = dependants;
    if (exists(enabled)) this._enabled = enabled;
    if (exists(canChangeState)) this._canChangeState = canChangeState;
    if (exists(dependencyMode)) this._dependencyMode = dependencyMode;
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

  get icon(): {url: string, svg: string} {
    return this._icon;
  }

  set icon(value: {url: string, svg: string}) {
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

  get configurable(): boolean {
    return this._configurable;
  }

  set configurable(value: boolean) {
    this._configurable = value;
  }

  get compatibility(): { project: boolean; api: boolean } {
    return this._compatibility;
  }

  set compatibility(value: { project: boolean; api: boolean }) {
    this._compatibility = value;
  }

  get dependencies(): Module[] {
    return this._dependencies;
  }

  set dependencies(value: Module[]) {
    this._dependencies = value;
  }

  get dependants(): Module[] {
    return this._dependants;
  }

  set dependants(value: Module[]) {
    this._dependants = value;
  }

  get enabled(): boolean {
    return this._enabled;
  }

  set enabled(value: boolean) {
    this._enabled = value;
  }

  get canChangeState(): boolean {
    return this._canChangeState;
  }

  set canChangeState(value: boolean) {
    this._canChangeState = value;
  }

  get dependencyMode(): DependencyMode {
    return this._dependencyMode;
  }

  set dependencyMode(value: DependencyMode) {
    this._dependencyMode = value;
  }

  /**
   * Loads course's active modules' styles.
   *
   * @param courseId
   * @param sanitizer
   */
  static loadStyles(courseId: number, sanitizer: DomSanitizer): void {
    Module.stylesLoaded.set(courseId, {state: LoadingState.PENDING});

    // Remove other course's styles
    this.stylesLoaded.forEach((value, courseId, map) => {
      if (value.stylesIds) this.removeStyles(courseId);
    })

    ApiHttpService.getModulesResources(courseId, true)
      .subscribe(resources => {
        const styles: {name: string, path: SafeUrl}[] = [];
        for (const [moduleId, moduleResources] of Object.entries(resources)) {
          for (const [key, keyResources] of Object.entries(moduleResources)) {

            if (key === 'styles') {
              for (const resourcePath of keyResources) {
                // Prevent unwanted browser caching
                const path = new ResourceManager(sanitizer);
                path.set(resourcePath);

                const resourceName = (resourcePath.split('/').pop()).split('.')[0];
                styles.push({
                  name: courseId + '-' + moduleId + '-' + resourceName,
                  path: path.get('URL')
                });
              }
            }
          }
        }

        // Load course styles
        const head = document.getElementsByTagName('head')[0];
        const stylesIds: string[] = [];
        styles.forEach(s => {
          const style = document.createElement('link');
          const id = s.name + '-styling';
          style.id = id;
          stylesIds.push(id);
          style.rel = 'stylesheet';
          style.href = s.path.toString();
          head.appendChild(style);
        });

        this.stylesLoaded.set(courseId, {state: LoadingState.LOADED, stylesIds});

      }, error => ErrorService.set(error));
  }

  /**
   * Reloads course's modules' styles
   *
   * @param courseId
   * @param sanitizer
   */
  static reloadStyles(courseId: number, sanitizer: DomSanitizer): void {
    this.removeStyles(courseId);
    this.loadStyles(courseId, sanitizer);
  }

  /**
   * Removes course's modules' styles
   *
   * @param courseId
   */
  static removeStyles(courseId: number): void {
    this.stylesLoaded.get(courseId).stylesIds.forEach(id => {
      const style = document.getElementById(id);
      style.remove();
    });
    this.stylesLoaded.delete(courseId);
  }

  static fromDatabase(obj: ModuleDatabase): Module {
    return new Module(
      obj.id,
      obj.name,
      obj.description,
      {url: obj.icon, svg: obj.iconSVG},
      obj.type as ModuleType,
      obj.version,
      obj.configurable,
      obj.compatibility,
      obj.dependencies ? obj.dependencies.map(dep => Module.fromDatabase(dep)) : null,
      obj.dependants ? obj.dependants.map(dep => Module.fromDatabase(dep)) : null,
      obj.isEnabled ?? null,
      obj.canChangeState ?? null,
      obj.mode as DependencyMode ?? null
    );
  }
}

interface ModuleDatabase {
  id: string,
  name: string,
  description: string;
  icon: string;
  iconSVG: string;
  type: string;
  version: string,
  configurable: boolean;
  compatibility: {project: boolean, api: boolean},
  dependencies?: ModuleDatabase[],
  dependants?: ModuleDatabase[],
  isEnabled?: boolean;
  canChangeState?: boolean;
  mode?: DependencyMode;
}

export enum LoadingState {
  NOT_LOADED,
  LOADED,
  PENDING
}
