import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ActivatedRoute, Route, Router} from "@angular/router";
import {ErrorService} from "../../../../_services/error.service";
import {View} from "../../../../_domain/views/view";
import {Skill} from "../../../../_domain/skills/skill";
import {ApiEndpointsService} from "../../../../_services/api/api-endpoints.service";
import {finalize} from "rxjs/operators";

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
          .subscribe(
            view => this.pageView = view,
            error => ErrorService.set(error)
          );
      }, error => ErrorService.set(error));
  }

  getSkill(): void {
    this.loading = true;
    this.api.renderSkillPage(this.courseID, this.skillID)
      .pipe(finalize(() => this.loading = false))
      .subscribe(
        skill => this.skill = skill,
        error => ErrorService.set(error)
      );
  }

  goBack() {
    this.router.navigate(['./settings/modules/skills/config'], {relativeTo: this.route.parent});
  }

}
