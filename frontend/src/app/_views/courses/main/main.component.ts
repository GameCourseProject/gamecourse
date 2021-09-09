import { Component, OnInit } from '@angular/core';
import {Course} from "../../../_domain/Course";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {throwError} from "rxjs";
import {QueryStringParameters} from "../../../_utils/query-string-parameters";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;
  loadingAction = false;

  userCourses: Course[];

  constructor(private api: ApiHttpService) { }

  ngOnInit(): void {
    this.getUserCourses();
  }

  getUserCourses(): void {
    this.api.getAllUserCourses(1) // FIXME: get actual ID
      .subscribe(
        res => {
          this.userCourses = res;

          let iterations = this.userCourses.length;
          for (const course of this.userCourses) {
            this.api.getAllCourseStudents(course.id).subscribe(
              res => {
                course.numberOfStudents = res.length;
                if (!--iterations) this.loading = false;
              },
              error => throwError(error)
            )
          }

        },
        error => throwError(error)
      )
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

    this.api.updateCourse(course.id, {'is_active': course.isActive}).subscribe(
      res => this.loadingAction = false,
      error => throwError(error)
    );
  }

}
