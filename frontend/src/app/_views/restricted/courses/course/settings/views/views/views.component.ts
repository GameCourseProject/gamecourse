import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";

import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {UpdateService} from "../../../../../../../_services/update.service";

import {Page} from "../../../../../../../_domain/views/pages/page";
import {Reduce} from "../../../../../../../_utils/lists/reduce";
import {Course} from "../../../../../../../_domain/courses/course";
import {Action} from 'src/app/_domain/modules/config/Action';
import {NgForm} from "@angular/forms";
import {TableDataType} from "../../../../../../../_components/tables/table-data/table-data.component";

import * as moment from "moment";
import * as _ from "lodash";
import {View} from 'src/app/_domain/views/view';
import {Template} from "../../../../../../../_domain/views/templates/template";

@Component({
  selector: 'app-views',
  templateUrl: './views.component.html'
})
export class ViewsComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    table: false
  };

  course: Course;                       // Specific course in which pages are being manipulated

  pages: Page[] = [];           // Pages of course
  views: Template[];                // Views inside course


  // SEARCH AND FILTER
  reduce = new Reduce();
  pagesToShow: Page[] = [];
  filteredPages: Page[] = [];           // Pages search

  @ViewChild('p', {static: false}) p: NgForm;   // Page form

  // Import action
  importData: {file: File, replace: boolean} = { file: null, replace: true };
  @ViewChild('fImport', {static: false}) fImport: NgForm;

  // TABLES
  pagesTable: {
    headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
    data: {type: TableDataType, content: any}[][],
    options: any,
    showTable: boolean,
    loading: boolean
  } = {
    headers: [
      {label: 'Position', align: 'middle'},
      {label: 'Name', align: 'middle'},
      {label: 'Visible', align: 'middle'},
      {label: 'Actions'}
    ],
    data: null,
    options: {
      order: [ 0 , 'asc'],
      columnDefs: [
        { type: 'natural', targets: [0,1] },
        { searchable: false, targets: [0,2] },
        { orderable: false, targets: [2] }
      ]
    },
    showTable: false,
    loading: false
  }

  viewsTable: {
    headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
    data: {type: TableDataType, content: any}[][],
    options: any,
    showTable: boolean,
    loading: boolean
  } = {
    headers: [
      {label: 'Name', align:'middle'},
      {label: 'Global', align: 'middle'},
      {label: 'Actions'}
    ],
    data: null,
    options: {
      order: [ 0 , 'asc'],
      columnDefs: [
        { type: 'natural', targets: [0] },
        { searchable: false, targets: [1] },
        { orderable: false, targets: [1] }
      ]
    },
    showTable: false,
    loading: false
  }

  /*allPages: Page[];
  allViewTemplates: Template[];
  allGlobalTemplates: Template[];

  reduceForPages = new Reduce();
  reduceForViewTemplates = new Reduce();
  reduceForGlobalTemplates = new Reduce();
  searchQuery: string; // FIXME: create search component and remove this

  types: RoleType[];

  isPageModalOpen: boolean;
  isTemplateModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;

  pageSelected: Page;
  templateSelected: Template;

  importedFile: File;

  mode: 'add' | 'edit';*/

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    private updateManager: UpdateService
  ) { }

  get Action(): typeof Action {
    return Action;
  }

  /*get Page(): typeof Page {
    return Page;
  }

  get Template(): typeof Template {
    return Template;
  }*/


  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getPages(courseID);
      await this.getViews(courseID);

      this.loading.page = false;
    });
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getPages(courseID: number) {
    this.pages = await this.api.getCoursePages(courseID).toPromise();
    this.pages.sort((a, b) => a.position - b.position);

    this.filteredPages = this.pages;
    this.pagesToShow = this.pages;

    await this.buildPagesTable();
  }

  async getViews(courseID: number) {
    this.views = await this.api.getViews(courseID).toPromise();

    await this.buildViewsTable();

    /*this.api.getViewsList(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(res => {
        this.allPages = res.pages.sort((a, b) => a.name.localeCompare(b.name));
        this.allViewTemplates = res.templates.sort((a, b) => a.name.localeCompare(b.name));
        this.allGlobalTemplates = res.globals.sort((a, b) => a.name.localeCompare(b.name));

        this.types = res.types;

        this.reduceList();

      });*/
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Tables ------------------- ***/
  /*** --------------------------------------------- ***/

  async buildPagesTable(): Promise<void> {
    this.pagesTable.loading = true;

    this.pagesTable.showTable = false;
    setTimeout(() => this.pagesTable.showTable = true, 0);

    const data : { type: TableDataType; content: any }[][] = [];

    this.pages.forEach(page => {
      data.push([
        {type: TableDataType.NUMBER, content: { value: page.position }},
        {type: TableDataType.TEXT, content: { text: page.name }},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: page.isVisible}},
        {type: TableDataType.ACTIONS, content: {actions: [
              Action.EDIT,
              { action: Action.DUPLICATE },
              { action: Action.MOVE_UP, disabled: page.position === 0 },
              { action: Action.MOVE_DOWN, disabled: page.position === this.pages.length },
              Action.REMOVE,
              Action.EXPORT
            ]}}
      ]);
    });

    this.pagesTable.data = _.cloneDeep(data);
    console.log(this.pagesTable);
    this.pagesTable.loading = false;

  }

  async buildViewsTable(): Promise<void> {
    this.viewsTable.loading = true;

    this.viewsTable.showTable = false;
    setTimeout(() => this.viewsTable.showTable = true, 0);

    const data : { type: TableDataType; content: any }[][] = [];

    this.views.forEach(view => {
      data.push([
        {type: TableDataType.TEXT, content: { text: view.name }},
        {type: TableDataType.TOGGLE, content: { toggleId: 'isGlobal', toggleValue: view.isGlobal }},
        {type: TableDataType.ACTIONS, content: {actions: [
              Action.VIEW,
              Action.DUPLICATE,
              Action.EDIT,
              Action.EXPORT,
              Action.REMOVE
            ]}}
      ]);
    });

    this.viewsTable.data = _.cloneDeep(data);

    this.viewsTable.loading = false;
    console.log(this.viewsTable);

  }


  async doActionOnTable(table: string, action:string, row: number, col: number): Promise<void> {
    // TODO
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareModal(action: string){
    // TODO
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/


  filterPages(searchQuery?: string) {
    if (searchQuery) {
      let pages: Page[] = [];

      for (let i = 0;  i < this.filteredPages.length; i++){
        if (((this.filteredPages[i].name).toLowerCase()).includes(searchQuery.toLowerCase())) {
          pages.push(this.filteredPages[i]);
        }
      }
      this.pagesToShow = pages;
    }

    else this.pagesToShow = this.pages;
  }

  /*reduceList(query?: string): void {
    this.reduceForPages.search(this.allPages, query);
    this.reduceForViewTemplates.search(this.allViewTemplates, query);
    this.reduceForGlobalTemplates.search(this.allGlobalTemplates, query);
  }*/


  /*** --------------------------------------------- ***/
  /*** ------------------- Pages ------------------- ***/
  /*** --------------------------------------------- ***/

  /*savePage(page: Page): void {
    this.saving = true;

    if (this.mode === 'add') {
      this.api.createPage(this.courseID, page)
        .pipe( finalize(() => {
          this.saving = false;
          this.isPageModalOpen = false;
          this.pageSelected = null;
        }) )
        .subscribe(res => {
          this.getViewsInfo();
          this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
        })

    } else if (this.mode === 'edit') {
      this.api.editPage(this.courseID, page)
        .pipe( finalize(() => {
          this.saving = false;
          this.isPageModalOpen = false;
          this.pageSelected = null;
        }) )
        .subscribe(res => {
          this.getViewsInfo();
          this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
        })
    }
  }

  editPage(page: Page): void {
    this.pageSelected = page;
    this.mode = 'edit';
    this.isPageModalOpen = true;
  }

  deletePage(): void {
    this.saving = true;
    this.api.deletePage(this.courseID, this.pageSelected)
      .pipe( finalize(() => {
        this.saving = false;
        this.clear();
        this.isDeleteVerificationModalOpen = false;
      }) )
      .subscribe(
        res => {
          this.getViewsInfo();
          this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
        })
  }


  /*** --------------------------------------------- ***/
  /*** ----------------- Templates ----------------- ***/
  /*** --------------------------------------------- ***/

  /*saveTemplate(template: Template): void {
    this.saving = true;

    if (this.mode === 'add') {
      this.api.createTemplate(this.courseID, template)
        .pipe( finalize(() => {
          this.saving = false;
          this.isTemplateModalOpen = false;
          this.templateSelected = null;
        }) )
        .subscribe(res => {
          this.getViewsInfo();
        })

    } else if (this.mode === 'edit') {
      this.api.editTemplateBasicInfo(this.courseID, template)
        .pipe( finalize(() => {
          this.saving = false;
          this.isTemplateModalOpen = false;
          this.templateSelected = null;
        }) )
        .subscribe(res => {
          this.getViewsInfo();
        })
    }
  }

  editTemplate(template: Template): void {
    this.templateSelected = template;
    this.mode = 'edit';
    this.isTemplateModalOpen = true;
  }

  deleteTemplate(): void {
    this.saving = true;

    // Check if there's a page binding to this template
    let canDelete = true;
    this.allPages.forEach(page => {
      if (page.viewId === this.templateSelected.viewId) {
        canDelete = false;
        ErrorService.set('Page \'' + page.name + '\' uses this template. Please delete this page first and try again.');
      }
    });

    if (canDelete) {
      this.api.deleteTemplate(this.courseID, this.templateSelected)
        .pipe( finalize(() => {
          this.saving = false;
          this.clear();
          this.isDeleteVerificationModalOpen = false;
        }) )
        .subscribe(res => this.getViewsInfo())

    } else {
      this.saving = false;
      this.clear();
      this.isDeleteVerificationModalOpen = false;
    }
  }

  globalize(template: Template): void {
    this.saving = true;
    this.api.globalizeTemplate(this.courseID, template)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(res => this.getViewsInfo());
  }

  useGlobal(template: Template): void {
    // TODO: update from GameCourse v1
    ErrorService.set('Error: This action still needs to be updated to the current version. (building-blocks.component.ts::useGlobal(template))')
  }

  importTemplate(): void {
    this.saving = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedTemplate = reader.result;
      this.api.importTemplate(this.courseID, importedTemplate)
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.saving = false;
        }) )
        .subscribe(() => this.getViewsInfo())
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportTemplate(template: Template): void {
    this.saving = true;
    this.api.exportTemplate(this.courseID, template.id)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(res => {
        DownloadManager.downloadAsText('Template - ' + template.name, res)
      })
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/


  calculateDate(date: Date): string{
    return date.toLocaleDateString("en-GB").split(", ")[0];
  }

  /*onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
  }

  isReadyToSubmit(type: 'page' | 'template') {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    if (type === 'page') {
      return isValid(this.pageSelected.name) && isValid(this.pageSelected.viewId);

    } else {
      return isValid(this.templateSelected.name) && isValid(this.templateSelected.roleType);
    }
  }

  getEmptyPage(): Page {
    return new Page(null, null, null, null, null, null, null, null);
  }

  getEmptyTemplate(): Template {
    return new Template(null, null, null, null, null, null);
  }

  clear(): void {
    this.pageSelected = null;
    this.templateSelected = null;
  }*/

}
