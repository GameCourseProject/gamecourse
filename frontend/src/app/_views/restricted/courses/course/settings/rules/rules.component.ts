import { Component, OnInit } from '@angular/core';
import {Reduce} from "../../../../../../_utils/lists/reduce";
import {Course} from "../../../../../../_domain/courses/course";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";

@Component({
  selector: 'app-rules',
  templateUrl: './rules.component.html',
  styleUrls: ['./rules.component.scss']
})
export class RulesComponent implements OnInit {

  loading: boolean = true;

  course: Course;

  reduce = new Reduce();
  searchQuery: string;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    // this.reduce.search(this.modules, query); TODO
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Sections ----------------- ***/
  /*** --------------------------------------------- ***/

  // TODO


  /*** --------------------------------------------- ***/
  /*** -------------------- Tags ------------------- ***/
  /*** --------------------------------------------- ***/

  // TODO
}
