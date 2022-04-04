import { Component, OnInit } from '@angular/core';

import {Course} from "../../../_domain/courses/course";

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";
import {finalize} from "rxjs/operators";
import {RoleTypeId} from "../../../_domain/roles/role-type";
import {User} from "../../../_domain/users/user";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;

  user: User;

  activeCourses: Course[] = [];
  redirectPages: {[courseID: string]: {id: number, roleType: RoleTypeId}}; // courseID -> {pageId, roleType}

  constructor(
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.getUserActiveCourses();
  }

  getUserActiveCourses(): void {
    this.api.getLoggedUser()
      .subscribe(
        user => {
          this.user = user;
          this.api.getUserActiveCourses()
            .pipe( finalize(() => this.loading = false) )
            .subscribe(
              res => {
                this.activeCourses = res.courses;
                this.redirectPages = res.landingPages;
              },
              error => ErrorService.set(error)
            );
        },
        error => ErrorService.set(error)
      );
  }

  getRedirectLink(courseID: number): string {
    let link = '/courses/' + courseID;
    const page = this.redirectPages[courseID.toString()];
    if (page.id) {
      link += '/pages/' + page.id;
      if (page.roleType === RoleTypeId.ROLE_INTERACTION)
        return link + '/user/' + this.user.id;
      return link;
    }
    else return link;
  }

}
