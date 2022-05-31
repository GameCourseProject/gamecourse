import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../../../../../_services/api/api-endpoints.service";
import {ResourceManager} from "../../../../../../../../_utils/resources/resource-manager";
import {copyObject} from "../../../../../../../../_utils/misc/misc";

import {Module} from "../../../../../../../../_domain/modules/module";
import {InputType} from "../../../../../../../../_domain/inputs/input-type";
import {Action, ActionScope} from 'src/app/_domain/modules/config/Action';
import {DownloadManager} from "../../../../../../../../_utils/download/download-manager";

@Component({
  selector: 'app-config',
  templateUrl: './config.component.html',
  styleUrls: ['./config.component.scss']
})
export class ConfigComponent implements OnInit {

  loading: boolean = true;
  loadingAction = false;
  hasUnsavedChanges: boolean;

  courseID: number;
  module: Module;

  generalInputs: GeneralInput[];
  lists: List[];
  personalizedConfig: {html: string, styles: string[], scripts: string[]};

  importedFile: File;

  isItemModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;

  mode: 'add' | 'edit';
  newItem: {list: List, item: any, first: boolean, last: boolean, even: boolean, odd: boolean} = {
    list: null,
    item: {},
    first: null,
    last: null,
    even: null,
    odd: null
  };
  itemToDelete: {list: List, item: any};
  listToAct: List;

  image: ResourceManager;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer
  ) {
    this.image = new ResourceManager(sanitizer);
  }

  ngOnInit(): void {
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);

      this.route.params.subscribe(async childParams => {
        const moduleID = childParams.id;
        await this.getModule(this.courseID, moduleID);
        await this.getModuleConfig(moduleID);
        this.loading = false;
      });
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getModule(courseID: number, moduleID: string): Promise<void> {
    this.module = await this.api.getCourseModuleById(courseID, moduleID).toPromise();
  }

  async getModuleConfig(moduleID: string): Promise<void> {
    const config = await this.api.getModuleConfig(this.courseID, moduleID).toPromise();
    this.generalInputs = config.generalInputs;
    this.lists = config.lists;
    this.personalizedConfig = config.personalizedConfig;
    this.loadPersonalizedConfig();
  }

  loadPersonalizedConfig() {
    const head = document.getElementsByTagName('head')[0];

    // Load styles
    for (const styleURL of this.personalizedConfig.styles) {
      // Prevent unwanted browser caching
      const path = new ResourceManager(this.sanitizer);
      path.set(styleURL);

      const style = document.createElement('link');
      const name = (styleURL.split('/').pop()).split('.')[0];
      style.id = this.module.id + '-' + name + '-styling';
      style.rel = 'stylesheet';
      style.href = path.get('URL').toString();
      head.appendChild(style);
    }

    // Load scripts
    for (const scriptURL of this.personalizedConfig.scripts) {
      // Prevent unwanted browser caching
      const path = new ResourceManager(this.sanitizer);
      path.set(scriptURL);

      const script = document.createElement('script');
      const name = (scriptURL.split('/').pop()).split('.')[0];
      script.id = this.module.id + '-' + name + '-script';
      script.type = 'text/javascript';
      script.src = path.get('URL').toString();
      head.appendChild(script);
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  // General Inputs
  saveGeneralInputs() {
    this.loadingAction = true;
    this.api.saveModuleConfig(this.courseID, this.module.id, this.generalInputs)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(() => this.hasUnsavedChanges = false)
  }

  // Lists
  doActionOnItem(listName: string, item: any, action: Action): void {
    this.loadingAction = true;
    this.api.saveModuleConfig(this.courseID, this.module.id, null, item, listName, action)
      .pipe( finalize(() => {
        this.loadingAction = false;
        this.isItemModalOpen = false;
        this.isDeleteVerificationModalOpen = false;
        this.newItem = {list: null, item: {}, first: null, last: null, even: null, odd: null};
        this.itemToDelete = null;
      }) )
      .subscribe(async () => await this.getModuleConfig(this.module.id))
  }

  toggleItemParam(listName: string, item: any, param: string) {
    this.loadingAction = true;
    item[param] = !item[param];
    this.doActionOnItem(listName, item, Action.EDIT);
  }

  moveItem(list: List, item: any, dir: number) {
    // TODO
  }

  importItems(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedItems = reader.result;
      this.api.importModuleItems(this.courseID, this.module.id, this.listToAct.listName, importedItems, replace)
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.loadingAction = false;
        }) )
        .subscribe(
          async nrItems => {
            await this.getModuleConfig(this.module.id);

            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nrItems + " " + this.listToAct.itemName.capitalize() + (nrItems !== 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          })
    }
    reader.readAsText(this.importedFile);
  }

  exportItems(list: List, item?: any): void {
    this.loadingAction = true;
    this.api.exportModuleItems(this.courseID, this.module.id, list.listName, item?.id ?? undefined)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(contents => DownloadManager.downloadAsCSV(list.listName, contents))
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initEditItem(list: List, item: any, first: boolean, last: boolean, even: boolean, odd: boolean): void {
    this.newItem = {list, item: copyObject(item), first, last, even, odd};
  }

  scopeAllows(scope: ActionScope, first: boolean, last: boolean, even: boolean, odd: boolean): boolean {
    if (scope === ActionScope.ALL) return true;
    if (scope === ActionScope.FIRST && first) return true;
    if (scope === ActionScope.LAST && last) return true;
    if (scope === ActionScope.EVEN && even) return true;
    if (scope === ActionScope.ODD && odd) return true;
    if (scope === ActionScope.ALL_BUT_FIRST && !first) return true;
    if (scope === ActionScope.ALL_BUT_LAST && !last) return true;
    return false;
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
    this.hasUnsavedChanges = true;
  }

  getImage(path: string): SafeUrl {
    this.image.set(ApiEndpointsService.API_ENDPOINT + '/' + path);
    return this.image.get();
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

  get InputType(): typeof InputType {
    return InputType;
  }

  get Action(): typeof Action {
    return Action;
  }
}

export interface GeneralInput {
  id: string,
  label: string,
  type: InputType,
  value: any,
  options?: any // FIXME: either use options or remove
}

export type List = {
  listName: string,
  itemName: string,
  listInfo: {id: string, label: string, type: InputType}[],
  items: any[],
  actions?: {action: Action, scope: ActionScope}[],
  [Action.EDIT]?: {id: string, label: string, type: InputType, scope: ActionScope}[]
}
