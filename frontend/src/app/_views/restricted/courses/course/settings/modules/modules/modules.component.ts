import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../../../../_services/api/api-endpoints.service";

import {Module} from "../../../../../../../_domain/modules/module";
import {Reduce} from "../../../../../../../_utils/lists/reduce";
import {ModuleType} from "../../../../../../../_domain/modules/ModuleType";
import {DomSanitizer} from "@angular/platform-browser";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {

  loading: boolean;

  courseID: number;

  allModules: Module[];
  modulesTypes: {[key in ModuleType]: string} = {
    GameElement: 'Game Elements',
    DataSource: 'Data Sources'
  };

  reduce = new Reduce();
  searchQuery: string; // FIXME: create search component and remove this

  isModuleDetailsModalOpen: boolean;
  moduleOpen: Module;
  isEnabled: boolean;
  saving: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer
  ) { }

  get API_ENDPOINT(): string {
    return ApiEndpointsService.API_ENDPOINT;
  }

  get ModuleType(): typeof ModuleType {
    return ModuleType;
  }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getModules(this.courseID);
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getModules(courseID: number): void {
    this.loading = true;
    this.api.getCourseModules(courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(modules => {
        this.allModules = modules.sort((a, b) => a.name.localeCompare(b.name));
        this.reduceList();
      });
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.allModules, query);
  }

  filterList(modules: Module[], type: ModuleType): Module[] {
    return modules.filter(module => module.type === type);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  toggleEnabled(module: Module): void {
    this.saving = true;
    const isEnabled = !module.enabled;

    this.api.setModuleState(this.courseID, module.id, isEnabled)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(
        res => {
          module.enabled = !module.enabled;
          this.getModules(this.courseID);
          Module.reloadStyles(this.courseID, this.sanitizer);
        },
        error => {},
        () => {
          this.isModuleDetailsModalOpen = false;
          this.moduleOpen = null;
        }
      );
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  objectKeys(obj: object): string[] {
    return Object.keys(obj);
  }

}
