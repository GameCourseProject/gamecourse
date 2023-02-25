import { Component, OnInit } from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";

import {User} from "../../../../../_domain/users/user";
import {Course} from "../../../../../_domain/courses/course";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html'
})
export class MainComponent implements OnInit {

  loading: boolean = true;

  course: Course;
  user: User;

  constructor(
    private route: ActivatedRoute,
    private api: ApiHttpService,
    private router: Router
  ) { }

  async ngOnInit(): Promise<void> {
    // Get logged user information
    await this.getLoggedUser();

    this.route.parent.params.subscribe(async params => {
      // Get course information
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
  }

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }
}
