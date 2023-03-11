import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute, NavigationStart, Router} from "@angular/router";
import {View} from "../../../../../_domain/views/view";
import {Skill} from "../../../../../_domain/modules/config/personalized-config/skills/skill";
import {ApiEndpointsService} from "../../../../../_services/api/api-endpoints.service";
import {Course} from "../../../../../_domain/courses/course";
import {exists} from "../../../../../_utils/misc/misc";
import {User} from "../../../../../_domain/users/user";
import {Page} from "../../../../../_domain/views/pages/page";
import {TableDataType} from "../../../../../_components/tables/table-data/table-data.component";
import {Tier} from "../../../../../_domain/modules/config/personalized-config/skills/tier";
import {SkillTree} from "../../../../../_domain/modules/config/personalized-config/skills/skill-tree";
import {environment} from "../../../../../../environments/environment";
import {Moment} from "moment";

@Component({
  selector: 'app-page',
  templateUrl: './page.component.html'
})
export class PageComponent implements OnInit {

  loading: boolean = true;

  course: Course;
  viewer: User;
  user: User;

  page: Page;
  pageView: View;

  // FIXME: hard-coded
  skillTrees: SkillTree[];
  skillTreesInfo: {
    skillTreeId: number,
    loading: {tiers: boolean, skills: boolean},
    data: {tiers: {type: TableDataType, content: any}[][], skills: {type: TableDataType, content: any}[][]},
    tiers: Tier[],
    skills: Skill[]
  }[] = [];
  availableWildcards: number;
  info: {[skillID: number]: {attempts: number, cost: number, completed: boolean}};
  vcIcon: string = environment.apiEndpoint + '/modules/VirtualCurrency/assets/default.png';

  streaks: Streak[] = [];
  userStreaksInfo: {id: number, nrCompletions: number, progress: number, deadline: Moment}[];

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
          this.pageView = null;
          const pageID = parseInt(params.id);
          const userID = parseInt(params.userId) || (await this.api.getLoggedUser().toPromise()).id;
          this.user = await this.api.getUserById(userID).toPromise();
          await this.getPage(pageID);

          // Render page
          await this.renderPage(pageID, userID);
        }
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
      this.availableWildcards = await this.api.getUserTotalAvailableWildcards(this.course.id, this.user.id, this.skillTrees[0].id).toPromise();

    } else if (this.page.name === "Streaks") {
      this.streaks = await this.api.getStreaks(this.course.id).toPromise();
      this.userStreaksInfo = await this.api.getUserStreaksInfo(this.course.id, this.user.id).toPromise();
    }
  }

  async renderPage(pageID: number, userID: number): Promise<void> {
    this.pageView = await this.api.renderPage(this.course.id, pageID, userID || this.viewer.id).toPromise();
  }

  // FIXME: hard-coded

  async initSkillTreesInfo(courseID: number) {
    this.skillTreesInfo = [];
    this.skillTrees = await this.api.getSkillTrees(courseID).toPromise();
    for (const skillTree of this.skillTrees) {
      // Get info
      const tiers = await this.api.getTiersOfSkillTree(skillTree.id, null).toPromise();
      const skills = await this.api.getSkillsOfSkillTree(skillTree.id, null, null, null).toPromise();
      this.info = await this.api.getSkillsExtraInfo(this.course.id, this.user.id, this.skillTrees[0].id).toPromise();
      this.skillTreesInfo.push({skillTreeId: skillTree.id, loading: {tiers: false, skills: false}, data: {tiers: [], skills: []}, tiers, skills});
    }
  }

  getSkillTreeInfo(skillTreeId: number): {
    skillTreeId: number,
    loading: {tiers: boolean, skills: boolean},
    data: {tiers: {type: TableDataType, content: any}[][], skills: {type: TableDataType, content: any}[][]},
    tiers: Tier[],
    skills: Skill[]
  } {
    const index = this.skillTreesInfo.findIndex(el => el.skillTreeId === skillTreeId);
    return this.skillTreesInfo[index];
  }

  filterSkillsByTier(skills: Skill[], tierID: number): Skill[] {
    return skills.filter(skill => skill.tierID === tierID && skill.isActive);
  }

  goToSkillPage(skill: Skill) {
    this.router.navigate(['./skills', skill.id], {relativeTo: this.route.parent})
  }

  getComboText(combo: Skill[]): string {
    let str = '';
    for (let i = 0; i < combo.length; i++) {
      const skill = combo[i];
      str += skill.name + (i != combo.length - 1 ? ' + ' : '');
    }
    return str;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Skills ------------------ ***/
  /*** --------------------------------------------- ***/

  goBack() {
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

  steps(goal: number): number[] {
    return Array(goal);
  }

}

export interface Streak {
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
