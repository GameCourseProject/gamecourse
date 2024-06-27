import { Component, OnInit } from '@angular/core';

import {ApiHttpService} from "../../../../_services/api/api-http.service";

import {Module} from "../../../../_domain/modules/module";
import { ModuleType } from 'src/app/_domain/modules/ModuleType';
import {ModalService} from "../../../../_services/modal.service";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html'
})
export class ModulesComponent implements OnInit {

  loading = true;

  modules: Module[];
  filteredModules: Module[];

  modulesTypes: {[key in ModuleType]: string} = {
    GameElement: 'Game Elements',
    DataSource: 'Data Sources',
    Util: 'Tools'
  };

  constructor(
    private api: ApiHttpService
  ) { }

  async ngOnInit(): Promise<void> {
    await this.getModules();
    this.loading = false;
  }

  get ModuleType(): typeof ModuleType {
    return ModuleType;
  }

  get ModuleService(): typeof ModalService {
    return ModalService;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getModules(): Promise<void> {
    this.modules = await this.api.getModules().toPromise();
    this.filteredModules = this.modules;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  public importModule(): void {
    // FIXME: check if working
/*    this.loading = true;

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
    reader.readAsDataURL(this.importedFile);*/
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

  objectKeys(obj: object): string[] {
    return Object.keys(obj);
  }

  filterModules(type: ModuleType): Module[] {
    return this.filteredModules.filter(module => module.type === type);
  }

}

export interface ImportModulesData {
  file: string | ArrayBuffer,
  fileName: string
}
