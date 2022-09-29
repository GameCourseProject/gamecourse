import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";

import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";

import {SkillTree} from 'src/app/_domain/modules/config/personalized-config/skills/skill-tree';
import {Tier} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/tier";
import {Skill} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/skill";
import {Action, ActionScope, scopeAllows} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../../../../_components/tables/table-data/table-data.component";

@Component({
  selector: 'app-skills',
  templateUrl: './skills.component.html'
})
export class SkillsComponent implements OnInit {

  loading = {
    page: true
  }

  courseID: number;
  courseFolder: string;

  skillTrees: SkillTree[];
  skillTreesInfo: {
    skillTreeId: number,
    loading: {tiers: boolean, skills: boolean},
    data: {tiers: {type: TableDataType, content: any}[][], skills: {type: TableDataType, content: any}[][]},
    tiers: Tier[],
    skills: Skill[]
  }[] = [];
  skillTreeInView: SkillTree;

  // mode: 'add' | 'edit';
  // newSkill: SkillData = {
  //   tierID: null,
  //   name: null,
  //   isCollab: null,
  //   isExtra: null,
  //   isActive: null,
  //   position: null,
  //   dependencies: []
  // };
  // skillToEdit: Skill;
  // skillToDelete: Skill;
  // infoSelected: {tiers: Tier[], skills: Skill[]};
  //
  // isSkillModalOpen: boolean;
  // skillModalRendered: Subject<void> = new Subject<void>();
  // isDeleteVerificationModalOpen: boolean;
  // isImportModalOpen: boolean;
  // isSkillPreviewModalOpen: boolean;
  // saving: boolean;
  //
  // // FIXME: allow more than two dependencies (backend can already handle it)
  // selectedDependency1: Skill = null;
  // selectedDependency2: Skill = null;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public router: Router
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.initSkillTreesInfo(this.courseID);
      // await this.getCourseDataFolder();
      this.loading.page = false;
    });
  }

  get Action(): typeof Action {
    return Action;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async initSkillTreesInfo(courseID: number) {
    this.skillTrees = await this.api.getSkillTrees(courseID).toPromise();
    for (const skillTree of this.skillTrees) {
      const tiers = await this.api.getTiersOfSkillTree(skillTree.id, null).toPromise();
      const skills = await this.api.getSkillsOfSkillTree(skillTree.id, null, null, null).toPromise();
      this.skillTreesInfo.push({skillTreeId: skillTree.id, loading: {tiers: false, skills: false}, data: {tiers: [], skills: []}, tiers, skills});

      // Build tables
      this.buildTiersTable(skillTree.id);
      this.buildSkillsTable(skillTree.id);
    }
    if (this.skillTrees.length > 0) this.skillTreeInView = this.skillTrees[0];
  }

  async getCourseDataFolder() {
    this.courseFolder = (await this.api.getCourseById(this.courseID).toPromise()).folder;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Tables ------------------ ***/
  /*** --------------------------------------------- ***/

  tablesInfo: {
    tiers: {
      headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
      tableOptions: any
    },
    skills: {
      headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
      tableOptions: any
    }
  } = {
    tiers: {
      headers: [
        {label: 'Position (sorting)', align: 'middle'},
        {label: 'Tier', align: 'middle'},
        {label: 'Reward', align: 'middle'},
        {label: 'Active', align: 'middle'},
        {label: 'Actions'}
      ],
      tableOptions: {
        order: [[ 0, 'asc' ]], // default order,
        searching: false,
        lengthChange: false,
        paging: false,
        info: false,
        columnDefs: [
          { orderData: 0,   targets: 1 },
          { visible: false, target: 0 },
          { orderable: false, targets: [1, 2, 3, 4] }
        ]
      }
    },
    skills: {
      headers: [
        {label: 'Tier Position (sorting)', align: 'middle'},
        {label: 'Tier', align: 'middle'},
        {label: 'Skill Position (sorting)', align: 'middle'},
        {label: 'Name', align: 'middle'},
        {label: 'Dependencies', align: 'middle'},
        {label: 'Color', align: 'middle'},
        {label: 'Reward (XP)', align: 'middle'},
        {label: 'Collab', align: 'middle'},
        {label: 'Extra', align: 'middle'},
        {label: 'Active', align: 'middle'},
        {label: 'Actions'}
      ],
      tableOptions: {
        order: [[ 0, 'asc' ], [ 2, 'asc' ]], // default order,
        columnDefs: [
          { orderData: 0,   targets: 1 },
          { orderData: 2,   targets: 3 },
          { visible: false, targets: [0, 2] },
          { orderable: false, targets: [1, 3, 4, 5, 6, 7, 8, 9, 10] }
        ]
      }
    }
  }

  buildTiersTable(skillTreeId: number): void {
    this.getSkillTreeInfo(skillTreeId).loading.tiers = true;

    const table: { type: TableDataType, content: any }[][] = []
    const nrTiers = this.getSkillTreeInfo(skillTreeId).tiers.length;
    this.getSkillTreeInfo(skillTreeId).tiers.forEach((tier, index) => {
      table.push([
        {type: TableDataType.NUMBER, content: {value: tier.position}},
        {type: TableDataType.TEXT, content: {text: tier.name}},
        {type: TableDataType.NUMBER, content: {value: tier.reward}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: tier.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [
          Action.EDIT,
          {action: Action.DELETE, disabled: !scopeAllows(ActionScope.ALL_BUT_LAST, nrTiers, index)},
          {action: Action.MOVE_UP, disabled: !scopeAllows(ActionScope.ALL_BUT_FIRST_AND_LAST, nrTiers, index)},
          {action: Action.MOVE_DOWN, disabled: !scopeAllows(ActionScope.ALL_BUT_TWO_LAST, nrTiers, index)},
          Action.EXPORT
        ]}}
      ]);
    });

    this.getSkillTreeInfo(skillTreeId).data.tiers = table;
    this.getSkillTreeInfo(skillTreeId).loading.tiers = false;
  }

  buildSkillsTable(skillTreeId: number): void {
    this.getSkillTreeInfo(skillTreeId).loading.skills = true;

    const tiers: {[tierId: number]: Tier} = {};
    this.getSkillTreeInfo(skillTreeId).tiers.forEach(tier => {
      tiers[tier.id] = tier;
    });

    const table: { type: TableDataType, content: any }[][] = [];
    this.getSkillTreeInfo(skillTreeId).skills.forEach(skill => {
      table.push([
        {type: TableDataType.NUMBER, content: {value: tiers[skill.tierID].position}},
        {type: TableDataType.TEXT, content: {text: tiers[skill.tierID].name}},
        {type: TableDataType.NUMBER, content: {value: skill.position}},
        {type: TableDataType.TEXT, content: {text: skill.name}},
        {type: TableDataType.CUSTOM, content: {html: skill.dependencies.map(dep => '<p class="prose text-sm text-center">' + this.getComboText(dep) + '</p>').join('')}},
        {type: TableDataType.COLOR, content: {color: skill.color, colorLabel: skill.color}},
        {type: TableDataType.NUMBER, content: {value: tiers[skill.tierID].reward, valueFormat: 'default'}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isCollab', toggleValue: skill.isCollab}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isExtra', toggleValue: skill.isExtra}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: skill.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.VIEW, Action.DUPLICATE, Action.EDIT, Action.DELETE, Action.MOVE_UP, Action.MOVE_DOWN, Action.EXPORT]}}
      ]);
    });

    this.getSkillTreeInfo(skillTreeId).data.skills = table;
    this.getSkillTreeInfo(skillTreeId).loading.skills = false;
  }

  doAction(table: 'tiers' | 'skills', action: string) {
    // if (table === 'used' && action === 'Add participation') {
    //   this.tables.qrUsed.mode = 'add';
    //   this.tables.qrUsed.participationToManage = this.initParticipationToManage();
    //   ModalService.openModal('participation-manage');
    //   await this.getExtraInfo();
    // }
  }

  doActionOnTable(table: 'tiers' | 'skills', action: string, row: number, col: number, value?: any): void {
    if (action === 'value changed') {
      // if (col === 7) this.toggleActive(userToActOn);
      // else if (col === 8) this.toggleAdmin(userToActOn);

    } else if (table === 'skills' && action === Action.VIEW) {
      const skillToActOn = this.getSkillTreeInfo(this.skillTreeInView.id).skills[row];
      this.goToSkillPage(skillToActOn);

    } else if (action === Action.EDIT) {
      // this.mode = 'edit';
      // this.userToManage = this.initUserToManage(userToActOn);
      // ModalService.openModal('manage');

    } else if (action === Action.DELETE) {
      // this.userToDelete = userToActOn;
      // ModalService.openModal('delete-verification');

    } else if (action === Action.EXPORT) {
      // this.exportUsers([userToActOn]);
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  createSkill(): void {
    // this.loading = true;
    // this.api.createSkill(this.courseID, this.newSkill)
    //   .pipe( finalize(() => {
    //     this.isSkillModalOpen = false;
    //     this.clearObject(this.newSkill);
    //     this.selectedDependency1 = null;
    //     this.selectedDependency2 = null;
    //     this.infoSelected = null;
    //     this.loading = false;
    //   }) )
    //   .subscribe(async () => await this.getSkillTreesInfo(this.courseID))
  }

  editSkill(): void {
    // this.loading = true;
    // this.newSkill['id'] = this.skillToEdit.id;
    //
    // this.api.editSkill(this.courseID, this.newSkill)
    //   .pipe( finalize(() => {
    //     this.isSkillModalOpen = false;
    //     this.clearObject(this.newSkill);
    //     this.selectedDependency1 = null;
    //     this.selectedDependency2 = null;
    //     this.skillToEdit = null;
    //     this.infoSelected = null;
    //     this.loading = false;
    //   }) )
    //   .subscribe(async () => await this.getSkillTreesInfo(this.courseID))
  }

  deleteSkill(): void {
    // this.loading = true;
    // this.api.deleteSkill(this.courseID, this.skillToDelete.id)
    //   .pipe( finalize(() => {
    //     this.isDeleteVerificationModalOpen = false;
    //     this.clearObject(this.newSkill);
    //     this.skillToDelete = null;
    //     this.infoSelected = null;
    //     this.loading = false;
    //   }) )
    //   .subscribe(async () => await this.getSkillTreesInfo(this.courseID))
  }

  toggleSkill(skillId: number, param: string) {
    // this.loading = true;
    //
    // const skill = this.getSkill(skillId);
    // skill[param] = !skill[param];
    // this.api.editSkill(this.courseID, skill)
    //   .pipe( finalize(() => this.loading = false) )
    //   .subscribe(async () => {})
  }

  moveSkill(skill: Skill, direction: number) {
    // this.loading = true;
    //
    // skill.position += -direction;
    // this.api.editSkill(this.courseID, skill)
    //   .pipe( finalize(() => this.loading = false) )
    //   .subscribe(async () => await this.getSkillTreesInfo(this.courseID))
  }

  exportAllSkills() {
    // TODO
  }

  addSkillDependency(dep1: Skill, dep2: Skill) {
    // if (!this.newSkill.dependencies) this.newSkill.dependencies = [];
    // this.newSkill.dependencies.push([dep1, dep2]);
  }

  removeSkillDependency(dependency: Skill[]) {
    // this.newSkill.dependencies = this.newSkill.dependencies.filter(combo => {
    //   return !(combo[0].id === dependency[0].id && combo[1].id === dependency[1].id);
    // });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initEditSkill(skill: Skill): void {
    // this.newSkill = {
    //   tierID: skill.tierID,
    //   name: skill.name,
    //   color: skill.color,
    //   page: skill.page,
    //   isCollab: skill.isCollab,
    //   isExtra: skill.isExtra,
    //   isActive: skill.isActive,
    //   position: skill.position,
    //   ruleID: skill.ruleID,
    //   dependencies: skill.dependencies
    // };
    // this.skillToEdit = skill;
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

  getTier(tierId: number): Tier {
    return null;
    // for (const info of this.getSkillTreeInfo()) {
    //   for (const tier of info.tiers) {
    //     if (tier.id == tierId) return tier;
    //   }
    // }
    // return null;
  }

  getWildcardTier(tiers: Tier[]): Tier {
    let wildcardTier: Tier = null;
    for (const tier of tiers) {
      if (tier.isWildcard()) wildcardTier = tier;
    }
    return wildcardTier;
  }

  getSkill(skillId: number): Skill {
    return null;
    // for (const info of this.getSkillTreeInfo()) {
    //   for (const skill of info.skills) {
    //     if (skill.id == skillId) return skill;
    //   }
    // }
    // return null;
  }

  getSkillsOfTier(tierId: number): Skill[] {
    return [];
    // const skills = [];
    // for (const info of this.getSkillTreeInfo()) {
    //   for (const skill of info.skills) {
    //     if (skill.tierID === tierId) skills.push(skill);
    //   }
    // }
    // return skills;
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
    return []; // FIXME
    // const filteredSkills = this.infoSelected.skills
    //   .filter(skill => skill.id !== skillID && skill.isActive &&
    //     this.getTier(skill.tierID).position === tier.position - 1)
    //   .sort((a, b) => a.name.localeCompare(b.name));
    //
    //   if (addWildcardOption(this.infoSelected.tiers, this.infoSelected.skills))
    //     filteredSkills.push(Skill.getWildcard(this.getWildcardTier(this.infoSelected.tiers).id));
    //
    //   return filteredSkills;
    //
    //   function addWildcardOption(tiers: Tier[], skills: Skill[]): boolean {
    //     let wildcardTier: Tier = null;
    //     for (const tier of tiers) {
    //       if (tier.isWildcard()) wildcardTier = tier;
    //     }
    //     if (!wildcardTier.isActive) return false;
    //
    //     let hasWildcardSkill = false;
    //     for (const skill of skills) {
    //       if (skill.tierID === wildcardTier.id) {
    //         hasWildcardSkill = true;
    //         break;
    //       }
    //     }
    //     return hasWildcardSkill;
    //   }
    }

  filterSkillsByTier(skills: Skill[], tierID: number): Skill[] {
    return skills.filter(skill => skill.tierID === tierID && skill.isActive);
  }

  initTextEditor() {
    // setTimeout(() => this.skillModalRendered.next(), 0);
    // return [];
  }

  goToSkillPage(skill: Skill) {
    this.router.navigate(['./skills', skill.id, true], {relativeTo: this.route.parent.parent})
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
