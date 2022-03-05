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
  redirectPages: {[courseID: string]: number}; // courseID -> pageId

  constructor(
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.getUserActiveCourses();
  }

  getUserActiveCourses(): void {
    this.api.getUserActiveCourses()
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => {
          this.activeCourses = res.courses;
          this.redirectPages = res.landingPages;
        },
        error => ErrorService.set(error));
  }

  getRedirectLink(courseID: number): string {
    const link = '/courses/' + courseID;
    const pageID = this.redirectPages[courseID.toString()];
    if (pageID) return link + '/pages/' + pageID;
    else return link;
  }

}
