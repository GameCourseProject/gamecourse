import { Component, OnInit } from '@angular/core';

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {ErrorService} from "../../../_services/error.service";

import {Module} from "../../../_domain/Module";
import {DownloadManager} from "../../../_utils/download/download-manager";
import {Reduce} from "../../../_utils/display/reduce";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {

  loading: boolean;

  allModules: Module[];

  reduce = new Reduce();
  searchQuery: string; // FIXME: create search component and remove this

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
          this.reduceList();
          },
          error => ErrorService.set(error),
        () => this.loading = false
  )
}


  /*** --------------------------------------------- ***/
  /*** ------------------- Search ------------------ ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.allModules, query);
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

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
  }
}

export interface ImportModulesData {
  file: string | ArrayBuffer,
  fileName: string
}
