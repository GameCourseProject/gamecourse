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
import {ThemingService} from "../../../../../../../_services/theming/theming.service";
import {Moment} from "moment";

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
  pages: Page[] = [];             // All pages to show (course + public from other courses)

  coursePages: Page[];            // Course pages
  publicPages: Page[];            // Pages from other courses that are public
  arrangingPages: Page[];         // Copy of coursePages for arranging modal

  pageToManage: PageManageData;
  publicPagesCourses: {[publicPageId: number]: string} = {};  // Names from public pages' courses
  mode: 'rename' | 'arrange' | 'delete' | 'remove page' | 'visibility' | 'make public-private' | 'duplicate';

  // Actions for pages
  actions: { icon: string, description: string, type: 'management' | 'configuration',
    color?: 'primary' | 'primary-content' | 'secondary' | 'secondary-content' |
      'accent' | 'accent-content' | 'neutral' | 'neutral-content' | 'info' | 'info-content' |
      'success' | 'success-content' | 'warning' | 'warning-content' | 'error' | 'error-content'  }[] =
    [{ icon: 'feather-type', description: 'Rename', type: 'management' },
     { icon: 'tabler-eye', description: 'Preview', type: 'management' },
     { icon: 'jam-padlock-f', description: 'Make public/private', type: 'configuration' },
     { icon: 'feather-sliders', description: 'Configure visibility', type: 'configuration' },
     { icon: 'jam-upload', description: 'Export', type: 'configuration' },
     { icon: 'jam-files-f', description: 'Duplicate', type: 'configuration' },
     { icon: 'jam-trash-f', description: 'Delete', type: 'configuration', color: 'error' }];

  visibilityCheckbox: boolean;                    // For 'configure-visibility' modal
  isHovered: {[pageId: number]: boolean} = {};    // Changes padlock icon when hovering
  pageName: string;                               // Auxiliar for page name ('rename' modal)

  // DUPLICATE OPTIONS
  duplicateOptions: {name: string, char: string}[] = [
    {name: "By reference", char: "ref"},
    {name: "By value", char: "value"}
  ];
  optionSelected: string = null;

  // SEARCH AND FILTER
  reduce = new Reduce();
  pagesToShow: Page[] = [];
  filteredPages: Page[] = [];                   // Pages search

  @ViewChild('f', {static: false}) f: NgForm;   // Page form

  // Import action
  importData: {file: File, replace: boolean} = { file: null, replace: true };
  @ViewChild('fImport', {static: false}) fImport: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    private updateManager: UpdateService,
    private themeService: ThemingService,
  ) { }

  get Action(): typeof Action {
    return Action;
  }


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
    this.coursePages = await this.api.getCoursePages(courseID).toPromise();
    this.publicPages = await this.api.getPublicPages(courseID, true).toPromise();

    for (let i = 0; i < this.publicPages.length; i++){
      let course = await this.api.getCourseById(this.publicPages[i].course).toPromise();
      this.publicPagesCourses[this.publicPages[i].id] =  course.name;
    }

    this.pages = this.coursePages.concat(this.publicPages);
    this.filteredPages = this.pages;
    this.pagesToShow = this.pages;


    for (let i = 0; i < this.pages.length; i++){
      this.isHovered[this.pages[i].id] = false;
    }

  }

  /*** --------------------------------------------- ***/
  /*** -------------- Top Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareModal(modal: string){
    modal = modal.toLowerCase();
    if (modal === 'arrange pages') {
      this.arrangingPages = _.cloneDeep(this.coursePages);
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

    // Save new positions
    for (let i = 0; i < this.arrangingPages.length; i++){
      // if order hasn't changed, skip the edition step (less accesses to DB)
      if (this.arrangingPages[i].position === i) continue;
      this.arrangingPages[i].position = i;
      let page = this.initPageToManage(this.arrangingPages[i]);
      await this.api.editPage(this.course.id, page).toPromise();
    }

    this.pages = this.arrangingPages.concat(this.publicPages);
    this.pagesToShow = this.pages;

    AlertService.showAlert(AlertType.SUCCESS, 'Pages\' order saved successfully');
    ModalService.closeModal('arrange-pages');

    this.loading.action = false;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  doAction(action:string, page: Page) {
    action = action.toLowerCase();

    if (action === 'rename') {
      this.mode = 'rename';
      this.pageToManage = this.initPageToManage(page);
      this.pageName = _.cloneDeep(this.pageToManage.name);
      ModalService.openModal('rename');

    } else if (action === 'preview') {
      // TODO

    } else if (action === 'make public/private') {
      this.pageToManage = this.initPageToManage(page);
      this.mode = 'make public-private';
      ModalService.openModal('make-public-private-page')

    } else if (action === 'configure visibility') {
      this.pageToManage = this.initPageToManage(page);
      this.mode = 'visibility';

      if (this.pageToManage.isVisible) ModalService.openModal('configure-not-visibility');
      else {
        ModalService.openModal('configure-visibility');

        if (this.pageToManage.visibleFrom === null) this.pageToManage.visibleFrom = '';
        if (this.pageToManage.visibleUntil === null) this.pageToManage.visibleUntil = '';

      }

    } else if (action === 'export') {
      // TODO

    } else if (action === 'duplicate') {
      this.pageToManage = this.initPageToManage(page);
      this.mode = 'duplicate';
      ModalService.openModal('duplicate');

    } else if (action === 'delete') {
      this.pageToManage = this.initPageToManage(page);
      this.mode = 'delete';
      ModalService.openModal('delete-page');
    }

  }

  async deletePage(){
    this.loading.action = true;

    await this.api.deletePage(this.course.id, this.pageToManage.id).toPromise();

    let index;
    if (this.pageToManage.course === this.course.id) {
      index = this.coursePages.findIndex(page => page.id === this.pageToManage.id);
      this.coursePages.splice(index, 1);

    } else {
      index = this.publicPages.findIndex(page => page.id === this.pageToManage.id);
      this.publicPages.splice(index, 1);
    }

    this.pages = this.coursePages.concat(this.publicPages);
    this.pagesToShow = this.pages;

    AlertService.showAlert(AlertType.SUCCESS, 'Page \'' + this.pageToManage.name + '\' deleted successfully.')
    ModalService.closeModal('delete-page');
    this.loading.action = false;
  }

  // FIXME : Refactor makePublicAndPrivate() + configureVisibility() + renamePage() + copyPage() --> Big common parts
  async makePublicAndPrivate(){
    this.loading.action = true;

    const page = _.cloneDeep(this.pageToManage);
    page.isPublic = !page.isPublic;
    const newPage = await this.api.editPage(this.course.id, page).toPromise();

    let index;
    if (this.pageToManage.course === this.course.id) {
      index = this.coursePages.findIndex(page => page.id === this.pageToManage.id);
      this.coursePages.splice(index, 1, newPage);

    } else {
      index = this.publicPages.findIndex(page => page.id === this.pageToManage.id);
      this.publicPages.splice(index, 1);
    }

    this.pages = this.coursePages.concat(this.publicPages);
    this.pagesToShow = this.pages;

    AlertService.showAlert(AlertType.SUCCESS, 'Page \'' + page.name + '\' ' +
      (page.isPublic ? 'published' : 'make private'));
    ModalService.closeModal('make-public-private-page');

    this.loading.action = false;
  }

  async configureVisibility(){
    this.loading.action = true;

    const page = _.cloneDeep(this.pageToManage);
    page.isVisible = !page.isVisible;
    if (page.visibleFrom === '') page.visibleFrom = null;
    if (page.visibleUntil === '') page.visibleUntil = null;

    // make invisible
    if (!page.isVisible) {
      page.visibleFrom = null;
      page.visibleUntil = null;
    }

    let origin: 'course' | 'public';
    if (page.course === this.course.id) origin = 'course';
    else origin = 'public';

    const newPage = await this.api.editPage(this.course.id, page).toPromise();
    let index = origin === 'course' ?
        this.coursePages.findIndex(myPage => myPage.id === page.id) :
        this.publicPages.findIndex(myPage => myPage.id === page.id);

    origin === 'course' ? this.coursePages.splice(index, 1, newPage) : this.publicPages.splice(index, 1, newPage);

    this.pages = this.coursePages.concat(this.publicPages);
    this.pagesToShow = this.pages;

    AlertService.showAlert(AlertType.SUCCESS, 'Page \'' + page.name + '\' made ' +
      (page.isVisible ? 'visible' : 'not visible'));
    ModalService.closeModal(page.isVisible ? 'configure-visibility' : 'configure-not-visibility');

    this.loading.action = false;
  }

  async renamePage(){
    this.loading.action = true;

    let origin: 'course' | 'public';
    if (this.pageToManage.course === this.course.id) origin = 'course';
    else origin = 'public';

    const newPage = await this.api.editPage(this.course.id, this.pageToManage).toPromise();
    let index = origin === 'course' ?
      this.coursePages.findIndex(myPage => myPage.id === this.pageToManage.id) :
      this.publicPages.findIndex(myPage => myPage.id === this.pageToManage.id);

    origin === 'course' ? this.coursePages.splice(index, 1, newPage) : this.publicPages.splice(index, 1, newPage);

    this.pages = this.coursePages.concat(this.publicPages);
    this.pagesToShow = this.pages;

    AlertService.showAlert(AlertType.SUCCESS, 'Page \'' + this.pageToManage.name + '\' successfully renamed.');
    ModalService.closeModal('rename');

    this.loading.action = false;
  }

  async duplicatePage(){
    this.loading.action = true;

    const page = _.cloneDeep(this.pageToManage);
    let origin = page.course === this.course.id ? 'course' : 'public';
    let newPage = await this.api.copyPage(this.course.id, page.id, this.optionSelected).toPromise();

    origin === 'course' ? this.coursePages.push(newPage) : this.publicPages.push(newPage);

    this.pages = this.coursePages.concat(this.publicPages);
    this.pagesToShow = this.pages;

    AlertService.showAlert(AlertType.SUCCESS, 'Page \'' + page.name + '\' duplicated successfully.');
    ModalService.closeModal('duplicate');

    this.loading.action = false;

  }

  // FIXME: Refactor
  resetChanges(){
    if (this.mode === 'arrange') this.arrangingPages = null;
    else if (this.mode === 'delete') this.pageToManage = null;
    else if (this.mode === 'visibility'){
      this.pageToManage = null;
      this.visibilityCheckbox = null;
    }
    else if (this.mode === 'rename') {
      this.pageName = null;
      this.pageToManage = null;
    } else if (this.mode === 'duplicate'){
      this.pageToManage = null;
      this.optionSelected = null;

    }

    this.mode = null;
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

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  drop(event: CdkDragDrop<string[]>){
    moveItemInArray(this.arrangingPages, event.previousIndex, event.currentIndex);
  }

  calculateDate(date: Moment): string{
    return date.format('DD/MM/YYYY');
  }

  getTheme(): string{
    return this.themeService.getTheme();
  }

  /*** --------------------------------------------- ***/
  /*** ----------------- Manage Data --------------- ***/
  /*** --------------------------------------------- ***/

  initPageToManage(page?: Page): PageManageData {
    const pageData: PageManageData = {
      course: page?.course ?? this.course.id,
      name: page?.name ?? null,
      isVisible: page?.isVisible ?? false,
      viewRoot: page?.viewId ?? null,
      creationTimestamp: page?.creationTimestamp ? page.creationTimestamp.format('YYYY-MM-DD') : null,
      updateTimestamp: page?.updateTimestamp ? page.updateTimestamp.format('YYYY-MM-DD') : null,
      visibleFrom: page?.visibleFrom ? page.visibleFrom.format('YYYY-MM-DD') : null,
      visibleUntil: page?.visibleUntil ? page.visibleUntil.format('YYYY-MM-DD') : null,
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
  creationTimestamp?: string,
  updateTimestamp?: string,
  visibleFrom?: string,
  visibleUntil?: string,
  position?: number,
  isPublic?: boolean
}
