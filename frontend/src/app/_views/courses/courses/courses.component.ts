import { Component, OnInit } from '@angular/core';

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {ErrorService} from "../../../_services/error.service";

import {Course} from "../../../_domain/Course";
import {User} from "../../../_domain/User";
import {DownloadManager} from "../../../_utils/download-manager";
import {Ordering} from "../../../_utils/ordering";

import Pickr from "@simonwep/pickr";

import _ from 'lodash';

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

  allCourses: Course[];
  filteredCourses: Course[];
  usingMyCourses: boolean;

  searchQuery: string;

  filters = {
    admin: ['Active', 'Inactive', 'Visible', 'Invisible'],
    nonAdmin: []
  };
  filtersActive: string[];

  orderBy = {
    admin: ['Name', 'Short', '# Students', 'Year'],
    nonAdmin: ['Name', 'Year']
  };
  orderByActive: {orderBy: string, sort: number};
  DEFAULT_SORT = 1;

  exports = [
    { module: 'awards', name: 'Awards and Participations' },
    { module: 'modules', name: 'Modules' },
  ];
  exportOptions: { [id: number]: { users: boolean, awards: boolean, modules: boolean } } = {};

  importedFile: File;

  isCourseModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isIndividualExportModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;

  mode: 'add' | 'edit';
  newCourse: CourseData = {
    name: null,
    short: null,
    year: null,
    color: null,
    isActive: null,
    isVisible: null
  };
  courseToEdit: Course;
  courseToDelete: Course;
  courseToExport: Course;

  pickr: Pickr;

  constructor(
    private api: ApiHttpService
  ) { }

  async ngOnInit(): Promise<void> {
    this.getUserAndCourses();
    this.getYearsOptions();
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getUserAndCourses(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
        this.user = user;
        this.getCourses();
      },
        error => ErrorService.set(error));
  }

  getCourses(): void {
    this.api.getUserCourses()
      .subscribe(data => {
        this.allCourses = data.courses;
        this.filteredCourses = _.cloneDeep(data.courses); // deep copy
        this.usingMyCourses = !!data.myCourses;

        this.allCourses.forEach(course => {
          this.exportOptions[course.id] = { users: true, awards: true, modules: true };
        });

        if (this.usingMyCourses) {  // non-admin
          // active or non-active courses but must be visible
          this.allCourses.filter(course => course.isVisible);
          this.filteredCourses.filter(course => course.isVisible);
        }

        this.filtersActive = this.user.isAdmin ? _.cloneDeep(this.filters.admin) : _.cloneDeep(this.filters.nonAdmin);
        this.orderByActive = this.user.isAdmin ? { orderBy: this.orderBy.admin[0], sort: this.DEFAULT_SORT } : { orderBy: this.orderBy.nonAdmin[0], sort: this.DEFAULT_SORT };
        this.reduceList();

        this.loading = false;
      },
        error => ErrorService.set(error));
  }

  getYearsOptions(): void {
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


  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  onSearch(query: string): void {
    this.searchQuery = query;
    this.reduceList();
  }

  onFilterChanged(filter: {filter: string, state: boolean}): void {
    if (filter.state) {
      this.filtersActive.push(filter.filter);

    } else {
      const index = this.filtersActive.findIndex(el => el === filter.filter);
      this.filtersActive.splice(index, 1);
    }

    this.reduceList();
  }

  onOrderByChanged(order: {orderBy: string, sort: number}): void {
    this.orderByActive = order;
    this.orderList();
  }

  reduceList(): void {
    this.filteredCourses = [];

    this.allCourses.forEach(course => {
      if (this.isQueryTrueSearch(course, this.searchQuery) && this.isQueryTrueFilter(course))
        this.filteredCourses.push(course);
    });

    this.orderList();
  }

  orderList(): void {
    switch (this.orderByActive.orderBy) {
      case "Name":
        this.filteredCourses.sort((a, b) => Ordering.byString(a.name, b.name, this.orderByActive.sort))
        break;

      case "Short":
        this.filteredCourses.sort((a, b) => Ordering.byString(a.short, b.short, this.orderByActive.sort))
        break;

      case "# Students":
        this.filteredCourses.sort((a, b) => Ordering.byNumber(a.nrStudents, b.nrStudents, this.orderByActive.sort))
        break;

      case "Year":
        this.filteredCourses.sort((a, b) => Ordering.byString(a.year, b.year, this.orderByActive.sort))
        break;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  toggleActive(courseID: number) {
    this.loadingAction = true;

    const course = this.allCourses.find(course => course.id === courseID);
    course.isActive = !course.isActive;

    this.api.setCourseActive(course.id, course.isActive)
      .subscribe(
      res => this.loadingAction = false,
      error => ErrorService.set(error)
      );
  }

  toggleVisible(courseID: number) {
    this.loadingAction = true;

    const course = this.allCourses.find(course => course.id === courseID);
    course.isVisible = !course.isVisible;

    this.api.setCourseVisible(course.id, course.isVisible)
      .subscribe(
        res => this.loadingAction = false,
        error => ErrorService.set(error)
      );
  }

  createCourse(): void {
    this.loadingAction = true;

    this.api.createCourse(this.newCourse)
      .subscribe(
        res => {
          this.allCourses.push(res);
          this.exportOptions[res.id] = { users: true, awards: true, modules: true };
          this.reduceList();
        },
        error => ErrorService.set(error),
        () => {
          this.isCourseModalOpen = false;
          this.clearObject(this.newCourse);
          this.loadingAction = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New course created");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  duplicateCourse(course: Course) {
    this.loadingAction = true;

    this.newCourse = {
      id: course.id,
      name: course.name,
      short: course.short,
      year: course.year,
      color: course.color,
      isActive: course.isActive,
      isVisible: course.isVisible
    }

    this.api.duplicateCourse(this.newCourse)
      .subscribe(
        res => {
          this.allCourses.push(res);
          this.exportOptions[res.id] = { users: true, awards: true, modules: true };
          this.reduceList();
        },
        error => ErrorService.set(error),
        () => {
          this.isCourseModalOpen = false;
          this.clearObject(this.newCourse);
          this.loadingAction = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New course created from " + course.name);
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  editCourse(): void {
    this.loadingAction = true;
    this.newCourse['id'] = this.courseToEdit.id;

    this.api.editCourse(this.newCourse)
      .subscribe(
        res => this.getCourses(),
        error => ErrorService.set(error),
        () => {
          this.isCourseModalOpen = false;
          this.clearObject(this.newCourse);
          this.loadingAction = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("Course: "+ this.courseToEdit.name + " edited");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  deleteCourse(course: Course): void {
    this.loadingAction = true;
    this.api.deleteCourse(course.id)
      .subscribe(
        res => {
          const index = this.allCourses.findIndex(el => el.id === course.id);
          this.allCourses.splice(index, 1);
          this.exportOptions[course.id] = null;
          this.reduceList();
        },
        error => ErrorService.set(error),
        () => {
          this.isDeleteVerificationModalOpen = false;
          this.loadingAction = false
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("Course: " + course.name + " deleted");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  importCourses(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedCourses = reader.result;
      this.api.importCourses({file: importedCourses, replace})
        .subscribe(
          nCourses => {
            this.getCourses();
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nCourses + " Course" + (nCourses > 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          },
          error => ErrorService.set(error),
          () => {
            this.isImportModalOpen = false;
            this.loadingAction = false;
          }
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportCourse(course: Course): void {
    this.saving = true;

    this.api.exportCourses(course?.id || null, this.exportOptions[course?.id] || null)
      .subscribe(
        zip => DownloadManager.downloadAsZip(zip, ApiEndpointsService.API_ENDPOINT),
        error => ErrorService.set(error),
        () => {
          this.isIndividualExportModalOpen = false;
          this.saving = false
        }
      )
  }

  exportAllCourses(): void {
    this.exportCourse(null);
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

  isQueryTrueSearch(course: Course, query: string): boolean {
    return !query ||
      (course.name && !!this.parseForSearching(course.name).find(a => a.includes(query.toLowerCase()))) ||
      (course.short && !!this.parseForSearching(course.short).find(a => a.includes(query.toLowerCase()))) ||
      (course.year && !!this.parseForSearching(course.year).find(a => a.includes(query.toLowerCase())));
  }

  isQueryTrueFilter(course: Course): boolean {
    if (!this.user.isAdmin && this.filters.nonAdmin.length === 0)
      return true;

    for (const filter of this.filtersActive) {
      if ((filter === 'Active' && course.isActive) || (filter === 'Inactive' && !course.isActive) ||
        (filter === 'Visible' && course.isVisible) || (filter === 'Invisible' && !course.isVisible))
        return true;
    }
    return false;
  }

  isReadyToSubmit() {
    let isValid = function (text) {
      return (text != "" && text != undefined)
    }

    // Validate inputs
    return isValid(this.newCourse.name) && isValid(this.newCourse.short) && isValid(this.newCourse.year);
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

  initEditCourse(course: Course): void {
    this.newCourse = {
      name: course.name,
      short: course.short,
      year: course.year,
      color: course.color,
      isActive: course.isActive,
      isVisible: course.isVisible
    };
    this.courseToEdit = course;
  }

  getActiveCourses(isActive: boolean): Course[] {
    return this.filteredCourses.filter(course => course.isActive === isActive);
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
  isActive: boolean,
  isVisible: boolean
}

export interface ImportCoursesData {
  file: string | ArrayBuffer,
  replace: boolean
}
