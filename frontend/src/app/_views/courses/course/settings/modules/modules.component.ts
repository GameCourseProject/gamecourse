import { Component, OnInit } from '@angular/core';
import {Module} from "../../../../../_domain/Module";
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ErrorService} from "../../../../../_services/error.service";

import _ from 'lodash';

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
  needsToBeSaved: {[key: string]: boolean} = {};
  saving: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

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
      .subscribe(modules => {
        this.allModules = modules;
        this.filteredModules = _.cloneDeep(modules); // deep copy
        this.allModules.forEach(module => this.needsToBeSaved[module.id] = false);
      },
      error => ErrorService.set(error),
        () => this.loading = false);
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

    module.enabled = !module.enabled;

    this.api.setModuleEnabled(this.courseID, module.id, module.enabled)
      .subscribe(
        res => {
          this.getModules(this.courseID);
        },
        error => ErrorService.set(error),
        () => {
          this.saving = false;
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
