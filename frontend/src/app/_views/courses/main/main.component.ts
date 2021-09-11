import { Component, OnInit } from '@angular/core';
import {throwError} from "rxjs";

import {ApiHttpService} from "../../../_services/api/api-http.service";

import {Course} from "../../../_domain/Course";
import {User} from "../../../_domain/User";

import {orderByNumber, orderByString} from "../../../_utils/order-by";
import Pickr from "@simonwep/pickr";
import _ from 'lodash';
import {removePTCharacters} from "../../../_utils/remove-pt-chars";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

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

  isNewCourseModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isIndividualExportModalOpen: boolean;
  isExportAllModalOpen: boolean;
  saving: boolean;

  newCourse = {
    name: null,
    short: null,
    year: null,
    color: null,
    isActive: null,
    isVisible: null
  };
  courseToDelete: Course;
  courseToExport: Course;

  pickr: Pickr;

  constructor(
    private api: ApiHttpService
  ) { }

  async ngOnInit(): Promise<void> {
    this.getUser();
    this.getYearsOptions();
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getUser(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
        this.user = user;
        this.getCourses();
      });
  }

  getCourses(): void {
    this.api.getUserCourses()
      .subscribe(data => {
        this.allCourses = data.courses;
        this.filteredCourses = _.cloneDeep(data.courses); // deep copy
        this.usingMyCourses = !!data.myCourses;

        this.allCourses.forEach(course => {
          course.nameUrl = course.name.replace(/\W+/g, '');
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
      });
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
      if (this.isQueryTrueSearch(course) && this.isQueryTrueFilter(course))
        this.filteredCourses.push(course);
    });

    this.orderList();
  }

  orderList(): void {
    switch (this.orderByActive.orderBy) {
      case "Name":
        this.filteredCourses.sort((a, b) => orderByString(a.name, b.name, this.orderByActive.sort))
        break;

      case "Short":
        this.filteredCourses.sort((a, b) => orderByString(a.short, b.short, this.orderByActive.sort))
        break;

      case "# Students":
        this.filteredCourses.sort((a, b) => orderByNumber(a.nrStudents, b.nrStudents, this.orderByActive.sort))
        break;

      case "Year":
        this.filteredCourses.sort((a, b) => orderByString(a.year, b.year, this.orderByActive.sort))
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
      error => throwError(error)
      );
  }

  toggleVisible(courseID: number) {
    this.loadingAction = true;

    const course = this.allCourses.find(course => course.id === courseID);
    course.isVisible = !course.isVisible;

    this.api.setCourseVisible(course.id, course.isVisible)
      .subscribe(
        res => this.loadingAction = false,
        error => throwError(error)
      );
  }

  createCourse(): void {
    this.loadingAction = true;

    const course = new Course(
      null,
      this.newCourse.name,
      this.newCourse.short,
      this.newCourse.color,
      this.newCourse.year,
      null,
      null,
      !!this.newCourse.isActive,
      !!this.newCourse.isVisible,
      null,
      null
    );

    this.api.createCourse(course)
      .subscribe(
        res => {
          this.allCourses.push(res);
          this.exportOptions[res.id] = { users: true, awards: true, modules: true };
          this.reduceList();
        },
        error => throwError(error),
        () => {
          this.isNewCourseModalOpen = false;
          for (const key of Object.keys(this.newCourse)) {
            this.newCourse[key] = null;
          }
          this.loadingAction = false
        }
      )
  }

  duplicateCourse(course: Course): void {
    this.loadingAction = true;

    this.api.duplicateCourse(course) // FIXME: bug mkdir()
      .subscribe(
        res => {
          this.allCourses.push(res);
          this.exportOptions[res.id] = { users: true, awards: true, modules: true };
          this.reduceList();
        },
        error => throwError(error),
        () => this.loadingAction = false
      )
  }

  editCourse(): void {
    // TODO
  }

  deleteCourse(courseID: number): void {
    this.loadingAction = true;
    this.api.deleteCourse(courseID)
      .subscribe(
        res => {
          const index = this.allCourses.findIndex(el => el.id === courseID);
          this.allCourses.splice(index, 1);
          this.exportOptions[courseID] = null;
          this.reduceList();
        },
        error => throwError(error),
        () => {
          this.isDeleteVerificationModalOpen = false;
          this.loadingAction = false
        }
      )
  }

  importCourse(): void {
    // TODO
  }

  exportCourse(course: Course, options): void {
    this.saving = true;
    this.api.exportCourses(course.id, options) // FIXME: not working
      .subscribe(
        zip => {
          console.log(zip)
        },
        error => throwError(error),
        () => {
          this.isIndividualExportModalOpen = false;
          this.saving = false
        }
      )
  }

  exportAllCourses(): void {
    // TODO
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  parseForSearching(query: string): string[] {
    let res: string[];
    let temp: string;
    query = removePTCharacters(query);

    res = query.toLowerCase().split(' ');

    temp = query.replace(' ', '').toLowerCase();
    if (!res.includes(temp)) res.push(temp);

    temp = query.toLowerCase();
    if (!res.includes(temp)) res.push(temp);
    return res;
  }

  isQueryTrueSearch(course: Course): boolean {
    return !this.searchQuery ||
      (course.name && !!this.parseForSearching(course.name).find(a => a.includes(this.searchQuery.toLowerCase()))) ||
      (course.short && !!this.parseForSearching(course.short).find(a => a.includes(this.searchQuery.toLowerCase()))) ||
      (course.year && !!this.parseForSearching(course.year).find(a => a.includes(this.searchQuery.toLowerCase())));
  }

  isQueryTrueFilter(course: Course): boolean {
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
        default: 'white',
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

}
