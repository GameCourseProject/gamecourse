import { Component, OnInit } from '@angular/core';
import {Course} from "../../../_domain/Course";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {User} from "../../../_domain/User";
import {orderByNumber, orderByString} from "../../../_utils/order-by";
import {throwError} from "rxjs";
import Pickr from "@simonwep/pickr";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;
  loadingAction = false;

  user: User;

  courses: Course[];
  filteredCourses: Course[];
  usingMyCourses: boolean;

  filters = {
    admin: ['Active', 'Inactive', 'Visible', 'Invisible'],
    nonAdmin: []
  };

  orderBy = {
    admin: ['Name', 'Short', '# Students', 'Year'],
    nonAdmin: ['Name', 'Year']
  }

  exports = [
    { module: 'awards', name: 'Awards and Participations' },
    { module: 'modules', name: 'Modules' },
  ];
  exportOptions = [];

  isNewCourseModalOpen: boolean;
  newCourse = {
    name: null,
    short: null,
    year: null,
    color: null,
    isActive: false,
    isVisible: false
  };
  saving: boolean;

  yearsOptions: string[] = [];

  pickr: Pickr;

  constructor(
    private api: ApiHttpService
  ) { }

  async ngOnInit(): Promise<void> {
    this.getUser();
    this.getCourses();
    this.getYearsOptions();
  }

  getUser(): void {
    this.api.getLoggedUser()
      .subscribe(user => this.user = user);
  }

  getCourses(): void {
    this.api.getUserCourses()
      .subscribe(data => {
        this.courses = data.courses;
        this.filteredCourses = data.courses.slice(); // using slice so it is a copy by value and not reference
        this.usingMyCourses = !!data.myCourses;

        this.courses.forEach(course => course.nameUrl = course.name.replace(/\W+/g, ''))

        if (this.usingMyCourses) {  // non-admin
          // active or non-active courses but must be visible
          this.courses.filter(course => course.isVisible);
          this.filteredCourses.filter(course => course.isVisible);
        }

        this.orderList({orderBy: 'Name', sort: 1});

        this.loading = false;
      });
  }

  searchList(query: string): void {
    console.log(query)
    // TODO
  }

  filterList(filter: {filter: string, state: boolean}): void {
    console.log(filter)
    // TODO

  }

  orderList(order: {orderBy: string, sort: number}): void {
    switch (order.orderBy) {
      case "Name":
        this.filteredCourses.sort((a, b) => orderByString(a.name, b.name, order.sort))
        break;

      case "Short":
        this.filteredCourses.sort((a, b) => orderByString(a.short, b.short, order.sort))
        break;

      case "# Students":
        this.filteredCourses.sort((a, b) => orderByNumber(a.nrStudents, b.nrStudents, order.sort))
        break;

      case "Year":
        this.filteredCourses.sort((a, b) => orderByString(a.year, b.year, order.sort))
        break;
    }
  }

  toggleActive(courseID: number) {
    this.loadingAction = true;

    const course = this.courses.find(course => course.id === courseID);
    course.isActive = !course.isActive;

    this.api.setCourseActive(course.id, course.isActive)
      .subscribe(
      res => this.loadingAction = false,
      error => throwError(error)
      );
  }

  toggleVisible(courseID: number) {
    this.loadingAction = true;

    const course = this.courses.find(course => course.id === courseID);
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
      this.newCourse.isActive,
      this.newCourse.isVisible,
      null,
      null
    );

    this.courses.push(course);
    this.filteredCourses.push(course); // FIXME: remove after filter
    // TODO: filter

    this.api.createCourse(course)
      .subscribe(
        done => {
          this.isNewCourseModalOpen = false;
          this.newCourse = {
            name: null,
            short: null,
            year: null,
            color: null,
            isActive: false,
            isVisible: false
          };
          this.loadingAction = false
        },
        error => throwError(error)
      )
  }

  isReadyToSubmit() {
    let isValid = function (text) {
      return (text != "" && text != undefined)
    }

    // Validate inputs
    return isValid(this.newCourse.name) && isValid(this.newCourse.short) && isValid(this.newCourse.year);
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

  initColorPicker(): void {
    setTimeout(() => {
      // Simple example, see optional options for more configuration.
      this.pickr = Pickr.create({
        el: '#new_pickr',
        useAsButton: true,
        default: '#ffffff',
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
