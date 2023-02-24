import { Component, Input, OnInit } from '@angular/core';
import { Course } from 'src/app/_domain/courses/course';
import { User } from "../../../_domain/users/user";

import { ApiHttpService } from "../../../_services/api/api-http.service";
import { Router } from "@angular/router";

@Component({
  selector: 'app-course-card',
  templateUrl: './course-card.component.html'
})
export class CourseCardComponent implements OnInit {

  @Input() course: Course;
  @Input() user: User;

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  ngOnInit(): void {
  }

  async goToCourse() {
    const redirectLink = await this.getRedirectLink();
    this.router.navigate([redirectLink]);
  }

  async getRedirectLink(): Promise<string> {
    const link = '/courses/' + this.course.id;
    if (this.user.isAdmin) return link + '/overview'; // admins go to overview page

    const userLandingPage = await this.api.getUserLandingPage(this.course.id, this.user.id).toPromise();
    const pageID = userLandingPage?.id || this.course.landingPage;
    if (pageID) return link + '/pages/' + pageID;
    else if (await this.api.isTeacher(this.course.id, this.user.id).toPromise()) return link + '/overview';
    else return link;
  }

}
