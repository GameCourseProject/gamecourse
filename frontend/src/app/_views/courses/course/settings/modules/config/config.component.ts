import {Component, OnInit} from '@angular/core';
import {Module} from "../../../../../../_domain/modules/module";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ErrorService} from "../../../../../../_services/error.service";
import {finalize} from "rxjs/operators";
import {InputType} from "../../../../../../_domain/inputs/input-type";
import {ImageManager} from "../../../../../../_utils/images/image-manager";
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../../../../_services/api/api-endpoints.service";
import {copyObject} from "../../../../../../_utils/misc/misc";
import {DownloadManager} from "../../../../../../_utils/download/download-manager";

@Component({
  selector: 'app-config',
  templateUrl: './config.component.html',
  styleUrls: ['./config.component.scss']
})
export class ConfigComponent implements OnInit {

  loading: boolean;
  loadingAction = false;
  hasUnsavedChanges: boolean;

  courseID: number;
  module: Module;
  courseFolder: string;

  generalInputs: {id: string, name: string, type: InputType, options: any, current_val: any}[];
  listingItems: {listName: string, itemName: string, header: string[], displayAttributes: {id: string, type: InputType}[],
                 items: any[], allAttributes: {id: string, name: string, type: InputType, options: any}[]};
  personalizedConfig; // TODO: put type
  tiers; // TODO: put type

  importedFile: File;

  isItemModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;

  mode: 'add' | 'edit';
  newItem: any = { };
  itemToEdit: any;
  itemToDelete: any;
  itemToExport: any;

  image: ImageManager;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer
  ) {
    this.image = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);

      this.route.params.subscribe(childParams => {
        this.getModuleConfigInfo(childParams.id);
      });
    });
  }

  get InputType(): typeof InputType {
    return InputType;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getModuleConfigInfo(moduleId: string): void {
    this.loading = true;
    this.api.getModuleConfigInfo(this.courseID, moduleId)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        info => {
          this.module = info.module;
          this.courseFolder = info.courseFolder;
          this.generalInputs = info.generalInputs;
          this.listingItems = info.listingItems;
          this.personalizedConfig = info.personalizedConfig;
          this.tiers = info.tiers;
        },
        error => ErrorService.set(error)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  saveGeneralInputs() {
    this.loadingAction = true;

    // Parse inputs
    const inputsObj = {};
    for (const input of this.generalInputs) {
      inputsObj[input.id] = input.current_val;
    }

    this.api.saveModuleConfigInfo(this.courseID, this.module.id, inputsObj)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(
        res => this.hasUnsavedChanges = false,
        error => ErrorService.set(error)
      )
  }

  createItem(): void {
    this.loadingAction = true;
    for (const param of this.listingItems.allAttributes) {
      if (!this.newItem.hasOwnProperty(param.id)) {
        this.newItem[param.id] = null;
      }
    }

    this.api.saveModuleConfigInfo(this.courseID, this.module.id, null, this.newItem, 'new')
      .pipe( finalize(() => {
        this.loadingAction = false;
        this.isItemModalOpen = false;
        this.newItem = {};
      }) )
      .subscribe(
        res => this.getModuleConfigInfo(this.module.id),
        error => ErrorService.set(error)
      )
  }

  editItem(): void {
    this.loadingAction = true;
    for (const param of this.listingItems.allAttributes) {
      if (!this.newItem.hasOwnProperty(param.id)) {
        this.itemToEdit[param.id] = null;
      }
    }

    this.api.saveModuleConfigInfo(this.courseID, this.module.id, null, this.itemToEdit, 'edit')
      .pipe( finalize(() => {
        this.loadingAction = false;
        this.isItemModalOpen = false;
        this.newItem = {};
      }) )
      .subscribe(
        res => this.getModuleConfigInfo(this.module.id),
        error => ErrorService.set(error)
      )
  }

  duplicateItem(item: any) {
    this.loadingAction = true;
    delete item.id;
    item.name = item.name + ' (Copy)';

    this.api.saveModuleConfigInfo(this.courseID, this.module.id, null, item, 'duplicate')
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(
        res => this.getModuleConfigInfo(this.module.id),
        error => ErrorService.set(error)
      )
  }

  deleteItem(item: any): void {
    this.loadingAction = true;

    this.api.saveModuleConfigInfo(this.courseID, this.module.id, null, item, 'delete')
      .pipe( finalize(() => {
        this.loadingAction = false;
        this.itemToDelete = null;
        this.isDeleteVerificationModalOpen = false
      }) )
      .subscribe(
        res => this.getModuleConfigInfo(this.module.id),
        error => ErrorService.set(error)
      )
  }

  importItems(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedItems = reader.result;
      this.api.importModuleItems(this.courseID, this.module.id, importedItems, replace)
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.loadingAction = false;
        }) )
        .subscribe(
          nrItems => {
            this.getModuleConfigInfo(this.module.id);
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nrItems + " " + this.listingItems.itemName + (nrItems > 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          },
          error => ErrorService.set(error)
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportItem(item: any): void {
    this.loadingAction = true;

    this.api.exportModuleItems(this.courseID, this.module.id, item?.id || null)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(
        res => DownloadManager.downloadAsCSV(res.fileName, res.contents),
        error => ErrorService.set(error)
      )
  }

  exportAllItems(): void {
    this.exportItem(null);
  }

  toggleItemParam(itemId: string, param: string) {
    this.loadingAction = true;

    const item = this.listingItems.items.find(item => item.id === itemId);
    item[param] = !item[param];

    this.api.toggleItemParam(this.courseID, this.module.id, parseInt(itemId), param)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(
        res => {},
        error => ErrorService.set(error)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  capitalize(str: string): string {
    return str.capitalize();
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
    this.hasUnsavedChanges = true;
  }

  initEditItem(item: any): void {
    this.newItem = copyObject(item);
    this.itemToEdit = item;
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

}
