import { Component, OnInit } from '@angular/core';
import {ActivatedRoute} from "@angular/router";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../../_services/api/api-endpoints.service";
import {ErrorService} from "../../../../../_services/error.service";

import {Module} from "../../../../../_domain/Module";

import _ from 'lodash';
import {finalize} from "rxjs/operators";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {

  loading: boolean;

  courseID: number;

  allModules: Module[];
  filteredModules: Module[];

  searchQuery: string;

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
    this.route.params.subscribe(params => {
      this.courseID = params.id;
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
        this.filteredModules = _.cloneDeep(modules); // deep copy
      },
      error => ErrorService.set(error)
      );
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Search ------------------ ***/
  /*** --------------------------------------------- ***/

  onSearch(): void {
    this.reduceList();
  }

  reduceList(): void {
    this.filteredModules = [];

    this.allModules.forEach(module => {
      if (this.isQueryTrueSearch(module, this.searchQuery))
        this.filteredModules.push(module);
    });
  }

  toggleEnabled(module: Module): void {
    this.saving = true;
    const isEnabled = !module.enabled;

    this.api.setModuleEnabled(this.courseID, module.id, isEnabled)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(
        res => {
          module.enabled = !module.enabled;
          this.getModules(this.courseID);
        },
        error => ErrorService.set(error),
        () => {
          this.isModuleDetailsModalOpen = false;
          this.moduleOpen = null;
        }
      );
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  parseForSearching(query: string): string[] {
    let res: string[];
    let temp: string;
    query = query.swapPTChars();

    res = query.toLowerCase().split(' ');

    temp = query.replace(' ', '').toLowerCase();
    if (!res.includes(temp)) res.push(temp);

    temp = query.toLowerCase();
    if (!res.includes(temp)) res.push(temp);
    return res;
  }

  isQueryTrueSearch(module: Module, query: string): boolean {
    return !query ||
      (module.name && !!this.parseForSearching(module.name).find(a => a.includes(query.toLowerCase()))) ||
      (module.id && !!this.parseForSearching(module.id).find(a => a.includes(query.toLowerCase())));
  }

}
