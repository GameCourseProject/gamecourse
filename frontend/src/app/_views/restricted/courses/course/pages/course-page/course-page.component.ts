import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, NavigationStart, Router} from "@angular/router";

import {Course} from "../../../../../../_domain/courses/course";
import {Page} from "../../../../../../_domain/views/pages/page";
import {User} from "../../../../../../_domain/users/user";
import {View} from "../../../../../../_domain/views/view";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";

@Component({
  selector: 'app-course-page',
  templateUrl: './course-page.component.html'
})
export class CoursePageComponent implements OnInit {

  loading: boolean = true;

  course: Course;
  viewer: User;
  user: User;

  page: Page;
  pageView: View;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  async ngOnInit(): Promise<void> {
    // Get logged user information
    await this.getLoggedUser();

    this.route.parent.parent.params.subscribe(async params => {
      // Get course information
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      // Get page information
      this.route.params.subscribe(async params => {
        const userID = parseInt(params.userId) || null;
        if (userID) this.user = await this.api.getUserById(userID).toPromise();

        const pageID = parseInt(params.id);
        await this.getPage(pageID);

        // Render page
        this.pageView = null; // NOTE: forces view to completely refresh
        await this.renderPage(pageID, userID);
        this.loading = false;
      });
    });

    // Whenever route changes, set loading as true
    this.router.events.subscribe(event => {
      if (event instanceof NavigationStart)
        this.loading = true;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.viewer = await this.api.getLoggedUser().toPromise();
  }

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Page ------------------- ***/
  /*** --------------------------------------------- ***/

  async getPage(pageID: number): Promise<void> {
    this.page = await this.api.getPageById(pageID).toPromise();
  }

  async renderPage(pageID: number, userID?: number): Promise<void> {
    this.pageView = await this.api.renderPage(pageID, userID).toPromise();
  }

}
