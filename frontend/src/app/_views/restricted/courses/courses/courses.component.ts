import { Component, OnInit } from '@angular/core';

import {ApiHttpService} from "../../../../_services/api/api-http.service";

import {Course} from "../../../../_domain/courses/course";
import {User} from "../../../../_domain/users/user";
import {Reduce} from "../../../../_utils/lists/reduce";
import {Order, Sort} from "../../../../_utils/lists/order";

import Pickr from "@simonwep/pickr";

import {exists} from "../../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";

@Component({
  selector: 'app-main',
  templateUrl: './courses.component.html',
  styleUrls: ['./courses.component.scss']
})
export class CoursesComponent implements OnInit {

  loading = true;
  loadingAction = false;
  yearsOptions: string[] = [];

  user: User;
  courses: Course[];

  reduce = new Reduce();
  order = new Order();

  filters = {
    admin: ['Active', 'Inactive', 'Visible', 'Invisible'],
    nonAdmin: []
  };

  orderBy = {
    admin: ['Name', 'Short', '# Students', 'Year'],
    nonAdmin: ['Name', 'Year']
  };

  importedFile: File;

  isCourseModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;

  mode: 'add' | 'edit';
  newCourse: CourseData = {
    name: null,
    short: null,
    year: null,
    color: null,
    startDate: null,
    endDate: null,
    isActive: null,
    isVisible: null
  };
  courseToEdit: Course;
  courseToDelete: Course;

  pickr: Pickr;

  constructor(
    private api: ApiHttpService
  ) { }

  async ngOnInit(): Promise<void> {
    await this.getLoggedUser();
    await this.getCourses();
    this.loading = false;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
  }

  async getCourses(): Promise<void> {
    if (this.user.isAdmin) this.courses = await this.api.getCourses().toPromise();
    else this.courses = await this.api.getUserCourses(this.user.id, null, null).toPromise();

    this.order.active = this.user.isAdmin ? { orderBy: this.orderBy.admin[0], sort: Sort.ASCENDING } : { orderBy: this.orderBy.nonAdmin[0], sort: Sort.ASCENDING };
    this.reduceList(undefined, this.user.isAdmin ? [...this.filters.admin] : [...this.filters.nonAdmin]);
  }


  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string, filters?: string[]): void {
    this.reduce.searchAndFilter(this.courses, query, filters);
    this.orderList();
  }

  orderList(): void {
    switch (this.order.active.orderBy) {
      case "Name":
        this.reduce.items.sort((a, b) => Order.byString(a.name, b.name, this.order.active.sort))
        break;

      case "Short":
        this.reduce.items.sort((a, b) => Order.byString(a.short, b.short, this.order.active.sort))
        break;

      case "# Students":
        this.reduce.items.sort((a, b) => Order.byNumber(a.nrStudents, b.nrStudents, this.order.active.sort))
        break;

      case "Year":
        this.reduce.items.sort((a, b) => Order.byString(a.year, b.year, this.order.active.sort))
        break;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  createCourse(): void {
    this.loadingAction = true;

    if (this.newCourse.startDate) this.newCourse.startDate += ' 00:00:00';
    else this.newCourse.startDate = null;

    if (this.newCourse.endDate) this.newCourse.endDate += ' 23:59:59';
    else this.newCourse.endDate = null;

    if (this.newCourse.short?.isEmpty()) this.newCourse.short = null;

    this.api.createCourse(this.newCourse)
      .pipe( finalize(() => {
        this.isCourseModalOpen = false;
        this.clearObject(this.newCourse);
        this.loadingAction = false;
      }) )
      .subscribe(
        newCourse => {
          this.courses.push(newCourse);
          this.reduceList();

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New course created");
          successBox.show().delay(3000).fadeOut();
        })
  }

  duplicateCourse(courseID: number) {
    this.loadingAction = true;

    this.api.duplicateCourse(courseID)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(
        newCourse => {
          this.courses.push(newCourse);
          this.reduceList();

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New course created");
          successBox.show().delay(3000).fadeOut();
        })
  }

  editCourse(): void {
    this.loadingAction = true;
    this.newCourse['id'] = this.courseToEdit.id;

    if (this.newCourse.startDate) this.newCourse.startDate += ' 00:00:00';
    else this.newCourse.startDate = null;

    if (this.newCourse.endDate) this.newCourse.endDate += ' 23:59:59';
    else this.newCourse.endDate = null;

    if (this.newCourse.short?.isEmpty()) this.newCourse.short = null;

    this.api.editCourse(this.newCourse)
      .pipe( finalize(() => {
        this.isCourseModalOpen = false;
        this.clearObject(this.newCourse);
        this.loadingAction = false;
      }) )
      .subscribe(
        courseEdited => {
          const index = this.courses.findIndex(course => course.id === courseEdited.id);
          this.courses.removeAtIndex(index);

          this.courses.push(courseEdited);
          this.reduceList();

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("Course: '"+ this.courseToEdit.name + "' edited");
          successBox.show().delay(3000).fadeOut();
        })
  }

  deleteCourse(course: Course): void {
    this.loadingAction = true;
    this.api.deleteCourse(course.id)
      .pipe( finalize(() => {
        this.isDeleteVerificationModalOpen = false;
        this.loadingAction = false
      }) )
      .subscribe(
        () => {
          const index = this.courses.findIndex(el => el.id === course.id);
          this.courses.removeAtIndex(index);
          this.reduceList();

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("Course '" + course.name + "' deleted");
          successBox.show().delay(3000).fadeOut();
        })
  }

  toggleActive(courseID: number) {
    this.loadingAction = true;

    const course = this.courses.find(course => course.id === courseID);
    course.isActive = !course.isActive;

    this.api.setCourseActive(course.id, course.isActive)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(res => {});
  }

  toggleVisible(courseID: number) {
    this.loadingAction = true;

    const course = this.courses.find(course => course.id === courseID);
    course.isVisible = !course.isVisible;

    this.api.setCourseVisible(course.id, course.isVisible)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(res => {});
  }

  importCourses(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const file = reader.result;
      this.api.importCourses({file, replace})
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.loadingAction = false;
        }) )
        .subscribe(
          async nrCourses => {
            await this.getCourses();
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nrCourses + " Course" + (nrCourses != 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          })
    }
    reader.readAsText(this.importedFile);
  }

  exportCourses(): void {
    // this.saving = true;
    //
    // this.api.exportCourses(course?.id || null, this.exportOptions[course?.id] || null)
    //   .pipe( finalize(() => {
    //     this.isExportModalOpen = false;
    //     this.saving = false
    //   }) )
    //   .subscribe(
    //     zip => DownloadManager.downloadAsZip(zip, ApiEndpointsService.API_ENDPOINT),
    //   )
  }

  exportCourse(course: Course): void
  {
     // TODO
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToSubmit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    // Validate inputs
    return isValid(this.newCourse.name) && isValid(this.newCourse.year);
  }

  initColorPicker(): void {
    setTimeout(() => {
      // Simple example, see optional options for more configuration.
      this.pickr = Pickr.create({
        el: '#new_pickr',
        useAsButton: true,
        default: this.mode === 'add' ? 'white' : this.newCourse.color,
        theme: 'monolith', // or 'classic', or 'nano',
        components: {
          hue: true,
          interaction: {
            input: true,
            save: true
          }
        }
      }).on('init', pickr => {
        this.newCourse.color = pickr.getSelectedColor().toHEXA().toString(0);
      }).on('save', color => {
        this.newCourse.color = color.toHEXA().toString(0);
        this.pickr.hide();
      }).on('change', color => {
        this.newCourse.color = color.toHEXA().toString(0);
      });
    }, 0);
  }

  initYearOptions(): void {
    if (this.yearsOptions.length != 0) return;

    const now = new Date();
    const currentYear = now.getFullYear();

    const YEARS_BEFORE = 1;
    const YEARS_AFTER = 5;

    let i = -YEARS_BEFORE;
    while (currentYear + i < currentYear + YEARS_AFTER) {
      this.yearsOptions.push((currentYear + i) + '-' + (currentYear + i + 1));
      i++;
    }
  }

  initEditCourse(course: Course): void {
    this.newCourse = {
      name: course.name,
      short: course.short,
      year: course.year,
      color: course.color,
      startDate: course.startDate?.format('YYYY-MM-DD') || null,
      endDate: course.endDate?.format('YYYY-MM-DD') || null,
      isActive: course.isActive,
      isVisible: course.isVisible
    };
    this.courseToEdit = course;
  }

  getNonAdminCourses(isActive: boolean): Course[] {
    return this.reduce.items.filter(course => course.isActive === isActive);
  }

  getRedirectLink(course: Course): string {
    const link = '/courses/' + course.id;
    const pageID = course.landingPage; // FIXME: landing page per user role
    if (pageID) return link + '/pages/' + pageID;
    else return link;
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
  }

  isWhite(color: string): boolean {
    if (!color) return false;
    return ['white', '#ffffff', '#fff'].includes(color.toLowerCase());
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

}

export interface CourseData {
  id?: number,
  name: string,
  short: string,
  year: string,
  color: string,
  startDate: string,
  endDate: string,
  isActive: boolean,
  isVisible: boolean
}

export interface ImportCoursesData {
  file: string | ArrayBuffer,
  replace: boolean
}
