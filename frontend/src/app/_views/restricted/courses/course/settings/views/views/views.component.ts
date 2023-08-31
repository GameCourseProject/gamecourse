import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";

import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {UpdateService} from "../../../../../../../_services/update.service";

import {Page} from "../../../../../../../_domain/views/pages/page";
import {Reduce} from "../../../../../../../_utils/lists/reduce";
import {Course} from "../../../../../../../_domain/courses/course";
import {Action} from 'src/app/_domain/modules/config/Action';
import {NgForm} from "@angular/forms";
import {ModalService} from 'src/app/_services/modal.service';
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";

import * as _ from 'lodash';
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";

@Component({
  selector: 'app-views',
  templateUrl: './views.component.html',
  styleUrls: ['./views.component.scss']
})
export class ViewsComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    table: false
  };

  course: Course;                 // Specific course in which pages are being manipulated
  pages: Page[] = [];             // Pages of course
  arrangingPages: Page[];         // Copy of pages for arranging modal

  actions: { icon: string, description: string, type: 'management' | 'configuration' }[] =
    [{ icon: 'feather-type', description: 'Rename', type: 'management' },
     { icon: 'tabler-eye', description: 'Preview', type: 'management' },
     { icon: 'jam-padlock-f', description: 'Make public/private', type: 'configuration' },
     { icon: 'feather-sliders', description: 'Configure visibility', type: 'configuration' },
     { icon: 'jam-upload', description: 'Export', type: 'configuration' },
     { icon: 'jam-files-f', description: 'Duplicate', type: 'configuration' },
     { icon: 'jam-trash-f', description: 'Delete', type: 'configuration' }];

  mode: 'arrange' | 'delete' | 'remove page' | 'visibility' | 'make public-private';

  pageToManage: PageManageData;

  // SEARCH AND FILTER
  reduce = new Reduce();
  pagesToShow: Page[] = [];
  filteredPages: Page[] = [];                   // Pages search

  @ViewChild('p', {static: false}) p: NgForm;   // Page form

  // Import action
  importData: {file: File, replace: boolean} = { file: null, replace: true };
  @ViewChild('fImport', {static: false}) fImport: NgForm;


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

  }

  /*** --------------------------------------------- ***/
  /*** -------------- Top Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareModal(modal: string){
    modal = modal.toLowerCase();
    if (modal === 'arrange pages'){
      this.arrangingPages = _.cloneDeep(this.pagesToShow);
      this.mode = 'arrange';
      ModalService.openModal('arrange-pages');

    } else if (modal === 'import page(s) from PC'){
      // TODO

    } else if (modal === 'import page from GameCourse'){
      // TODO

    } else if (modal === 'export all pages'){
      // TODO
    }

  }

  async arrangePages(){
    this.loading.action = true;
    this.pagesToShow = this.arrangingPages;

    // Save new positions
    for (let i = 0; i < this.pagesToShow.length; i++){
      if (this.pagesToShow[i].position === i) continue; // if order hasn't changed, skip the edition step (less accesses to DB)
      this.pagesToShow[i].position = i;
      let page = this.initPageToManage(this.pagesToShow[i]);
      await this.api.editPage(this.course.id, page).toPromise();
    }

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Pages\' order saved successfully');
    ModalService.closeModal('arrange-pages');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  doAction(action:string, page: Page){
    action = action.toLowerCase();

    if (action === 'rename'){


    } else if (action === 'preview'){
      // TODO

    } else if (action === 'make public/private'){
      this.pageToManage = this.initPageToManage(page);
      this.mode = 'make public-private';
      ModalService.openModal('make-public-private-page')

    } else if (action === 'configure visibility'){
      this.pageToManage = this.initPageToManage(page);
      this.mode = 'visibility';
      ModalService.openModal('configure-visibility');

    } else if (action === 'export'){


    } else if (action === 'duplicate'){


    } else if (action === 'delete'){
      this.pageToManage = this.initPageToManage(page);
      this.mode = 'delete';
      ModalService.openModal('delete-page');
    }

  }

  async deletePage(){
    this.loading.action = true;

    const index = this.pagesToShow.findIndex(page => page.id === this.pageToManage.id);
    await this.api.deletePage(this.course.id, this.pageToManage.id).toPromise();
    this.pagesToShow.splice(index, 1);

    this.pages = this.pagesToShow;

    AlertService.showAlert(AlertType.SUCCESS, 'Page \'' + this.pageToManage.name + '\' deleted successfully.')
    ModalService.closeModal('delete-page');
    this.loading.action = false;
  }

  async makePublicAndPrivate(){
    this.loading.action = true;

    const page = _.cloneDeep(this.pageToManage);
    page.isPublic = !page.isPublic;
    console.log(page);
    const newPage = await this.api.editPage(this.course.id, page).toPromise();
    const index = this.pagesToShow.findIndex(myPage => myPage.id === page.id);
    console.log(index);
    this.pagesToShow.splice(index, 1, newPage);

    this.pages = this.pagesToShow;

    console.log("pages: ", this.pages);
    console.log("pagesToShow: ", this.pagesToShow);

    AlertService.showAlert(AlertType.SUCCESS, 'Page \'' + page.name + '\' ' +
      (page.isPublic ? 'published' : 'make private'));
    ModalService.closeModal('make-public-private-page');

    this.loading.action = false;
  }

  async configureVisibility(){
    // TODO
  }

  resetChanges(){
    if (this.mode === 'arrange') this.arrangingPages = null;
    if (this.mode === 'delete') this.pageToManage = null;

    this.mode = null;
    console.log("resetting changes");
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
  }*/

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  drop(event: CdkDragDrop<string[]>){
    moveItemInArray(this.arrangingPages, event.previousIndex, event.currentIndex);
  }

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

  /*** --------------------------------------------- ***/
  /*** ----------------- Manage Data --------------- ***/
  /*** --------------------------------------------- ***/

  initPageToManage(page?: Page): PageManageData {
    const pageData: PageManageData = {
      course: page?.course ?? this.course.id,
      name: page?.name ?? null,
      isVisible: page?.isVisible ?? false,
      viewRoot: page?.viewId ?? null,
      creationTimestamp: page?.creationTimestamp ?? null,
      updateTimestamp: page?.updateTimestamp ?? null,
      visibleFrom: page?.visibleFrom ?? null,
      visibleUntil: page?.visibleUntil ?? null,
      position: page?.position ?? null,
      isPublic: page?.isPublic ?? false
    };
    if (page) pageData.id = page.id;
    return pageData;
  }

}

export interface PageManageData {
  id?: number,
  course?: number,
  name?: string,
  isVisible?: boolean,
  viewRoot?: number,
  creationTimestamp?: Date,
  updateTimestamp?: Date,
  visibleFrom?: Date,
  visibleUntil?: Date,
  position?: number,
  isPublic?: boolean
}
