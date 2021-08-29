import { Component, OnInit } from '@angular/core';
import {Course} from "../../_domain/Course";
import {ApiHttpService} from "../../_services/api/api-http.service";
import {throwError} from "rxjs";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;

  activeCourses: Course[];

  constructor(private apiHttpService: ApiHttpService) {
  }

  ngOnInit(): void {
    this.getUserActiveCourses();
  }

  getUserActiveCourses(): void {
    this.apiHttpService.getAllUserActiveCourses(1) // FIXME: get actual ID
      .subscribe(
        res => {
          this.activeCourses = res;
          this.loading = false;
        },
        error => throwError(error)
      )
  }

}
