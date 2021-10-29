import { Component, OnInit } from '@angular/core';

import {Course} from "../../../_domain/courses/course";

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";
import {finalize} from "rxjs/operators";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;

  activeCourses: Course[] = [];

  constructor(
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.loading = false;
    this.getUserActiveCourses();
  }

  getUserActiveCourses(): void {
    this.api.getUserActiveCourses()
      .pipe( finalize(() => this.loading = false) )
      .subscribe(courses => this.activeCourses = courses,
        error => ErrorService.set(error));
  }

}
