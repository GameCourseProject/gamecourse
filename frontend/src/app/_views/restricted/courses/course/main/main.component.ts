import { Component, OnInit } from '@angular/core';
import {Course} from "../../../../../_domain/courses/course";
import {ActivatedRoute} from "@angular/router";
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {finalize} from "rxjs/operators";

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
      this.getCourse(parseInt(params.id));
    });
  }

  getCourse(courseID: number): void {
    this.api.getCourse(courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(course => this.course = course);
  }
}
