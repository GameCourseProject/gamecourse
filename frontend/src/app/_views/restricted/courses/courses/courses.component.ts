import {Component, OnInit, ViewChild} from '@angular/core';
import {NgForm} from "@angular/forms";
import {Router} from "@angular/router";

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {AlertService, AlertType} from "../../../../_services/alert.service";
import {ModalService} from "../../../../_services/modal.service";

import {Course} from "../../../../_domain/courses/course";
import {User} from "../../../../_domain/users/user";
import {TableDataType} from "../../../../_components/tables/table-data/table-data.component";
import {Action} from "../../../../_domain/modules/config/Action";
import {clearEmptyValues} from "../../../../_utils/misc/misc";


@Component({
  selector: 'app-main',
  templateUrl: './courses.component.html'
})
export class CoursesComponent implements OnInit {

  loading = {
    page: true,
    table: true,
    action: false
  }

  user: User;
  courses: Course[];

  mode: 'create' | 'edit';
  courseToManage: CourseManageData = this.initCourseToManage();
  courseToDelete: Course;
  @ViewChild('f', { static: false }) f: NgForm;

  yearOptions: {value: string, text: string}[] = this.initYearOptions();

  importedFile: File;

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  async ngOnInit(): Promise<void> {
    await this.getLoggedUser();
    await this.getCourses();
    this.loading.page = false;

    if (this.user.isAdmin) this.buildTable();
  }

  get Action(): typeof Action {
    return Action;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
  }

  async getCourses(): Promise<void> {
    if (this.user.isAdmin) this.courses = await this.api.getCourses().toPromise();
    else this.courses = await this.api.getUserCourses(this.user.id, null, true).toPromise();
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Name (sorting)', align: 'left'},
    {label: 'Course', align: 'left'},
    {label: '# Students', align: 'middle'},
    {label: 'Year', align: 'middle'},
    {label: 'Start (timestamp sorting)', align: 'middle'},
    {label: 'Start', align: 'middle'},
    {label: 'End (timestamp sorting)', align: 'middle'},
    {label: 'End', align: 'middle'},
    {label: 'Active', align: 'middle'},
    {label: 'Visible', align: 'middle'},
    {label: 'Actions'}
  ];
  data: {type: TableDataType, content: any}[][];
  tableOptions = {
    order: [[ 3, 'asc' ], [ 0, 'asc' ]], // default order
    columnDefs: [
      { type: 'natural', targets: [0, 1, 2, 3, 4, 5, 6, 7] },
      { orderData: 0,   targets: 1 },
      { orderData: 4,   targets: 5 },
      { orderData: 6,   targets: 7 },
      { orderable: false, targets: [8, 9, 10] }
    ]
  }

  buildTable(): void {
    this.loading.table = true;

    const table: { type: TableDataType, content: any }[][] = [];
    this.courses.forEach(course => {
      table.push([
        {type: TableDataType.TEXT, content: {text: course.name}},
        {type: TableDataType.CUSTOM, content: {html: '<div class="!text-left !text-start !justify-start">' +
              '<div class="flex items-center space-x-3">' +
                '<div class="avatar">' +
                  '<div class="mask mask-circle w-9 h-9 !flex !items-center !justify-center bg-base-content bg-opacity-30" style="background-color: ' + course.color + '">' +
                    '<span class="text-base-100">' + course.name[0] + '</span>' +
                  '</div>' +
                '</div>' +
                '<div class="prose text-sm">' +
                  '<h4>' + course.name + '</h4>' +
                  (course.short ? '<span class="opacity-60">' + course.short + '</span>' : '') +
                '</div>' +
              '</div>' +
            '</div>'}},
        {type: TableDataType.NUMBER, content: {value: course.nrStudents}},
        {type: TableDataType.TEXT, content: {text: course.year}},
        {type: TableDataType.NUMBER, content: {value: course.startDate?.unix()}},
        {type: TableDataType.DATE, content: {date: course.startDate}},
        {type: TableDataType.NUMBER, content: {value: course.endDate?.unix()}},
        {type: TableDataType.DATE, content: {date: course.endDate}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: course.isActive}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isVisible', toggleValue: course.isVisible}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.VIEW, Action.DUPLICATE, Action.EDIT, Action.DELETE, Action.EXPORT]}},
      ]);
    });

    this.data = table;
    this.loading.table = false;
  }

  doActionOnTable(action: string, row: number, col: number, value?: any): void {
    const courseToActOn = this.courses[row];

    if (action === 'value changed') {
      if (col === 8) this.toggleActive(courseToActOn);
      else if (col === 9) this.toggleVisible(courseToActOn);

    } else if (action === Action.VIEW) {
      const redirectLink = this.getRedirectLink(courseToActOn);
      this.router.navigate([redirectLink]);

    } else if (action === Action.DUPLICATE) {
      this.duplicateCourse(courseToActOn);

    } else if (action === Action.EDIT) {
      this.mode = 'edit';
      this.courseToManage = this.initCourseToManage(courseToActOn);
      ModalService.openModal('manage');

    } else if (action === Action.DELETE) {
      this.courseToDelete = courseToActOn;
      ModalService.openModal('delete-verification');

    } else if (action === Action.EXPORT) {
      this.exportCourses([courseToActOn]);
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  doAction(action: string) {
    if (action === Action.IMPORT) {
      // TODO

    } else if (action === Action.EXPORT) {
      // TODO

    } else if (action === 'Create course') {
      this.mode = 'create';
      this.courseToManage = this.initCourseToManage();
      ModalService.openModal('manage');
    }
  }

  async createCourse(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      const newCourse = await this.api.createCourse(clearEmptyValues(this.courseToManage)).toPromise();
      this.courses.push(newCourse);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('manage');
      this.resetManage();
      AlertService.showAlert(AlertType.SUCCESS, 'New course created: ' + newCourse.name);

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async duplicateCourse(course: Course) {
    this.loading.action = true;

    const newCourse = await this.api.duplicateCourse(course.id).toPromise();
    this.courses.push(newCourse);
    this.buildTable();

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'New course created: ' + newCourse.name);
  }

  async editCourse(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      const courseEdited = await this.api.editCourse(clearEmptyValues(this.courseToManage)).toPromise();
      const index = this.courses.findIndex(course => course.id === courseEdited.id);
      this.courses.removeAtIndex(index);
      this.courses.push(courseEdited);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('manage');
      this.resetManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Course \'' + courseEdited.name + '\' edited');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async deleteCourse(course: Course): Promise<void> {
    this.loading.action = true;

    await this.api.deleteCourse(course.id).toPromise();
    const index = this.courses.findIndex(el => el.id === course.id);
    this.courses.removeAtIndex(index);
    this.buildTable();

    this.loading.action = false;
    ModalService.closeModal('delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'Course \'' + course.name + '\' deleted');
  }

  async toggleActive(course: Course) {
    this.loading.action = true;

    course.isActive = !course.isActive;
    await this.api.setCourseActive(course.id, course.isActive).toPromise();

    this.loading.action = false;
  }

  async toggleVisible(course: Course) {
    this.loading.action = true;

    course.isVisible = !course.isVisible;
    await this.api.setCourseVisible(course.id, course.isVisible).toPromise();

    this.loading.action = false;
  }

  importCourses(replace: boolean): void {
    // TODO
    // this.loading.action = true;
    //
    // const reader = new FileReader();
    // reader.onload = (e) => {
    //   const file = reader.result;
    //   this.api.importCourses({file, replace})
    //     .pipe( finalize(() => {
    //       // this.isImportModalOpen = false;
    //       this.loading.action = false;
    //     }) )
    //     .subscribe(
    //       async nrCourses => {
    //         await this.getCourses();
    //         const successBox = $('#action_completed');
    //         successBox.empty();
    //         successBox.append(nrCourses + " Course" + (nrCourses != 1 ? 's' : '') + " Imported");
    //         successBox.show().delay(3000).fadeOut();
    //       })
    // }
    // reader.readAsText(this.importedFile);
  }

  async exportCourses(courses: Course[]): Promise<void> {
    // TODO
    // this.loading.action = true;
    //
    // await this.api.exportCourses(courses, this.exportOptions[course?.id] || null)
    //   .pipe( finalize(() => {
    //     this.isExportModalOpen = false;
    //     this.saving = false
    //   }) )
    //   .subscribe(
    //     zip => DownloadManager.downloadAsZip(zip, ApiEndpointsService.API_ENDPOINT),
    //   )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initCourseToManage(course?: Course): CourseManageData {
    const courseData: CourseManageData = {
      name: course?.name ?? null,
      short: course?.short ?? null,
      color: course?.color ?? null,
      year: course?.year ?? null,
      startDate: course?.startDate?.format('YYYY-MM-DD') ?? null,
      endDate: course?.endDate?.format('YYYY-MM-DD') ?? null
    };
    if (course) courseData.id = course.id;
    return courseData;
  }

  initYearOptions(): {value: string, text: string}[] {
    const years = [];
    const now = new Date();
    const currentYear = now.getFullYear();

    const YEARS_BEFORE = 1;
    const YEARS_AFTER = 5;

    let i = -YEARS_BEFORE;
    while (currentYear + i < currentYear + YEARS_AFTER) {
      const year = (currentYear + i) + '-' + (currentYear + i + 1);
      years.push({value: year, text: year});
      i++;
    }

    return years;
  }

  getRedirectLink(course: Course): string {
    const link = '/courses/' + course.id;
    if (this.user.isAdmin) return link; // admins go to main page

    const pageID = course.landingPage; // FIXME: landing page per user role
    if (pageID) return link + '/pages/' + pageID;
    else return link;
  }

  filterCourses(active: boolean = null, visible: boolean = null): Course[] {
    return this.courses.filter(course => {
      return (active !== null ? course.isActive === active : true) &&
      (visible !== null ? course.isVisible === visible : true);
      }
    );
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
  }

  resetManage() {
    this.mode = null;
    this.courseToManage = this.initCourseToManage();
    this.f.resetForm()
  }

}

export interface CourseManageData {
  id?: number,
  name: string,
  short: string,
  color: string,
  year: string,
  startDate: string,
  endDate: string
}

export interface ImportCoursesData {
  file: string | ArrayBuffer,
  replace: boolean
}
