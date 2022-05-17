import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {View} from "../../../../../_domain/views/view";
import {Skill} from "../../../../../_domain/skills/skill";
import {ApiEndpointsService} from "../../../../../_services/api/api-endpoints.service";
import {finalize} from "rxjs/operators";
import {Course} from "../../../../../_domain/courses/course";
import {exists} from "../../../../../_utils/misc/misc";

@Component({
  selector: 'app-page',
  templateUrl: './page.component.html',
  styleUrls: ['./page.component.scss']
})
export class PageComponent implements OnInit {

  courseID: number;
  pageID: number;
  userID: number;

  skillID: number;

  pageView: View;
  skill: Skill;

  participationKey: string;
  course: Course;
  lectureNr: number;
  typeOfClass: TypeOfClass;

  loading: boolean;
  isPreview: boolean;

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
      this.courseID = parseInt(params.id);

      this.route.params.subscribe(params => {
        if (this.router.url.includes('skills')) {
          this.skillID = parseInt(params.id);
          this.isPreview = !!params.preview;
          this.getSkill();

        } else if (this.router.url.includes('participation')) {
          this.participationKey = params.key;
          this.api.getCourse(this.courseID)
            .pipe(finalize(() => this.loading = false))
            .subscribe(course => this.course = course)

        } else {
          this.pageID = parseInt(params.id);
          this.userID = parseInt(params.userId) || null;
          this.getPage();
        }
      });
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getPage(): void {
    this.loading = true;
    this.pageView = null; // NOTE: Important - Forces view to completely refresh
    this.api.getLoggedUser()
      .subscribe(user => {
        this.api.renderPage(this.courseID, this.pageID, this.userID || user.id)
          .pipe(finalize(() => this.loading = false))
          .subscribe(view => this.pageView = view);
      });
  }

  getSkill(): void {
    this.loading = true;
    this.api.renderSkillPage(this.courseID, this.skillID)
      .pipe(finalize(() => this.loading = false))
      .subscribe(skill => this.skill = skill);
  }

  goBack() {
    this.router.navigate(['./settings/modules/skills/config'], {relativeTo: this.route.parent});
  }

  getTypesOfClasses(): string[] {
    return Object.values(TypeOfClass);
  }

  submitParticipation() {
    this.loading = true;
    this.api.submitQRParticipation(this.courseID, this.participationKey, this.lectureNr, this.typeOfClass)
      .pipe(finalize(() => this.loading = false))
      .subscribe(
        res => {
          const successBox = $('.success_msg');
          successBox.empty();
          successBox.append("Your active participation was registered.<br />Congratulations! Keep participating. ;)");
          successBox.show().delay(5000).fadeOut();
        });
  }

  isReadyToSubmitParticipation(): boolean {
    return exists(this.lectureNr) && this.lectureNr > 0 &&
      exists(this.typeOfClass) && Object.values(TypeOfClass).includes(this.typeOfClass);
  }

}

export enum TypeOfClass {
  LECTURE = 'Lecture',
  INVITED_LECTURE = 'Invited Lecture'
}
