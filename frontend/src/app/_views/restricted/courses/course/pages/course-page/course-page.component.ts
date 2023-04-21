import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, NavigationStart, Router} from "@angular/router";

import {Course} from "../../../../../../_domain/courses/course";
import {Page} from "../../../../../../_domain/views/pages/page";
import {Skill} from "../../../../../../_domain/modules/config/personalized-config/skills/skill";
import {SkillTree} from "../../../../../../_domain/modules/config/personalized-config/skills/skill-tree";
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";
import {Tier} from "../../../../../../_domain/modules/config/personalized-config/skills/tier";
import {User} from "../../../../../../_domain/users/user";
import {View} from "../../../../../../_domain/views/view";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";

import {Moment} from "moment";
import {environment} from "../../../../../../../environments/environment";

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

  // FIXME: hard-coded Skill Tree
  skillTrees: SkillTree[];
  skillTreesInfo: {
    skillTreeId: number,
    loading: {tiers: boolean, skills: boolean},
    data: {tiers: {type: TableDataType, content: any}[][], skills: {type: TableDataType, content: any}[][]},
    tiers: Tier[],
    skills: Skill[]
  }[] = [];
  availableWildcards: number;
  info: {[skillID: number]: {available: boolean, attempts: number, cost: number, completed: boolean, wildcardsUsed: number}};
  vcIcon: string = environment.apiEndpoint + '/modules/VirtualCurrency/assets/default.png';

  // FIXME: hard-coded Streaks
  streaks: Streak[] = [];
  userStreaksInfo: {id: number, nrCompletions: number, progress: number, deadline: Moment}[];
  streaksTotal: number;

  // FIXME: hard-coded Gold Exchange
  hasExchanged: boolean;
  wallet: number;
  exchanging: boolean;

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

    // FIXME: hard-coded
    if (this.page.name === "Skill Tree") {
      await this.initSkillTreesInfo(this.course.id);
      this.availableWildcards = await this.api.getUserTotalAvailableWildcards(this.course.id, this.user?.id || this.viewer.id, this.skillTrees[0].id).toPromise();

    } else if (this.page.name === "Streaks") {
      this.streaks = await this.api.getStreaks(this.course.id).toPromise();
      const info = await this.api.getUserStreaksInfo(this.course.id, this.user?.id || this.viewer.id).toPromise();
      this.userStreaksInfo = info.info;
      this.streaksTotal = info.total;

    } else if (this.page.name === "Gold Exchange") {
      this.hasExchanged = await this.api.hasExchangedUserTokens(this.course.id, this.user?.id || this.viewer.id).toPromise();
      this.wallet = await this.api.getUserTokens(this.course.id, this.user?.id || this.viewer.id).toPromise();
    }
  }

  async renderPage(pageID: number, userID?: number): Promise<void> {
    this.pageView = await this.api.renderPage(pageID, userID).toPromise();
  }


  // FIXME: hard-coded below

  async initSkillTreesInfo(courseID: number) { // FIXME: hard-coded
    this.skillTreesInfo = [];
    this.skillTrees = await this.api.getSkillTrees(courseID).toPromise();
    for (const skillTree of this.skillTrees) {
      // Get info
      const tiers = await this.api.getTiersOfSkillTree(skillTree.id, null).toPromise();
      const skills = await this.api.getSkillsOfSkillTree(skillTree.id, null, null, null).toPromise();
      this.info = await this.api.getSkillsExtraInfo(this.course.id, this.user?.id || this.viewer.id, this.skillTrees[0].id).toPromise();
      this.skillTreesInfo.push({skillTreeId: skillTree.id, loading: {tiers: false, skills: false}, data: {tiers: [], skills: []}, tiers, skills});
    }
  }

  getSkillTreeInfo(skillTreeId: number): { // FIXME: hard-coded
    skillTreeId: number,
    loading: {tiers: boolean, skills: boolean},
    data: {tiers: {type: TableDataType, content: any}[][], skills: {type: TableDataType, content: any}[][]},
    tiers: Tier[],
    skills: Skill[]
  } {
    const index = this.skillTreesInfo.findIndex(el => el.skillTreeId === skillTreeId);
    return this.skillTreesInfo[index];
  }

  filterSkillsByTier(skills: Skill[], tierID: number): Skill[] { // FIXME: hard-coded
    return skills.filter(skill => skill.tierID === tierID && skill.isActive);
  }

  goToSkillPage(skill: Skill) { // FIXME: hard-coded
    this.router.navigate(['./skills', skill.id], {relativeTo: this.route.parent.parent})
  }

  getComboText(combo: Skill[]): string { // FIXME: hard-coded
    let str = '';
    for (let i = 0; i < combo.length; i++) {
      const skill = combo[i];
      str += skill.name + (i != combo.length - 1 ? ' + ' : '');
    }
    return str;
  }

  steps(goal: number): number[] {
    return Array(goal);
  }

  async exchange() {
    this.exchanging = true;

    const earnedXP = await this.api.exchangeUserTokens(this.course.id, this.user?.id || this.viewer.id, '1:3', 1000).toPromise();
    this.hasExchanged = await this.api.hasExchangedUserTokens(this.course.id, this.user?.id || this.viewer.id).toPromise();
    this.wallet = await this.api.getUserTokens(this.course.id, this.user?.id || this.viewer.id).toPromise();

    this.exchanging = false;
    AlertService.showAlert(AlertType.SUCCESS, 'You earned ' + earnedXP + ' XP!');
  }
}

export interface Streak { // FIXME: hard-coded
  id: number,
  name: string,
  description: string,
  color: string,
  image: string;
  svg: string,
  goal: number,
  reward: number,
  tokens: number,
  isExtra: boolean,
  isRepeatable: boolean,
  isPeriodic: boolean,
  nrCompletions: number,
  progress: number,
  deadline: Moment,
}
