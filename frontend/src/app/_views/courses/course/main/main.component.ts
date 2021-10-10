import { Component, OnInit } from '@angular/core';
import {Course} from "../../../../_domain/courses/course";
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

  constructor(
    private route: ActivatedRoute,
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.getCourse(params.id);
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
}
