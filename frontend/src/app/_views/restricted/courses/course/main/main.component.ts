import { Component, OnInit } from '@angular/core';
import {Course} from "../../../../../_domain/courses/course";
import {ActivatedRoute} from "@angular/router";
import {ApiHttpService} from "../../../../../_services/api/api-http.service";

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
    this.route.params.subscribe(async params => {
      const courseId = parseInt(params.id);
      await this.getCourse(courseId);
      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }
}
