import { Component, OnInit } from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../_services/error.service";
import {PagesUpdateService} from "../../../../../_services/pages-update.service";

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

  isViewModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  saving: boolean;

  newView: {view: {name: string, viewId: number, isEnabled: boolean, roleTypeId?: string}, type: 'page' | 'template'} = {
    view: {
      name: null,
      viewId: null,
      isEnabled: null,
      roleTypeId: null
    },
    type: null
  }
  viewToEdit: Page | Template;
  viewToDelete: {view: Page | Template, type: 'page' | 'template'};

  mode: 'add' | 'edit';

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private pagesUpdate: PagesUpdateService
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

  saveView(): void {
    this.saving = true;

    if (this.mode === 'add') {
      if (this.newView.type === 'page') {
        this.api.createPage(this.courseID, this.newView.view)
          .pipe( finalize(() => {
            this.saving = false;
            this.isViewModalOpen = false;
            this.clearObject(this.newView.view);
            this.newView.type = null;
          }) )
          .subscribe(res => {
            this.getViewsInfo();
            this.pagesUpdate.triggerUpdate();
          }, error => ErrorService.set(error))

      } else if(this.newView.type === 'template') {
        this.api.createTemplate(this.courseID, this.newView.view)
          .pipe( finalize(() => {
            this.saving = false;
            this.isViewModalOpen = false;
            this.clearObject(this.newView.view);
            this.newView.type = null;
          }) )
          .subscribe(res => {
            this.getViewsInfo();
          }, error => ErrorService.set(error))
      }

    } else if (this.mode === 'edit') {
      if (this.newView.type === 'page') {
        this.viewToEdit = this.viewToEdit as Page;
        this.viewToEdit.name = this.newView.view.name;
        this.viewToEdit.viewId = this.newView.view.viewId;
        this.viewToEdit.isEnabled = this.newView.view.isEnabled;

        this.api.editPage(this.courseID, this.viewToEdit)
          .pipe( finalize(() => {
            this.saving = false;
            this.isViewModalOpen = false;
            this.clearObject(this.newView.view);
            this.newView.type = null;
          }) )
          .subscribe(res => {
              this.getViewsInfo();
              this.pagesUpdate.triggerUpdate();
            }, error => ErrorService.set(error))

      } else if (this.newView.type === 'template') {
        this.viewToEdit = this.viewToEdit as Template;
        this.viewToEdit.name = this.newView.view.name;
        this.viewToEdit.viewId = this.newView.view.viewId;
        this.viewToEdit.roleTypeId = this.newView.view.roleTypeId;

        this.api.editTemplate(this.courseID, this.viewToEdit)
          .pipe( finalize(() => {
            this.saving = false;
            this.isViewModalOpen = false;
            this.clearObject(this.newView.view);
            this.newView.type = null;
          }) )
          .subscribe(res => {
            this.getViewsInfo();
          }, error => ErrorService.set(error))
      }
    }
  }

  editView(view: Page | Template, type: 'page' | 'template'): void {
    this.viewToEdit = view;
    this.newView.type = type;

    if (type === 'page') {
      view = view as Page;
      this.newView.view.name = view.name;
      this.newView.view.viewId = view.viewId;
      this.newView.view.isEnabled = view.isEnabled;

    } else if (type === 'template') {
      view = view as Template;
      this.newView.view.name = view.name;
      this.newView.view.roleTypeId = view.roleTypeId;
    }

    this.mode = 'edit';
    this.isViewModalOpen = true;
  }

  deleteView(): void {
    this.saving = true;
    if (this.viewToDelete.type === 'page') {
      this.api.deletePage(this.courseID, this.viewToDelete.view as Page)
        .pipe( finalize(() => {
          this.saving = false;
          this.isDeleteVerificationModalOpen = false;
          this.viewToDelete = null;
        }) )
        .subscribe(
          res => {
            this.getViewsInfo();
            this.pagesUpdate.triggerUpdate();
          },
          error => ErrorService.set(error)
        )

    } else if (this.viewToDelete.type === 'template') {
      this.api.deleteTemplate(this.courseID, this.viewToDelete.view as Template)
        .pipe( finalize(() => {
          this.saving = false;
          this.isDeleteVerificationModalOpen = false;
          this.viewToDelete = null;
        }) )
        .subscribe(
          res => {
            this.getViewsInfo();
          },
          error => ErrorService.set(error)
        )
    }
  }

  globalize(template: Template): void {
    this.saving = true;
    this.api.globalizeTemplate(this.courseID, template)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(res => {
        this.getViewsInfo();
      }, error => ErrorService.set(error));
  }

  useGlobal(template: Template): void {
    // TODO: update from GameCourse v1
    ErrorService.set('This action still needs to be updated to the current version. Action: useGlobal()')
  }

  exportTemplate(template: Template): void {
    // TODO: update from GameCourse v1
    ErrorService.set('This action still needs to be updated to the current version. Action: exportTemplate()')
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

  isReadyToSubmit() {
    let isValid = function (text) {
      return (text != "" && text != undefined)
    }

    if (this.newView.type === 'page') {
      return isValid(this.newView.view.name) && isValid(this.newView.view.viewId);

    } else if (this.newView.type === 'template') {
      return isValid(this.newView.view.name) && isValid(this.newView.view.roleTypeId);
    }

    return false;
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

}
