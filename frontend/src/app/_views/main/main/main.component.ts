import { Component, OnInit } from '@angular/core';

import {Course} from "../../../_domain/courses/course";

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;

  activeCourses: Course[] = [];

  constructor(
    private apiHttpService: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.loading = false;
    this.getUserActiveCourses();
  }

  getUserActiveCourses(): void {
    this.apiHttpService.getUserActiveCourses()
      .subscribe(courses => {
        this.activeCourses = courses;
        this.loading = false;
      },
        error => ErrorService.set(error));
  }

}
