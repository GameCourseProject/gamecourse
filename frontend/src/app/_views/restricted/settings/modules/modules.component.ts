import { Component, OnInit } from '@angular/core';

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../_services/api/api-endpoints.service";

import {Module} from "../../../../_domain/modules/module";
import {DownloadManager} from "../../../../_utils/download/download-manager";
import {Reduce} from "../../../../_utils/lists/reduce";
import {finalize} from "rxjs/operators";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {

  loading = true;
  modules: Module[];
  versions: {project: string, api: string};

  reduce = new Reduce();
  searchQuery: string;

  importedFile: File;

  isImportModalOpen: boolean;
  saving: boolean;

  constructor(
    private api: ApiHttpService
  ) { }

  async ngOnInit(): Promise<void> {
    await this.getModules();
    this.loading = false;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getModules(): Promise<void> {
    this.modules = await this.api.getModules().toPromise();
    this.reduceList();
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Search ------------------ ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.modules, query);
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
        .subscribe(res => this.getModules())
    }
    reader.readAsDataURL(this.importedFile);
  }

  public exportModules(): void {
    // FIXME: not working
    // this.loading = true;
    // this.api.exportModules()
    //   .pipe( finalize(() => this.loading = false) )
    //   .subscribe(zip => DownloadManager.downloadAsZip(zip, ApiEndpointsService.API_ENDPOINT),
    //   )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isCompatible(module: Module): boolean {
    return module.compatibility.project && module.compatibility.api;
  }

  getIncompatibleString(module: Module): string {
    if (!module.compatibility.project && !module.compatibility.api)
      return "Project & API";
    if (!module.compatibility.project)
      return "Project";
    else return "API";
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
  }
}

export interface ImportModulesData {
  file: string | ArrayBuffer,
  fileName: string
}
