import { Component, OnInit } from '@angular/core';

import {Course} from "../../../_domain/courses/course";
import {ApiHttpService} from "../../../_services/api/api-http.service";

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html'
})
export class HomeComponent implements OnInit {

  loading = true;

  activeCourses: Course[] = [];

  constructor(
    private api: ApiHttpService
  ) { }

  async ngOnInit(): Promise<void> {
    const loggedUser = await this.api.getLoggedUser().toPromise();
    this.activeCourses = await this.api.getUserCourses(loggedUser.id, true, null).toPromise();
    this.loading = false;
  }


  /*** --------------------------------------------- ***/
  /*** ----------------- Redirect ------------------ ***/
  /*** --------------------------------------------- ***/

  getRedirectLink(course: Course): string {
    const link = '/courses/' + course.id;
    const pageID = course.landingPage; // FIXME: landing page per user role
    if (pageID) return link + '/pages/' + pageID;
    else return link;
  }

}
