import { Component, OnInit } from '@angular/core';
import {Course} from "../../../../_domain/Course";
import {ActivatedRoute} from "@angular/router";
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../_services/error.service";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;

  course: Course;
  ruleSystemLastRun: string;

  constructor(
    private route: ActivatedRoute,
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.getCourseInfo(params.id);
    }).unsubscribe();
  }

  getCourse(courseID: number): void {
    this.api.getCourse(courseID)
      .subscribe(
        course => this.course = course,
        error => ErrorService.set(error),
        () => this.loading = false
      );
  }

  getCourseInfo(courseID: number): void {
    this.api.getCourseInfo(courseID)
      .subscribe(
        info => {
          console.log(info)
          this.ruleSystemLastRun = info.ruleSystemLastRun;
          this.getCourse(courseID);
        },
        error => ErrorService.set(error)
      );
  }
}
