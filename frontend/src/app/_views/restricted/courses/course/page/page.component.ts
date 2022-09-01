import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {View} from "../../../../../_domain/views/view";
import {Skill} from "../../../../../_domain/modules/config/personalized-config/skills/skill";
import {ApiEndpointsService} from "../../../../../_services/api/api-endpoints.service";
import {Course} from "../../../../../_domain/courses/course";
import {exists} from "../../../../../_utils/misc/misc";
import {User} from "../../../../../_domain/users/user";
import {Page} from "../../../../../_domain/pages & templates/page";

@Component({
  selector: 'app-page',
  templateUrl: './page.component.html',
  styleUrls: ['./page.component.scss']
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

  ngOnInit(): void {
    this.route.parent.params.subscribe(params => {
      const courseID = parseInt(params.id);

      this.route.params.subscribe(async params => {
        if (this.router.url.includes('skills')) { // Skill page
          this.skill = await this.api.getSkillById(params.id).toPromise();
          this.isPreview = !!params.preview;

        } else if (this.router.url.includes('participation')) { // QR participation
          this.participationKey = params.key;
          this.course = await this.api.getCourseById(courseID).toPromise();
          this.typesOfClass = await this.api.getTypesOfClass().toPromise();

        } else { // Render page
          this.pageView = null; // NOTE: Important - Forces view to completely refresh

          // const pageID = parseInt(params.id); FIXME: render page
          // const userID = parseInt(params.userId) || null;
          // this.api.getLoggedUser()
          //   .subscribe(user => {
          //     this.api.renderPage(this.courseID, this.pageID, this.userID || user.id)
          //       .pipe(finalize(() => this.loading = false))
          //       .subscribe(view => this.pageView = view);
          //   });
        }
        this.loading = false;
      });
    });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Skills ------------------ ***/
  /*** --------------------------------------------- ***/

  closePreview() {
    this.router.navigate(['./settings/modules/skills/config'], {relativeTo: this.route.parent});
  }


  /*** --------------------------------------------- ***/
  /*** ------------- QR Participation -------------- ***/
  /*** --------------------------------------------- ***/

  async submitParticipation() {
    this.loading = true;

    const loggedUser = await this.api.getLoggedUser().toPromise();
    await this.api.submitQRParticipation(this.course.id, loggedUser.id, this.lectureNr, this.typeOfClass, this.participationKey).toPromise();

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
