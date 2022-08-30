import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {exists} from "../../../../../../../../../_utils/misc/misc";
import {Subject} from "rxjs";
import {Tier} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/tier";
import {Skill} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/skill";
import {finalize} from "rxjs/operators";

@Component({
  selector: 'app-skills',
  templateUrl: './skills.component.html',
  styleUrls: ['./skills.component.scss']
})
export class SkillsComponent implements OnInit {

  loading: boolean = true;

  courseID: number;
  courseFolder: string;

  skillTreesInfo: Map<number, {tiers: Tier[], skills: Skill[]}> = new Map<number, {tiers: Tier[]; skills: Skill[]}>();

  mode: 'add' | 'edit';
  newSkill: SkillData = {
    tierID: null,
    name: null,
    isCollab: null,
    isExtra: null,
    isActive: null,
    position: null,
    dependencies: []
  };
  skillToEdit: Skill;
  skillToDelete: Skill;
  infoSelected: {tiers: Tier[], skills: Skill[]};

  isSkillModalOpen: boolean;
  skillModalRendered: Subject<void> = new Subject<void>();
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  isSkillPreviewModalOpen: boolean;
  saving: boolean;

  // FIXME: allow more than two dependencies (backend can already handle it)
  selectedDependency1: Skill = null;
  selectedDependency2: Skill = null;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public router: Router
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.getSkillTreesInfo(this.courseID);
      await this.getCourseDataFolder();
      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getSkillTreesInfo(courseID: number) {
    const skillTrees = await this.api.getSkillTrees(this.courseID).toPromise();
    for (const skillTree of skillTrees) {
      const tiers = await this.api.getTiersOfSkillTree(skillTree.id, null).toPromise();
      const skills = await this.api.getSkillsOfSkillTree(skillTree.id, null, null, null).toPromise();
      this.skillTreesInfo.set(skillTree.id, {tiers, skills});
    }
  }

  async getCourseDataFolder() {
    this.courseFolder = (await this.api.getCourseById(this.courseID).toPromise()).folder;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  createSkill(): void {
    this.loading = true;
    this.api.createSkill(this.courseID, this.newSkill)
      .pipe( finalize(() => {
        this.isSkillModalOpen = false;
        this.clearObject(this.newSkill);
        this.selectedDependency1 = null;
        this.selectedDependency2 = null;
        this.infoSelected = null;
        this.loading = false;
      }) )
      .subscribe(async () => await this.getSkillTreesInfo(this.courseID))
  }

  editSkill(): void {
    this.loading = true;
    this.newSkill['id'] = this.skillToEdit.id;

    this.api.editSkill(this.courseID, this.newSkill)
      .pipe( finalize(() => {
        this.isSkillModalOpen = false;
        this.clearObject(this.newSkill);
        this.selectedDependency1 = null;
        this.selectedDependency2 = null;
        this.skillToEdit = null;
        this.infoSelected = null;
        this.loading = false;
      }) )
      .subscribe(async () => await this.getSkillTreesInfo(this.courseID))
  }

  deleteSkill(): void {
    this.loading = true;
    this.api.deleteSkill(this.courseID, this.skillToDelete.id)
      .pipe( finalize(() => {
        this.isDeleteVerificationModalOpen = false;
        this.clearObject(this.newSkill);
        this.skillToDelete = null;
        this.infoSelected = null;
        this.loading = false;
      }) )
      .subscribe(async () => await this.getSkillTreesInfo(this.courseID))
  }

  toggleSkill(skillId: number, param: string) {
    this.loading = true;

    const skill = this.getSkill(skillId);
    skill[param] = !skill[param];
    this.api.editSkill(this.courseID, skill)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(async () => {})
  }

  moveSkill(skill: Skill, direction: number) {
    this.loading = true;

    skill.position += (direction > 0 ? -1 : 1);
    this.api.editSkill(this.courseID, skill)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(async () => await this.getSkillTreesInfo(this.courseID))
  }

  exportAllSkills() {
    // TODO
  }

  addSkillDependency(dep1: Skill, dep2: Skill) {
    if (!this.newSkill.dependencies) this.newSkill.dependencies = [];
    this.newSkill.dependencies.push([dep1, dep2]);
  }

  removeSkillDependency(dependency: Skill[]) {
    this.newSkill.dependencies = this.newSkill.dependencies.filter(combo => {
      return !(combo[0].id === dependency[0].id && combo[1].id === dependency[1].id);
    });
  }

  goToSkillPage(skillID: number) {
    this.router.navigate(['./skills', skillID, true], {relativeTo: this.route.parent.parent})
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToSubmit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    // Validate inputs
    return isValid(this.newSkill.tierID) && isValid(this.newSkill.name);
  }

  initEditSkill(skill: Skill): void {
    this.newSkill = {
      tierID: skill.tierID,
      name: skill.name,
      color: skill.color,
      page: skill.page,
      isCollab: skill.isCollab,
      isExtra: skill.isExtra,
      isActive: skill.isActive,
      position: skill.position,
      ruleID: skill.ruleID,
      dependencies: skill.dependencies
    };
    this.skillToEdit = skill;
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

  getSkillTreeInfo(): {tiers: Tier[], skills: Skill[]}[] {
    const info = [];

    const skillTreeIds = [...this.skillTreesInfo.keys()];
    for (const skillTreeId of skillTreeIds) {
      info.push(this.skillTreesInfo.get(skillTreeId));
    }

    return info;
  }

  getTier(tierId: number): Tier {
    for (const info of this.getSkillTreeInfo()) {
      for (const tier of info.tiers) {
        if (tier.id == tierId) return tier;
      }
    }
    return null;
  }

  getWildcardTier(tiers: Tier[]): Tier {
    let wildcardTier: Tier = null;
    for (const tier of tiers) {
      if (tier.isWildcard()) wildcardTier = tier;
    }
    return wildcardTier;
  }

  getSkill(skillId: number): Skill {
    for (const info of this.getSkillTreeInfo()) {
      for (const skill of info.skills) {
        if (skill.id == skillId) return skill;
      }
    }
    return null;
  }

  getDependenciesText(dependencies: Skill[][]): string {
    let str = '';
    for (let i = 0; i < dependencies.length; i++) {
      const dependency = dependencies[i];
      str += this.getComboText(dependency) + (i != dependencies.length - 1 ? ' | ' : '');
    }
    return str;
  }

  getComboText(combo: Skill[]): string {
    let str = '';
    for (let i = 0; i < combo.length; i++) {
      const skill = combo[i];
      str += skill.name + (i != combo.length - 1 ? ' + ' : '');
    }
    return str;
  }

  filterSkillsForDependencies(skillID: number, tier: Tier): Skill[] {
    const filteredSkills = this.infoSelected.skills
      .filter(skill => skill.id !== skillID && skill.isActive &&
        this.getTier(skill.tierID).position === tier.position - 1)
      .sort((a, b) => a.name.localeCompare(b.name));

      if (addWildcardOption(this.infoSelected.tiers, this.infoSelected.skills))
        filteredSkills.push(Skill.getWildcard(this.getWildcardTier(this.infoSelected.tiers).id));

      return filteredSkills;

      function addWildcardOption(tiers: Tier[], skills: Skill[]): boolean {
        let wildcardTier: Tier = null;
        for (const tier of tiers) {
          if (tier.isWildcard()) wildcardTier = tier;
        }
        if (!wildcardTier.isActive) return false;

        let hasWildcardSkill = false;
        for (const skill of skills) {
          if (skill.tierID === wildcardTier.id) {
            hasWildcardSkill = true;
            break;
          }
        }
        return hasWildcardSkill;
      }
    }

  filterSkillsByTier(skills: Skill[], tierID: number): Skill[] {
    return skills.filter(skill => skill.tierID === tierID && skill.isActive);
  }

  initTextEditor() {
    setTimeout(() => this.skillModalRendered.next(), 0);
    return [];
  }

}

export interface SkillData {
  id?: number,
  tierID: number,
  name: string,
  color?: string,
  page?: string,
  isCollab: boolean,
  isExtra: boolean,
  isActive: boolean,
  position: number,
  ruleID?: number,
  dependencies: Skill[][]
}
