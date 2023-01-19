import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {NgForm} from "@angular/forms";

import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {AlertService, AlertType} from "../../../../../../../../_services/alert.service";
import {ErrorService} from "../../../../../../../../_services/error.service";
import {ModalService} from "../../../../../../../../_services/modal.service";
import {ResourceManager} from "../../../../../../../../_utils/resources/resource-manager";
import {DownloadManager} from "../../../../../../../../_utils/download/download-manager";

import {Course} from "../../../../../../../../_domain/courses/course";
import {Module} from "../../../../../../../../_domain/modules/module";
import {InputType} from "../../../../../../../../_components/inputs/InputType";
import {Action, ActionScope, scopeAllows} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../../../_components/tables/table-data/table-data.component";
import {SkillsComponent} from "../personalized-config/skills/skills.component";
import {QrComponent} from "../personalized-config/qr/qr.component";
import {GooglesheetsComponent} from "../personalized-config/googlesheets/googlesheets.component";
import {ProgressReportComponent} from "../personalized-config/progress-report/progress-report.component";
import {ProfilingComponent} from "../personalized-config/profiling/profiling.component";
import {dateFromDatabase} from "../../../../../../../../_utils/misc/misc";

import * as _ from "lodash";


@Component({
  selector: 'app-config',
  templateUrl: './config.component.html'
})
export class ConfigComponent implements OnInit {

  loading = {
    page: true
  }

  course: Course;
  module: Module;

  generalInputs: ConfigSection[];
  unsavedGeneralInputs: ConfigSection[];
  lists: List[];
  personalizedConfig: string;

  mode: 'create' | 'duplicate' | 'edit' | 'delete' | string;
  itemToManage: ItemManageData;
  @ViewChild('fManage', { static: false }) fManage: NgForm;

  importData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      this.route.params.subscribe(async childParams => {
        const moduleID = childParams.id;
        await this.getModule(this.course.id, moduleID);
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


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getModule(courseID: number, moduleID: string): Promise<void> {
    this.module = await this.api.getCourseModuleById(courseID, moduleID).toPromise();
    this.module.icon.svg = this.module.icon.svg.replace('<svg', '<svg id="' + this.module.id + '-modal-icon"');
  }

  async getModuleConfig(moduleID: string): Promise<void> {
    const config = await this.api.getModuleConfig(this.course.id, moduleID).toPromise();

    // General inputs
    this.generalInputs = config.generalInputs;
    this.unsavedGeneralInputs = _.cloneDeep(this.generalInputs);

    // Lists
    this.lists = config.lists?.map(list => {
      list.loading = {table: true, action: false};

      // Update top actions
      if (list.topActions) {
        list.topActions.right = list.topActions.right.map(action => {
          if (action.action === Action.NEW) action.action = "Create " + list.itemName;
          return action;
        });
      }

      // Add actions to table
      if (list.actions?.length > 0 && list.headers[list.headers.length - 1].label !== 'Actions') {
        const nrItems = list.data.length;

        // Add headers
        const hasRuleAction = list.actions.find(action => action.action === Action.VIEW_RULE);
        if (hasRuleAction) list.headers.push({label: 'View Rule'});
        list.headers.push({label: 'Actions'});

        // Add cells
        list.data = list.data.map((row, index) => {
          if (hasRuleAction) {
            row.push({type: TableDataType.ACTIONS, content: {actions: list.actions
                  .filter(action => action.action === Action.VIEW_RULE)
                  .map(action => {
                    const a = _.cloneDeep(action);
                    a.disabled = !scopeAllows(action.scope, nrItems, index);
                    return a;
            })}});
          }
          row.push({type: TableDataType.ACTIONS, content: {actions: list.actions
                .filter(action => action.action !== Action.VIEW_RULE)
                .map(action => {
                  const a = _.cloneDeep(action);
                  a.disabled = !scopeAllows(action.scope, nrItems, index);
                  return a;
          })}});
          return row;
        });
        if (!list.options) list.options = {};
        if (!list.options.hasOwnProperty('columnDefs')) list.options['columnDefs'] = [];
        list.options['columnDefs'].push({orderable: false, targets: [list.headers.length - 1]});
        if (hasRuleAction) list.options['columnDefs'][list.options['columnDefs'].length - 1]['targets'].push(list.headers.length - 2);
      }

      // Parse dates
      for (let row of list.data) {
        for (let cell of row) {
          if (cell.type === TableDataType.DATE) cell.content['date'] = dateFromDatabase(cell.content['date']);
          if (cell.type === TableDataType.TIME) cell.content['time'] = dateFromDatabase(cell.content['time']);
          if (cell.type === TableDataType.DATETIME) cell.content['datetime'] = dateFromDatabase(cell.content['datetime']);
        }
      }

      list.loading.table = false;
      return list;
    });

    // Personalized config
    this.personalizedConfig = config.personalizedConfig;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  // GENERAL INPUTS

  async saveGeneralInputs(section: ConfigSection, form: NgForm) {
    if (form.valid) {
      section.loading = true;

      // Get section inputs
      const inputs = getInputs(section.contents, []);
      await this.api.saveModuleConfig(this.course.id, this.module.id, inputs).toPromise();
      const index = this.unsavedGeneralInputs.findIndex(s => s.name === section.name);
      this.generalInputs[index].contents = _.cloneDeep(section.contents);

      section.loading = false;
      AlertService.showAlert(AlertType.SUCCESS, section.successMsg ?? 'Changes saved successfully');

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

  async doAction(list: List, action: string) {
    if (action === Action.IMPORT) {
      if (!this.itemToManage) {
        this.itemToManage = this.initItemToManage(list);
        ModalService.openModal('import');

      } else {
        if (this.fImport.valid) {
          list.loading.action = true;

          try {
            const extensions = list[Action.IMPORT].extensions;
            const file = extensions.includes('.csv') || extensions.includes('.txt') ?
              await ResourceManager.getText(this.importData.file) :
              await ResourceManager.getBase64(this.importData.file);

            const nrItemsImported = await this.api.importModuleItems(this.course.id, this.module.id, list.name, file, this.importData.replace).toPromise();
            await this.getModuleConfig(this.module.id);

            list.loading.action = false;
            ModalService.closeModal('import');
            this.resetImport();
            AlertService.showAlert(AlertType.SUCCESS, nrItemsImported + ' ' + list.itemName + (nrItemsImported != 1 ? 's' : '') + ' imported');

          } catch (error) {
            list.loading.action = false;
          }

        } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
      }

    } else if (action === Action.EXPORT) {
      this.exportItems(list, list.items);

    } else if (action.containsWord('Create')) {
      this.mode = 'create';
      this.itemToManage = this.initItemToManage(list);
      ModalService.openModal('manage');

    } else if (list.hasOwnProperty(action) && list[action].hasOwnProperty("contents") && !ModalService.isOpen('manage')) {
      this.mode = action;
      this.itemToManage = this.initItemToManage(list);
      ModalService.openModal('manage');

    } else {
      if (action !== Action.DUPLICATE && action !== Action.DELETE)
        if (!this.fManage.valid) {
          AlertService.showAlert(AlertType.ERROR, 'Invalid form');
          return;
        }

      list.loading.action = true;

      try {
        const item = this.getItemToManage();
        let successMsg = await this.api.saveModuleConfig(this.course.id, this.module.id, null, item, list.name, action).toPromise();
        await this.getModuleConfig(this.module.id);

        list.loading.action = false;
        ModalService.closeModal('manage');
        this.resetManage();

        if (action === Action.NEW || action === Action.DUPLICATE) successMsg = 'New ' + list.itemName + ' created';
        else if (action === Action.EDIT) successMsg = list.itemName.capitalize() + ' edited';
        else if (action === Action.DELETE) successMsg = list.itemName.capitalize() + ' deleted';
        AlertService.showAlert(AlertType.SUCCESS, successMsg);

      } catch (error) {
        list.loading.action = false;
      }
    }
  }

  async doActionOnTable(list: List, action: string, row: number, col: number, value?: any): Promise<void> {
    const itemToActOn = list.items[row];

    if (action === 'value changed') {
      list.loading.action = true;

      const param = list.data[row][col].content['toggleId'];
      this.itemToManage = this.initItemToManage(list, itemToActOn, row);
      this.itemToManage.item[param] = value;

      await this.api.saveModuleConfig(this.course.id, this.module.id, null, this.itemToManage.item, list.name, Action.EDIT).toPromise();

      list.loading.action = false;

    } else if (action === Action.VIEW) { // TODO
      // const redirectLink = '/profile/' + userToActOn.id;
      // this.router.navigate([redirectLink]);

    } else if (action === Action.DUPLICATE) {
      this.mode = 'duplicate';
      this.itemToManage = this.initItemToManage(list, itemToActOn, row);
      this.doAction(list, Action.DUPLICATE);

    } else if (action === Action.EDIT) {
      this.mode = 'edit';
      this.itemToManage = this.initItemToManage(list, itemToActOn, row);
      ModalService.openModal('manage');

    } else if (action === Action.DELETE) {
      this.mode = 'delete';
      this.itemToManage = this.initItemToManage(list, itemToActOn, row);
      ModalService.openModal('delete-verification');

    } else if (action === Action.EXPORT) {
      this.exportItems(list, [itemToActOn]);

    } else if (action === Action.VIEW_RULE) {
      // const ruleLink = './rule-system/rule/' + itemToActOn["rule"]; // FIXME: redirect to rule
      const ruleLink = './rule-system'
      this.router.navigate([ruleLink], {relativeTo: this.route.parent});

    } else {
      this.mode = action;
      this.itemToManage = this.initItemToManage(list, itemToActOn, row);
      ModalService.openModal('manage');
    }
  }

  async exportItems(list: List, items: any[]): Promise<void> {
    if (items.length === 0)
      AlertService.showAlert(AlertType.WARNING, 'There are no ' + list.itemName + 's to export');

    else {
      list.loading.action = true;

      const contents = await this.api.exportModuleItems(this.course.id, this.module.id, list.name, items.map(item => item.id)).toPromise();

      if (contents.extension === '.csv') DownloadManager.downloadAsCSV((this.course.short ?? this.course.name) + '-' + list.itemName + "s", contents.file);
      else if (contents.extension === '.zip') DownloadManager.downloadAsZip(contents.path, this.api, this.course.id);

      list.loading.action = false;
    }
  }


  // PERSONALIZED CONFIG

  get PersonalizedConfig() {
    if (this.personalizedConfig === ApiHttpService.GOOGLESHEETS) return GooglesheetsComponent;
    if (this.personalizedConfig === ApiHttpService.PROGRESS_REPORT) return ProgressReportComponent;
    if (this.personalizedConfig === ApiHttpService.PROFILING) return ProfilingComponent;
    if (this.personalizedConfig === ApiHttpService.SKILLS) return SkillsComponent;
    if (this.personalizedConfig === ApiHttpService.QR) return QrComponent;

    ErrorService.set("Personalized config for module '" + this.module.id + "' not found.");
    return null;
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

  async onFileSelected(files: FileList, artifact: ConfigInputItem, accept: string): Promise<void> {
    // FIXME: should be more general than this (create input-image component)
    const isImage = accept.containsWord('image') || accept.containsWord('svg') || accept.containsWord('png') || accept.containsWord('jpg');
    if (isImage) await ResourceManager.getBase64(files.item(0)).then(data => artifact.value = data);
    else await ResourceManager.getText(files.item(0)).then(data => artifact.value = data);
  }

  scopeAllows(scope: ActionScope | string): boolean {
    if (!scope) return true;
    return scopeAllows(scope, this.itemToManage.list.data.length, this.itemToManage.index);
  }

  nullifyEmptyValues(item: any): any {
    for (let key of Object.keys(item)) {
      if (typeof item[key] === 'string' && item[key].isEmpty())
        item[key] = null;
    }
    return item;
  }

  getConfigKey(): string
  {
    if (this.mode === 'create') return Action.NEW;
    if (['duplicate', 'edit', 'delete'].includes(this.mode)) return Action.EDIT;
    return this.mode;
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

  initItemToManage(list: List, item?: any, index?: number): ItemManageData {
    const itemData = item ? _.cloneDeep(item) : getEmptyItem(list[this.getConfigKey()].contents, {});
    if (this.mode === 'create' || this.mode === 'edit' || this.mode === 'duplicate' || this.mode === 'delete')
      initValues(list[this.getConfigKey()]['contents'], itemData);
    return {
      list: list,
      item: itemData,
      index: index ?? null
    };

    function getEmptyItem(contents: (ConfigInputContainer|ConfigInputItem)[], item: any): any {
      for (const artifact of contents) {
        if (artifact.contentType === 'item')
          item[artifact.id] = artifact.type === InputType.SELECT || artifact.type === InputType.WEEKDAY ? [] : null;
        else item = getEmptyItem(artifact.contents, item);
      }
      return item;
    }

    function initValues(contents: (ConfigInputContainer|ConfigInputItem)[], item: any): any {
      for (const artifact of contents) {
        if (artifact.contentType === 'item') artifact.value = item[artifact.id];
        else initValues(artifact.contents, item);
      }
    }
  }

  getItemToManage(): ItemManageData {
    getItem(this.itemToManage.list[this.getConfigKey()]['contents'], this.itemToManage.item);
    return this.nullifyEmptyValues(this.itemToManage.item);

    function getItem(contents: (ConfigInputContainer|ConfigInputItem)[], item: any): any {
      for (const artifact of contents) {
        if (artifact.contentType === 'item')
          item[artifact.id] = artifact.value;
        else getItem(artifact.contents, item);
      }
    }
  }

  resetManage() {
    this.mode = null;
    this.itemToManage = null;
    this.fManage.resetForm();
  }

  resetImport() {
    this.importData = {file: null, replace: true};
    this.fImport.resetForm();
  }

}

export interface ConfigSection {
  name: string,
  btnText?: string,
  successMsg?: string,
  contents: (ConfigInputContainer|ConfigInputItem)[],
  loading: boolean
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
  name: string,
  itemName: string,
  topActions?: {left: {action: Action | string, icon?: string}[], right: {action: Action | string, icon?: string, outline?: boolean,
      color?: 'ghost' | 'primary' | 'secondary' | 'accent' | 'neutral' | 'info' | 'success' | 'warning' | 'error'}[]},
  headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
  data: {type: TableDataType, content: any}[][],
  actions?: {action: Action | string, scope: ActionScope | string, icon?: string, color?: 'ghost' | 'primary' | 'secondary' |
      'accent' | 'neutral' | 'info' | 'success' | 'warning' | 'error', disabled?: boolean}[],
  options?: any,
  loading: {
    table: boolean,
    action: boolean
  }
  items?: any[],
  [Action.NEW]?: {modalSize?: 'sm' | 'md' | 'lg', contents: (ConfigInputContainer|ConfigInputItem)[]},
  [Action.EDIT]?: {modalSize?: 'sm' | 'md' | 'lg', contents: (ConfigInputContainer|ConfigInputItem)[]},
  [Action.IMPORT]?: {extensions: string[], csvHeaders?: string[], csvRows?: string[][]}
}

export interface ItemManageData {
  list: List,
  item: any,
  index: number
}
