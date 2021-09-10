import { Component, OnInit } from '@angular/core';
import {Course} from "../../../_domain/Course";
import {ApiHttpService} from "../../../_services/api/api-http.service";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;
  loadingAction = false;

  userCourses: Course[];

  constructor(
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.getUserCourses();
  }

  getUserCourses(): void {
    this.api.getUserCourses()
      .subscribe(courses => {
        this.userCourses = courses;
        console.log(courses)
        this.loading = false;
      });
  }

  onSearch(query: string) {
    console.log(query)
    // TODO
  }

  onFilterChange(filter: {filter: string, state: boolean}) {
    console.log(filter)
    // TODO
  }

  onOrderChange(order: {orderBy: string, sort: number}) {
    console.log(order)
    // TODO
  }

  toggleActive(courseID: number) {
    this.loadingAction = true;

    const course = this.userCourses.find(el => el.id === courseID);
    course.isActive = !course.isActive;

    if (!course.isActive) {

    }

    // this.api.updateCourse(course.id, {'is_active': course.isActive}).subscribe(
    //   res => this.loadingAction = false,
    //   error => throwError(error)
    // );
  }

}
