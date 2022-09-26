import {AfterViewInit, Component, HostListener, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../../../../../_services/api/api-endpoints.service";
import {ResourceManager} from "../../../../../../../../_utils/resources/resource-manager";
import {clearEmptyValues, copyObject, dateFromDatabase} from "../../../../../../../../_utils/misc/misc";

import {Module} from "../../../../../../../../_domain/modules/module";
import {InputType} from "../../../../../../../../_components/inputs/InputType";
import {Action, ActionScope, scopeAllows, showAtLeastOnce} from 'src/app/_domain/modules/config/Action';
import {DownloadManager} from "../../../../../../../../_utils/download/download-manager";
import {SkillsComponent} from "../personalized-config/skills/skills.component";
import {QrComponent} from "../personalized-config/qr/qr.component";
import {FenixComponent} from "../personalized-config/fenix/fenix.component";
import {GooglesheetsComponent} from "../personalized-config/googlesheets/googlesheets.component";
import {NotificationsComponent} from "../personalized-config/notifications/notifications.component";
import {ProfilingComponent} from "../personalized-config/profiling/profiling.component";
import {NgForm} from "@angular/forms";

import * as _ from "lodash";
import {UpdateType} from "../../../../../../../../_services/update.service";
import {AlertService, AlertType} from "../../../../../../../../_services/alert.service";

@Component({
  selector: 'app-config',
  templateUrl: './config.component.html'
})
export class ConfigComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    form: null
  }

  courseID: number;
  module: Module;

  generalInputs: ConfigSection[];
  unsavedGeneralInputs: ConfigSection[];
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

        this.loading.page = false;
        setTimeout(() => this.initIcon());
      });
    });
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


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getModule(courseID: number, moduleID: string): Promise<void> {
    this.module = await this.api.getCourseModuleById(courseID, moduleID).toPromise();
    this.module.icon.svg = this.module.icon.svg.replace('<svg', '<svg id="' + this.module.id + '-modal-icon"');
  }

  async getModuleConfig(moduleID: string): Promise<void> {
    const config = await this.api.getModuleConfig(this.courseID, moduleID).toPromise();
    this.generalInputs = config.generalInputs;
    this.unsavedGeneralInputs = _.cloneDeep(this.generalInputs);
    this.lists = config.lists;
    this.personalizedConfig = config.personalizedConfig;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  // GENERAL INPUTS

  async saveGeneralInputs(section: ConfigSection, form: NgForm) {
    if (form.valid) {
      this.loading.form = form;
      this.loading.action = true;

      // Get section inputs
      const inputs = getInputs(section.contents, []);
      await this.api.saveModuleConfig(this.courseID, this.module.id, inputs).toPromise();
      const index = this.unsavedGeneralInputs.findIndex(s => s.name === section.name);
      this.generalInputs[index].contents = _.cloneDeep(section.contents);

      this.loading.action = false;
      this.loading.form = null;
      AlertService.showAlert(AlertType.SUCCESS, 'Changed saved successfully');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');

    function getInputs(contents: (ConfigInputContainer|ConfigInputItem)[], inputs: ConfigInputItem[]): ConfigInputItem[] {
      for (const artifact of contents) {
        if (artifact.contentType === 'container') {
          inputs = inputs.concat(getInputs(artifact.contents, inputs));

        } else if (artifact.contentType === 'item') inputs.push(artifact);
      }
      return inputs;
    }
  }

  discardGeneralInputs(section: ConfigSection, form: NgForm) {
    const index = this.unsavedGeneralInputs.findIndex(s => s.name === section.name);
    this.unsavedGeneralInputs[index].contents = _.cloneDeep(this.generalInputs[index].contents);

    const formValues = {};
    for (const ID of this.getInputIDs(section.contents)) {
      formValues[ID] = getOriginalValue(section.contents, ID);
    }
    form.resetForm(formValues);

    function getOriginalValue(contents: (ConfigInputContainer|ConfigInputItem)[], ID: string): any {
      for (const artifact of contents) {
        if (artifact.contentType === 'container') {
          const value = getOriginalValue(artifact.contents, ID);
          if (value !== 'not-found') return value;

        } else if (artifact.contentType === 'item' && artifact.id === ID) return artifact.value;
      }
      return 'not-found';
    }
  }


  // LISTS

  doActionOnItem(listName: string, item: any, action: Action, parentID?: number): void {
    // this.loadingAction = true;
    // this.api.saveModuleConfig(this.courseID, this.module.id, null, item, listName, parentID, action)
    //   .pipe( finalize(() => {
    //     this.loadingAction = false;
    //     this.isItemModalOpen = false;
    //     this.isDeleteVerificationModalOpen = false;
    //     this.newItem = {list: null, item: {}, nrItems: null, index: null, first: null, last: null, even: null, odd: null};
    //     this.itemToDelete = null;
    //   }) )
    //   .subscribe(async () => await this.getModuleConfig(this.module.id))
  }

  toggleItemParam(list: List, item: any) {
    // this.loadingAction = true;
    // this.doActionOnItem(list.listName, item, Action.EDIT, list.parent ?? null);
  }

  moveItem(list: List, item: any, dir: number) {
    // this.doActionOnItem(list.listName, item, dir > 0 ? Action.MOVE_UP : Action.MOVE_DOWN, list.parent);
  }

  viewItem(list: List, item: any) {
    // TODO
  }

  importItems(replace: boolean): void {
    // this.loadingAction = true;
    //
    // const reader = new FileReader();
    // reader.onload = (e) => {
    //   const importedItems = reader.result;
    //   this.api.importModuleItems(this.courseID, this.module.id, this.listToAct.listName, importedItems, replace)
    //     .pipe( finalize(() => {
    //       this.isImportModalOpen = false;
    //       this.loadingAction = false;
    //     }) )
    //     .subscribe(
    //       async nrItems => {
    //         await this.getModuleConfig(this.module.id);
    //
    //         const successBox = $('#action_completed');
    //         successBox.empty();
    //         successBox.append(nrItems + " " + this.listToAct.itemName.capitalize() + (nrItems !== 1 ? 's' : '') + " Imported");
    //         successBox.show().delay(3000).fadeOut();
    //       })
    // }
    // reader.readAsText(this.importedFile);
  }

  exportItems(list: List, item?: any): void {
    // this.loadingAction = true;
    // this.api.exportModuleItems(this.courseID, this.module.id, list.listName, item?.id ?? undefined)
    //   .pipe( finalize(() => this.loadingAction = false) )
    //   .subscribe(res => {
    //     if (res.extension === ".csv") DownloadManager.downloadAsCSV(list.listName, res.file);
    //     if (res.extension === ".txt") DownloadManager.downloadAsText(list.listName, res.file);
    //     if (res.extension === ".zip") DownloadManager.downloadAsZip(list.listName, res.path);
    //   })
  }


  /*** --------------------------------------------- ***/
  /*** ------------ Personalized Config ------------ ***/
  /*** --------------------------------------------- ***/

  get PersonalizedConfig() {
    if (this.personalizedConfig === ApiHttpService.FENIX) return FenixComponent;
    if (this.personalizedConfig === ApiHttpService.GOOGLESHEETS) return GooglesheetsComponent;
    if (this.personalizedConfig === ApiHttpService.NOTIFICATIONS) return NotificationsComponent;
    if (this.personalizedConfig === ApiHttpService.PROFILING) return ProfilingComponent;
    if (this.personalizedConfig === ApiHttpService.SKILLS) return SkillsComponent;
    if (this.personalizedConfig === ApiHttpService.QR) return QrComponent;
    else throw Error("Personalized config for module '" + this.module.id + "' not found.");
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initIcon() {
    setTimeout(() => {
      const svg = document.getElementById(this.module.id + '-modal-icon');
      svg.style.width = '2.5rem';
      svg.style.height = '2.5rem';
    }, 0);
  }


  // General Inputs

  getWidth(width: 'full' | '1/2' | '1/3' | '1/4'): string {
    if (width === '1/2') return 'w-[calc(100%+1.5rem)] sm:w-1/2';
    if (width === '1/3') return 'w-[calc(100%+1.5rem)] sm:w-1/2 lg:w-1/3';
    if (width === '1/4') return 'w-[calc(100%+1.5rem)] sm:w-1/2 md:w-1/3 lg:w-1/4';
    return 'w-[calc(100%+1.5rem)]';
  }

  getMarginTOP(item: boolean, width: 'full' | '1/2' | '1/3' | '1/4', index: number): string {
    if (!item) return '';
    if (width === '1/2') return (index !== 0 ? 'mt-3' : '') + (index > 1 ? ' sm:mt-3' : ' sm:mt-0');
    if (width === '1/3') return (index !== 0 ? 'mt-3' : '') + (index > 1 ? ' sm:mt-3' : ' sm:mt-0') + (index > 2 ? ' lg:mt-3' : ' lg:mt-0');
    if (width === '1/4') return (index !== 0 ? 'mt-3' : '') + (index > 1 ? ' sm:mt-3' : ' sm:mt-0') + (index > 2 ? ' md:mt-3' : ' md:mt-0') + (index > 3 ? ' lg:mt-3' : ' lg:mt-0');
    return (index !== 0 ? 'mt-3' : '');
  }

  getHelperPosition(width: 'full' | '1/2' | '1/3' | '1/4', index: number): 'top' | 'bottom' | 'left' | 'right' {
    if (width === 'full') return 'right';

    if (window.innerWidth < 640) { // xs
      return 'right';

    } else if (window.innerWidth < 768) { // sm
      if (index % 2 === 0) return 'right';
      else return 'left';

    } else if (window.innerWidth < 1024) { // md
      if (width === '1/2' || width === '1/3') {
        if (index % 2 === 0) return 'right';
        else return 'left';

      } else {
        if (index % 3 === 0) return 'right';
        if (index % 3 === 1) return 'top';
        else return 'left';
      }

    } else { // lg or upper
      if (width === '1/2') {
        if (index % 2 === 0) return 'right';
        else return 'left';

      } else if (width === '1/3') {
        if (index % 3 === 0) return 'right';
        if (index % 3 === 1) return 'top';
        else return 'left';

      } else {
        if (index % 4 === 0) return 'right';
        if (index % 4 === 1 || index % 4 === 2) return 'top';
        else return 'left';
      }
    }
  }

  getInputIDs(contents: (ConfigInputContainer|ConfigInputItem)[]): string[] {
    const IDs = [];
    for (const artifact of contents) {
      if (artifact.contentType === 'item') IDs.push(artifact.id);
    }
    return IDs;
  }


  // Lists

  initEditItem(list: List, item: any, index: number, first: boolean, last: boolean, even: boolean, odd: boolean): void {
    this.newItem = {list, item: copyObject(item), nrItems: list.items.length, index, first, last, even, odd};
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
    // this.hasUnsavedChanges = true;
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

  formatDate(date: string, format: string): string {
    if (!date) return 'Never';
    return dateFromDatabase(date).format(format);
  }

}

export interface ConfigSection {
  name: string,
  contents: (ConfigInputContainer|ConfigInputItem)[]
}

export interface ConfigInputContainer {
  contentType: 'container',
  classList?: string,
  width?: 'full' | '1/2' | '1/3' | '1/4',
  contents: (ConfigInputContainer|ConfigInputItem)[]
}

export interface ConfigInputItem {
  contentType: 'item',
  classList?: string,
  width?: 'full' | '1/2' | '1/3' | '1/4',
  type: InputType,
  id: string,
  value: any,
  placeholder?: string,
  options?: {[key: string]: any}[],
  helper?: string
}

export type List = {
  listName: string,
  itemName: string,
  parent?: number,
  listActions?: Action[],
  listInfo: {id: string, label: string, type: InputType}[],
  items: any[],
  actions?: {action: Action, scope: ActionScope}[],
  [Action.EDIT]?: {id: string, label: string, type: InputType, scope: ActionScope, options?: {[key: string]: any}}[],
  [Action.IMPORT]?: {extensions: string[]}
}
