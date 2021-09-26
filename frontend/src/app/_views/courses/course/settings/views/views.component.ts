import { Component, OnInit } from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../_services/error.service";

import {Page} from "../../../../../_domain/Page";
import {Template} from "../../../../../_domain/Template";
import {RoleType} from "../../../../../_domain/RoleType";

import _ from 'lodash';

@Component({
  selector: 'app-views',
  templateUrl: './views.component.html',
  styleUrls: ['./views.component.scss']
})
export class ViewsComponent implements OnInit {

  loading: boolean;

  courseID: number;

  allPages: Page[];
  filteredPages: Page[];

  allViewTemplates: Template[];
  filteredViewTemplates: Template[];

  allGlobalTemplates: Template[];
  filteredGlobalTemplates: Template[];

  types: RoleType[];

  searchQuery: string;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.params.subscribe(params => {
      this.courseID = params.id;
      this.getViewsInfo();
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getViewsInfo(): void {
    this.loading = true;
    this.api.getViewsList(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(res => {
        this.allPages = res.pages;
        this.filteredPages = _.cloneDeep(res.pages); // deep copy

        this.allViewTemplates = res.templates;
        this.filteredViewTemplates = _.cloneDeep(res.templates); // deep copy

        this.allGlobalTemplates = res.globals;
        this.filteredGlobalTemplates = _.cloneDeep(res.globals); // deep copy

        this.types = res.types;

      }, error => ErrorService.set(error));
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Search ------------------ ***/
  /*** --------------------------------------------- ***/

  onSearch(): void {
    this.reduceList();
  }

  reduceList(): void {
    this.filteredPages = [];
    this.filteredViewTemplates = [];
    this.filteredGlobalTemplates = [];

    this.allPages.forEach(page => {
      if (this.isQueryTrueSearch(page, this.searchQuery))
        this.filteredPages.push(page);
    });

    this.allViewTemplates.forEach(template => {
      if (this.isQueryTrueSearch(template, this.searchQuery))
        this.filteredViewTemplates.push(template);
    });

    this.allGlobalTemplates.forEach(template => {
      if (this.isQueryTrueSearch(template, this.searchQuery))
        this.filteredGlobalTemplates.push(template);
    });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  useGlobal(template: Template): void {
    // TODO: update from GameCourse v1
    ErrorService.set('This action still needs to be update to the current version. Action: useGlobal()')
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

  isQueryTrueSearch(item: Page | Template, query: string): boolean {
    return !query ||
      (item.name && !!this.parseForSearching(item.name).find(a => a.includes(query.toLowerCase())));
  }

}
