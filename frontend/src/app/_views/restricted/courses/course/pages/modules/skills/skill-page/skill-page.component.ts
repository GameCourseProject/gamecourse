import { Component, OnInit } from '@angular/core';
import {ActivatedRoute, NavigationStart, Router} from "@angular/router";

import {Course} from "../../../../../../../../_domain/courses/course";
import {Skill} from "../../../../../../../../_domain/modules/config/personalized-config/skills/skill";

import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";

@Component({
  selector: 'app-skill-page',
  templateUrl: './skill-page.component.html'
})
export class SkillPageComponent implements OnInit {

  loading: boolean = true;

  course: Course;

  skill: Skill;
  isPreview: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.route.parent.parent.params.subscribe(async params => {
      // Get course information
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      // Get skill information
      this.route.parent.params.subscribe(async params => {
        const skillID = parseInt(params.id);
        await this.getSkill(skillID);

        this.isPreview = this.router.url.includes('preview');
        this.loading = false;
      });

      // Whenever route changes, set loading as true
      this.router.events.subscribe(event => {
        if (event instanceof NavigationStart)
          this.loading = true;
      });
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Skills ------------------ ***/
  /*** --------------------------------------------- ***/

  async getSkill(skillID: number): Promise<void> {
    this.skill = await this.api.getSkillById(skillID).toPromise();
  }

  goBack() {
    history.back();
  }
}
