import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../../../_services/api/api-endpoints.service";
import {ErrorService} from "../../../../../../_services/error.service";

import {Module} from "../../../../../../_domain/modules/module";
import {Reduce} from "../../../../../../_utils/display/reduce";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {

  loading: boolean;

  courseID: number;

  allModules: Module[];

  reduce = new Reduce();
  searchQuery: string; // FIXME: create search component and remove this

  isModuleDetailsModalOpen: boolean;
  moduleOpen: Module;
  isEnabled: boolean;
  saving: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  get API_ENDPOINT(): string {
    return ApiEndpointsService.API_ENDPOINT;
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
        this.allModules = modules;
        this.reduceList();
      },
      error => ErrorService.set(error)
      );
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

  toggleEnabled(module: Module): void {
    this.saving = true;
    const isEnabled = !module.enabled;

    this.api.setModuleEnabled(this.courseID, module.id, isEnabled)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(
        res => {
          module.enabled = !module.enabled;
          this.getModules(this.courseID);
          Module.reloadStyles(this.courseID);
        },
        error => ErrorService.set(error),
        () => {
          this.isModuleDetailsModalOpen = false;
          this.moduleOpen = null;
        }
      );
  }

}
