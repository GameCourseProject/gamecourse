import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../../../../../_services/api/api-endpoints.service";
import {ResourceManager} from "../../../../../../../../_utils/resources/resource-manager";
import {copyObject} from "../../../../../../../../_utils/misc/misc";

import {Module} from "../../../../../../../../_domain/modules/module";
import {InputType} from "../../../../../../../../_domain/modules/config/input-type";
import {Action, ActionScope, scopeAllows, showAtLeastOnce} from 'src/app/_domain/modules/config/Action';
import {DownloadManager} from "../../../../../../../../_utils/download/download-manager";
import {SkillsComponent} from "../personalized-config/skills/skills.component";
import {QrComponent} from "../personalized-config/qr/qr.component";

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
  personalizedConfig: string;

  importedFile: File;

  isItemModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;

  mode: 'add' | 'edit';
  newItem: {list: List, item: any, nrItems: number, index: number, first: boolean, last: boolean, even: boolean, odd: boolean} = {
    list: null,
    item: {},
    nrItems: null,
    index: null,
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
  doActionOnItem(listName: string, item: any, action: Action, parentID?: number): void {
    this.loadingAction = true;
    this.api.saveModuleConfig(this.courseID, this.module.id, null, item, listName, parentID, action)
      .pipe( finalize(() => {
        this.loadingAction = false;
        this.isItemModalOpen = false;
        this.isDeleteVerificationModalOpen = false;
        this.newItem = {list: null, item: {}, nrItems: null, index: null, first: null, last: null, even: null, odd: null};
        this.itemToDelete = null;
      }) )
      .subscribe(async () => await this.getModuleConfig(this.module.id))
  }

  toggleItemParam(list: List, item: any) {
    this.loadingAction = true;
    this.doActionOnItem(list.listName, item, Action.EDIT, list.parent ?? null);
  }

  moveItem(list: List, item: any, dir: number) {
    this.doActionOnItem(list.listName, item, dir > 0 ? Action.MOVE_UP : Action.MOVE_DOWN, list.parent);
  }

  viewItem(list: List, item: any) {
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
      .subscribe(res => {
        if (res.extension === ".csv") DownloadManager.downloadAsCSV(list.listName, res.file);
        if (res.extension === ".txt") DownloadManager.downloadAsText(list.listName, res.file);
        if (res.extension === ".zip") DownloadManager.downloadAsZip(list.listName, res.path);
      })
  }


  /*** --------------------------------------------- ***/
  /*** ------------ Personalized Config ------------ ***/
  /*** --------------------------------------------- ***/

  get PersonalizedConfig() {
    if (this.personalizedConfig === ApiHttpService.SKILLS) return SkillsComponent;
    if (this.personalizedConfig === ApiHttpService.QR) return QrComponent;
    else throw Error("Personalized config for module '" + this.module.id + "' not found.");
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initEditItem(list: List, item: any, index: number, first: boolean, last: boolean, even: boolean, odd: boolean): void {
    this.newItem = {list, item: copyObject(item), nrItems: list.items.length, index, first, last, even, odd};
  }

  hasAction(list: List, action: Action): boolean {
    for (const a of list.actions) {
      if (a.action === action) return true;
    }
    return false;
  }

  scopeAllows(scope: ActionScope, nrItems: number, index?: number, first?: boolean, last?: boolean, even?: boolean, odd?: boolean): boolean {
    return scopeAllows(scope, nrItems, index, first, last, even, odd);
  }

  showAtLeastOnce(scope: ActionScope, nrItems: number): boolean
  {
    return showAtLeastOnce(scope, nrItems);
  }

  async onFileSelected(files: FileList, type: 'image' | 'file', item?: any, param?: string): Promise<void> {
    if (type === 'image') {
      await ResourceManager.getBase64(files.item(0)).then(data => item[param] = data);
      this.image.set(files.item(0));

    } else this.importedFile = files.item(0);
    this.hasUnsavedChanges = true;
  }

  getImage(path: string): SafeUrl {
    this.image.set(path);
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

  get API_ENDPOINT(): string {
    return ApiEndpointsService.API_ENDPOINT;
  }
}

export interface GeneralInput {
  id: string,
  label: string,
  type: InputType,
  value: any,
  options?: {[key: string]: any}[]
}

export type List = {
  listName: string,
  itemName: string,
  parent?: number,
  importExtensions: string[],
  listInfo: {id: string, label: string, type: InputType}[],
  items: any[],
  actions?: {action: Action, scope: ActionScope}[],
  [Action.EDIT]?: {id: string, label: string, type: InputType, scope: ActionScope, options?: {[key: string]: any}}[]
}
