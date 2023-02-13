import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {View} from "../../../../../_domain/views/view";
import {Skill} from "../../../../../_domain/modules/config/personalized-config/skills/skill";
import {ApiEndpointsService} from "../../../../../_services/api/api-endpoints.service";
import {Course} from "../../../../../_domain/courses/course";
import {exists} from "../../../../../_utils/misc/misc";
import {User} from "../../../../../_domain/users/user";
import {Page} from "../../../../../_domain/views/pages/page";

@Component({
  selector: 'app-page',
  templateUrl: './page.component.html'
})
export class PageComponent implements OnInit {

  loading: boolean = true;

  course: Course;
  user: User;

  page: Page;
  pageView: View;

  skill: Skill;
  isPreview: boolean;

  participationKey: string;
  lectureNr: number;
  typeOfClass: string;
  typesOfClass: string[];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  get ApiEndpointsService(): typeof ApiEndpointsService {
    return ApiEndpointsService;
  }

  async ngOnInit(): Promise<void> {
    // Get logged user information
    await this.getLoggedUser();

    this.route.parent.params.subscribe(async params => {
      // Get course information
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      this.route.params.subscribe(async params => {
        if (this.router.url.includes('skills')) { // Skill page
          this.skill = await this.api.getSkillById(params.id).toPromise();
          this.isPreview = this.router.url.includes('preview');

        } else if (this.router.url.includes('participation')) { // QR participation
          this.participationKey = params.key;
          this.course = await this.api.getCourseById(courseID).toPromise();
          this.typesOfClass = await this.api.getTypesOfClass().toPromise();

        } else { // Render page
          this.pageView = null; // NOTE: Important - Forces view to completely refresh

          // Get page info
          const pageID = parseInt(params.id);
          await this.getPage(pageID);

          // Render page
          const userID = parseInt(params.userId) || null;
          await this.renderPage(pageID, userID);
        }
        this.loading = false;
      });
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


  /*** --------------------------------------------- ***/
  /*** -------------------- Page ------------------- ***/
  /*** --------------------------------------------- ***/

  async getPage(pageID: number): Promise<void> {
    this.page = await this.api.getPageById(pageID).toPromise();
  }

  async renderPage(pageID: number, userID: number): Promise<void> {
    this.pageView = await this.api.renderPage(this.course.id, pageID, userID || this.user.id).toPromise();
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Skills ------------------ ***/
  /*** --------------------------------------------- ***/

  closePreview() {
    history.back();
  }


  /*** --------------------------------------------- ***/
  /*** ------------- QR Participation -------------- ***/
  /*** --------------------------------------------- ***/

  async submitParticipation() {
    this.loading = true;

    const loggedUser = await this.api.getLoggedUser().toPromise();
    await this.api.addQRParticipation(this.course.id, loggedUser.id, this.lectureNr, this.typeOfClass, this.participationKey).toPromise();

    const successBox = $('.success_msg');
    successBox.empty();
    successBox.append("Your class participation was registered.<br />Congratulations! Keep participating. 😊");
    successBox.show().delay(5000).fadeOut();

    this.loading = false;
  }

  isReadyToSubmitParticipation(): boolean {
    return exists(this.lectureNr) && this.lectureNr > 0 && exists(this.typeOfClass);
  }

}
