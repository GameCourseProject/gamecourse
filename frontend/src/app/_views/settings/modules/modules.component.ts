import { Component, OnInit } from '@angular/core';

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {ErrorService} from "../../../_services/error.service";

import {Module} from "../../../_domain/modules/module";
import {DownloadManager} from "../../../_utils/download/download-manager";
import {Reduce} from "../../../_utils/display/reduce";
import {finalize} from "rxjs/operators";

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
    this.api.getModulesAvailable()
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        modules => {
          this.allModules = modules;
          this.reduceList();
          },
          error => ErrorService.set(error)
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
    // FIXME: check if working
    this.loading = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedModule = reader.result;
      this.api.importModule({file: importedModule, fileName: this.importedFile.name})
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.loading = false
        }) )
        .subscribe(
          res => this.getModules(),
          error => ErrorService.set(error)
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  public exportModules(): void {
    // FIXME: not working
    this.loading = true;
    this.api.exportModules()
      .pipe( finalize(() => this.loading = false) )
      .subscribe(zip => DownloadManager.downloadAsZip(zip, ApiEndpointsService.API_ENDPOINT),
        error => ErrorService.set(error)
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
