import { Component, OnInit } from '@angular/core';
import {User} from "../../../_domain/User";
import {swapPTCharacters} from "../../../_utils/swap-pt-chars";
import {Module} from "../../../_domain/Module";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import _ from 'lodash';
import {DownloadManager} from "../../../_utils/download-manager";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {

  loading: boolean;

  allModules: Module[];
  filteredModules: Module[];

  searchQuery: string;

  importedFile: File;

  isImportModalOpen: boolean;
  saving: boolean;

  constructor(
    private api: ApiHttpService
  ) { }

  get API_ENDPOINT(): string {
    return ApiEndpointsService.API_ENDPOINT;
  }

  ngOnInit(): void {
    this.getModules();
  }

  getModules(): void {
    this.loading = true;
    this.api.getSettingsModules()
      .subscribe(
        modules => {
          this.allModules = modules;
          this.filteredModules = _.cloneDeep(modules); // deep copy
          this.reduceList();
          },
          error => ErrorService.set(error),
        () => this.loading = false
  )
}


  /*** --------------------------------------------- ***/
  /*** ------------------- Search ------------------ ***/
  /*** --------------------------------------------- ***/

  onSearch(query: string): void {
    this.searchQuery = query;
    this.reduceList();
  }

  reduceList(): void {
    this.filteredModules = [];

    this.allModules.forEach(module => {
      if (this.isQueryTrueSearch(module, this.searchQuery))
        this.filteredModules.push(module);
    });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  public importModule(): void {
    this.loading = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedModule = reader.result;
      this.api.importModule({file: importedModule, fileName: this.importedFile.name})
        .subscribe(
          res => this.getModules(),
          error => ErrorService.set(error),
          () => {
            this.isImportModalOpen = false;
            this.loading = false;
          }
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  public exportModules(): void {
    this.loading = true;
    this.api.exportModules()
      .subscribe(zip => DownloadManager.downloadAsZip(zip, ApiEndpointsService.API_ENDPOINT),
        error => ErrorService.set(error),
      () => this.loading = false
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  parseForSearching(query: string): string[] {
    let res: string[];
    let temp: string;
    query = swapPTCharacters(query);

    res = query.toLowerCase().split(' ');

    temp = query.replace(' ', '').toLowerCase();
    if (!res.includes(temp)) res.push(temp);

    temp = query.toLowerCase();
    if (!res.includes(temp)) res.push(temp);
    return res;
  }

  isQueryTrueSearch(module: Module, query: string): boolean {
    return !query ||
      (module.name && !!this.parseForSearching(module.name).find(a => a.includes(query.toLowerCase())));
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
  }
}

export interface ImportModulesData {
  file: string | ArrayBuffer,
  fileName: string
}
