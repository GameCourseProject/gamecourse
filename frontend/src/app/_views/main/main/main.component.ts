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

  activeCourses: Course[] = [];

  constructor(private apiHttpService: ApiHttpService) {
  }

  ngOnInit(): void {
    this.loading = false;
    // this.getUserActiveCourses();
  }

  getUserActiveCourses(): void {
    const queries = (qs: QueryStringParameters) => {
      qs.push('active', true);
    };

    this.apiHttpService.getAllUserCourses(1, queries) // FIXME: get actual ID
      .subscribe(
        res => {
          this.activeCourses = res;
          this.loading = false;
        },
        error => throwError(error)
      )
  }

}
