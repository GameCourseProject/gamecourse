import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {NgForm} from "@angular/forms";

import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";

import {SkillTree} from 'src/app/_domain/modules/config/personalized-config/skills/skill-tree';
import {Tier} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/tier";
import {Skill} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/skill";
import {Action, ActionScope, scopeAllows} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../../../../_components/tables/table-data/table-data.component";
import {clearEmptyValues} from "../../../../../../../../../_utils/misc/misc";
import {ModalService} from "../../../../../../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../../../../../../_services/alert.service";

import * as _ from 'lodash';
import {DownloadManager} from "../../../../../../../../../_utils/download/download-manager";
import {ResourceManager} from "../../../../../../../../../_utils/resources/resource-manager";

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

  VCEnabled: boolean;
  VCName: string;

  skillTrees: SkillTree[];
  skillTreesInfo: {
    skillTreeId: number,
    loading: {tiers: boolean, skills: boolean},
    data: {tiers: {type: TableDataType, content: any}[][], skills: {type: TableDataType, content: any}[][]},
    tiers: Tier[],
    skills: Skill[]
  }[] = [];
  skillTreeInView: SkillTree;

  tierMode: 'create' | 'edit';
  tierToManage: TierManageData = this.initTierToManage();
  tierToDelete: Tier;
  @ViewChild('fTier', { static: false }) fTier: NgForm;

  skillMode: 'create' | 'edit';
  skillPageMode: 'editor' | 'preview';
  skillToManage: SkillManageData = this.initSkillToManage();
  skillToDelete: Skill;
  @ViewChild('fSkill', { static: false }) fSkill: NgForm;

  dependency: string[];
  @ViewChild('fDependency', { static: false }) fDependency: NgForm;

  // IMPORT
  skillsImportData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fSkillsImport', { static: false }) fSkillsImport: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public router: Router
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);

      await this.isVCEnabled();
      if (this.VCEnabled) await this.getVCName();

      await this.initSkillTreesInfo(this.courseID);
      await this.getCourseDataFolder();

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
    this.skillTreesInfo = [];
    this.skillTrees = await this.api.getSkillTrees(courseID).toPromise();
    for (const skillTree of this.skillTrees) {
      // Get info
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

  async isVCEnabled() {
    this.VCEnabled = (await this.api.getCourseModuleById(this.courseID, ApiHttpService.VIRTUAL_CURRENCY).toPromise()).enabled;
  }

  async getVCName() {
    this.VCName = (await this.api.getVCName(this.courseID).toPromise());
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
        {label: 'Reward (XP)', align: 'middle'},
        {label: 'Active', align: 'middle'},
        {label: 'Actions'}
      ],
      tableOptions: {
        order: [[ 0, 'asc' ]], // default order,
        columnDefs: [
          { orderData: 0,   targets: 1 },
          { orderable: false, targets: [0, 1, 2, 3, 4] }
        ]
      }
    },
    skills: {
      headers: [
        {label: 'Tier Position (sorting)', align: 'middle'},
        {label: 'Skill Position (sorting)', align: 'middle'},
        {label: 'Skill', align: 'left'},
        {label: 'Dependencies', align: 'middle'},
        {label: 'Reward (XP)', align: 'middle'},
        {label: 'Collab', align: 'middle'},
        {label: 'Extra', align: 'middle'},
        {label: 'Active', align: 'middle'},
        {label: 'View Rule'},
        {label: 'Actions'}
      ],
      tableOptions: {
        order: [[ 0, 'asc' ], [ 1, 'asc' ]], // default order,
        columnDefs: [
          { orderData: 0, targets: 2 },
          { orderData: 1, targets: 2 },
          { orderable: false, targets: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] }
        ]
      }
    }
  }

  buildTiersTable(skillTreeId: number): void {
    this.getSkillTreeInfo(skillTreeId).loading.tiers = true;

    // Add tier cost if Virtual Currency enabled
    if (this.VCEnabled) {
      this.tablesInfo.tiers.headers.splice(3, 0,  {label: 'Cost (' + this.VCName + ')', align: 'middle'});
      this.tablesInfo.tiers.tableOptions['columnDefs'][1]['targets'].push(5);
    }

    const table: { type: TableDataType, content: any }[][] = []
    const nrTiers = this.getSkillTreeInfo(skillTreeId).tiers.length;
    this.getSkillTreeInfo(skillTreeId).tiers.forEach((tier, index) => {
      const row: { type: TableDataType, content: any }[] = [
        {type: TableDataType.NUMBER, content: {value: tier.position}},
        {type: TableDataType.TEXT, content: {text: tier.name, classList: 'font-semibold'}},
        {type: TableDataType.NUMBER, content: {value: tier.reward}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: tier.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [
              Action.EDIT,
              {action: Action.DELETE, disabled: !scopeAllows(ActionScope.ALL_BUT_LAST, nrTiers, index)},
              {action: Action.MOVE_UP, disabled: !scopeAllows(ActionScope.ALL_BUT_FIRST_AND_LAST, nrTiers, index)},
              {action: Action.MOVE_DOWN, disabled: !scopeAllows(ActionScope.ALL_BUT_TWO_LAST, nrTiers, index)},
              Action.EXPORT
            ]}}
      ];

      // Add tier cost if Virtual Currency enabled
      if (this.VCEnabled) {
        row.splice(3, 0, {
          type: TableDataType.TEXT, content: {
            text: tier.costType.capitalize() + ': ' + tier.cost +
              (tier.costType === 'incremental' ? (' + ' + tier.increment + ' x #attempts (rating >= ' + tier.minRating + ')')
                : tier.costType === 'exponential' ? (' x 2 ^ (#attempts (rating >= ' + tier.minRating + '))')
                : '')
          }
        });
      }

      table.push(row);
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
      const nrSkillsInTier = this.getSkillTreeInfo(skillTreeId).skills.filter(s => s.tierID === skill.tierID).length;
      table.push([
        {type: TableDataType.NUMBER, content: {value: tiers[skill.tierID].position}},
        {type: TableDataType.NUMBER, content: {value: skill.position}},
        {type: TableDataType.CUSTOM, content: {html: '<div class="!text-left !text-start !justify-start">' +
              '<div class="flex items-center space-x-3">' +
              '<div class="avatar">' +
              '<div class="mask mask-circle w-9 h-9 !flex !items-center !justify-center bg-base-content bg-opacity-30" style="background-color: ' + skill.color + '">' +
              '<span class="text-base-100">' + skill.name[0] + '</span>' +
              '</div>' +
              '</div>' +
              '<div class="prose text-sm">' +
              '<h4>' + skill.name + '</h4>' +
              '<span class="opacity-60">Tier: ' + tiers[skill.tierID].name + '</span>' +
              '</div>' +
              '</div>' +
              '</div>', searchBy: skill.name + ' ' + tiers[skill.tierID].name}},
        {type: TableDataType.CUSTOM, content: {html: skill.dependencies.map(dep => '<p class="prose text-sm text-center">' + this.getComboText(dep) + '</p>').join(''),
            searchBy: skill.dependencies.map(dep => dep.map(s => s.name).join(' ')).join(' ')}},
        {type: TableDataType.NUMBER, content: {value: tiers[skill.tierID].reward, valueFormat: 'default'}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isCollab', toggleValue: skill.isCollab}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isExtra', toggleValue: skill.isExtra}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: skill.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.VIEW_RULE]}},
        {type: TableDataType.ACTIONS, content: {actions: [
          {action: Action.VIEW, disabled: !skill.page},
          Action.EDIT,
          Action.DELETE,
          {action: Action.MOVE_UP, disabled: !scopeAllows(ActionScope.ALL_BUT_FIRST, nrSkillsInTier, skill.position)},
          {action: Action.MOVE_DOWN, disabled: !scopeAllows(ActionScope.ALL_BUT_LAST, nrSkillsInTier, skill.position)},
          Action.EXPORT
        ]}}
      ]);
    });

    this.getSkillTreeInfo(skillTreeId).data.skills = table;
    this.getSkillTreeInfo(skillTreeId).loading.skills = false;
  }

  async doActionOnTable(table: 'tiers' | 'skills', action: string, row: number, col: number, value?: any): Promise<void> {
    if (table === 'tiers') { // TIERS
      const tierToActOn = this.getSkillTreeInfo(this.skillTreeInView.id).tiers[row];

      if (action === 'value changed') {
        this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = true;

        tierToActOn.isActive = value;
        await this.api.editTier(this.courseID, clearEmptyValues(tierToActOn)).toPromise();

        if (!tierToActOn.isActive) {
          this.getSkillTreeInfo(this.skillTreeInView.id).skills = await this.api.getSkillsOfSkillTree(this.skillTreeInView.id, null, null, null).toPromise();
          this.buildSkillsTable(this.skillTreeInView.id);
        }

        this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = false;

      } else if (action === Action.EDIT) {
        this.tierMode = 'edit';
        this.tierToManage = this.initTierToManage(tierToActOn);
        ModalService.openModal('tier-manage');

      } else if (action === Action.DELETE) {
        this.tierToDelete = tierToActOn;
        ModalService.openModal('tier-delete-verification');

      } else if (action === Action.MOVE_UP || action === Action.MOVE_DOWN) {
        this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = true;

        tierToActOn.position += action === Action.MOVE_UP ? -1 : 1;
        await this.api.editTier(this.courseID, clearEmptyValues(tierToActOn)).toPromise();
        this.getSkillTreeInfo(this.skillTreeInView.id).tiers = await this.api.getTiersOfSkillTree(this.skillTreeInView.id, null).toPromise();
        this.buildTiersTable(this.skillTreeInView.id);

        this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = false;

      } else if (action === Action.EXPORT){
        const contents = await this.api.exportModuleItems(this.courseID, "Skills", "Tiers", [tierToActOn.id]).toPromise();
        DownloadManager.downloadAsZip(contents.path, this.api, this.courseID);
      }

    } else if (table === 'skills') { // SKILLS
      const skillToActOn = this.getSkillTreeInfo(this.skillTreeInView.id).skills[row];

      if (action === 'value changed') {
        this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = true;

        if (col === 7) skillToActOn.isCollab = value;
        else if (col === 8) skillToActOn.isExtra = value;
        else if (col === 9) skillToActOn.isActive = value;

        await this.api.editSkill(this.courseID, clearEmptyValues(skillToActOn)).toPromise();

        this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = false;

      } else if (table === 'skills' && action === Action.VIEW) {
        const skillToActOn = this.getSkillTreeInfo(this.skillTreeInView.id).skills[row];
        this.goToSkillPage(skillToActOn);

      } else if (action === Action.EDIT) {
        this.skillMode = 'edit';
        this.skillToManage = this.initSkillToManage(skillToActOn);
        this.skillPageMode = 'editor';

        ModalService.openModal('skill-manage');

      } else if (action === Action.DELETE) {
        this.skillToDelete = skillToActOn;
        ModalService.openModal('skill-delete-verification');

      } else if (action === Action.MOVE_UP || action === Action.MOVE_DOWN) {
        this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = true;

        skillToActOn.position += action === Action.MOVE_UP ? -1 : 1;
        await this.api.editSkill(this.courseID, clearEmptyValues(skillToActOn)).toPromise();
        this.getSkillTreeInfo(this.skillTreeInView.id).skills = await this.api.getSkillsOfSkillTree(this.skillTreeInView.id, null, null, null).toPromise();
        this.buildSkillsTable(this.skillTreeInView.id);

        this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = false;

      } else if (action === Action.EXPORT){
        const contents = await this.api.exportModuleItems(this.courseID, "Skills", "Skills", [skillToActOn.id]).toPromise();
        DownloadManager.downloadAsZip(contents.path, this.api, this.courseID);
        // this.exportUsers([userToActOn]);

      } else if (action === Action.VIEW_RULE) {
        let sectionID = await this.api.getSectionIdByModule(this.courseID, "Skills").toPromise();
        const ruleLink = './rule-system/sections/' + sectionID + "/rules/" + skillToActOn.ruleID;
        this.router.navigate([ruleLink], {relativeTo: this.route.parent});

      }
    }
  }

  closeDiscardModal(){
    ModalService.closeModal('skill-delete-verification');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async doAction(table: 'tiers' | 'skills', action: string) {
    if (table === 'tiers') { // TIERS
      if (action === Action.IMPORT) {
        ModalService.openModal('tier-import');

      } else if (action === Action.EXPORT) {
        let tiers = this.skillTreesInfo.map(info => { return info.tiers.map(tier => { return tier.id })});
        const contents = await this.api.exportModuleItems(this.courseID, "Skills", "Tiers", tiers.flat()).toPromise();
        DownloadManager.downloadAsZip(contents.path, this.api, this.courseID);
        // this.exportTiers(this.getSkillTreeInfo(this.skillTreeInView.id).tiers);

      } else if (action === 'Create tier') {
        this.tierMode = 'create';
        this.tierToManage = this.initTierToManage();
        ModalService.openModal('tier-manage');
      }

    } else if (table === 'skills') { // SKILLS
      if (action === Action.IMPORT) {
        ModalService.openModal('skill-import');

      } else if (action === Action.EXPORT) {
        let skills = this.skillTreesInfo.map(info => { return info.skills.map(skill => { return skill.id })});
        const contents = await this.api.exportModuleItems(this.courseID, "Skills", "Skills", skills.flat()).toPromise();
        DownloadManager.downloadAsZip(contents.path, this.api, this.courseID);
        // this.exportSkills(this.getSkillTreeInfo(this.skillTreeInView.id).skills);

      } else if (action === 'Create skill') {
        this.skillMode = 'create';
        this.skillToManage = this.initSkillToManage();
        this.skillPageMode = 'editor';
        ModalService.openModal('skill-manage');
      }
    }
  }


  // TIERS

  async createTier(): Promise<void> {
    if (this.fTier.valid) {
      this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = true;

      await this.api.createTier(this.courseID, this.skillTreeInView.id, clearEmptyValues(this.tierToManage)).toPromise();
      this.getSkillTreeInfo(this.skillTreeInView.id).tiers = await this.api.getTiersOfSkillTree(this.skillTreeInView.id, null).toPromise();
      this.buildTiersTable(this.skillTreeInView.id);

      this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = false;
      ModalService.closeModal('tier-manage');
      AlertService.showAlert(AlertType.SUCCESS, 'New tier added: ' + this.tierToManage.name);
      this.resetTierToManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editTier(): Promise<void> {
    if (this.fTier.valid) {
      this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = true;

      const tierToEdit = this.getSkillTreeInfo(this.skillTreeInView.id).tiers.find(el => el.id === this.tierToManage.id);
      tierToEdit.name = this.tierToManage.name;
      tierToEdit.reward = this.tierToManage.reward;
      tierToEdit.costType = this.tierToManage.costType;
      tierToEdit.cost = this.tierToManage.cost;
      tierToEdit.increment = this.tierToManage.increment;
      tierToEdit.minRating = this.tierToManage.minRating;
      await this.api.editTier(this.courseID, clearEmptyValues(tierToEdit)).toPromise();
      this.getSkillTreeInfo(this.skillTreeInView.id).tiers = await this.api.getTiersOfSkillTree(this.skillTreeInView.id, null).toPromise();
      this.buildTiersTable(this.skillTreeInView.id);

      this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = false;
      ModalService.closeModal('tier-manage');
      AlertService.showAlert(AlertType.SUCCESS, 'Tier \'' + this.tierToManage.name + '\' edited');
      this.resetTierToManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async deleteTier(tier: Tier): Promise<void> {
    this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = true;

    await this.api.deleteTier(this.courseID, tier.id).toPromise();
    this.getSkillTreeInfo(this.skillTreeInView.id).tiers = await this.api.getTiersOfSkillTree(this.skillTreeInView.id, null).toPromise();
    this.buildTiersTable(this.skillTreeInView.id);

    this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = false;
    ModalService.closeModal('tier-delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'Tier \'' + tier.name + '\' deleted');
  }

  async importTiers(){
    if (this.fSkillsImport.valid) {
      this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = true;

      await this.api.setSkillTreeInView(this.courseID, this.skillTreeInView.id, true).toPromise();
      const file = await ResourceManager.getBase64(this.skillsImportData.file);
      const nrSkillsImported = await this.api.importModuleItems(this.courseID, "Skills", "Tiers", file, this.skillsImportData.replace).toPromise();

      await this.api.setSkillTreeInView(this.courseID, this.skillTreeInView.id, false).toPromise();
      this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = false;
      ModalService.closeModal('tier-import');
      AlertService.showAlert(AlertType.SUCCESS, nrSkillsImported + ' Tier' + (nrSkillsImported != 1 ? 's' : '') + ' imported');

      this.buildTiersTable(this.skillTreeInView.id);

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  // SKILLS

  async createSkill(): Promise<void> {
    if (this.fSkill.valid) {
      this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = true;

      await this.api.createSkill(this.courseID, clearEmptyValues(this.skillToManage)).toPromise();
      this.getSkillTreeInfo(this.skillTreeInView.id).skills = await this.api.getSkillsOfSkillTree(this.skillTreeInView.id, null, null, null).toPromise();
      this.buildSkillsTable(this.skillTreeInView.id);

      this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = false;
      ModalService.closeModal('skill-manage');
      AlertService.showAlert(AlertType.SUCCESS, 'New skill added: ' + this.skillToManage.name);
      this.resetSkillToManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editSkill(): Promise<void> {
    if (this.fSkill.valid) {
      this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = true;

      const skillToEdit = this.getSkillTreeInfo(this.skillTreeInView.id).skills.find(el => el.id === this.skillToManage.id);
      skillToEdit.tierID = parseInt(this.skillToManage.tierID.substring(3));
      skillToEdit.name = this.skillToManage.name;
      skillToEdit.color = this.skillToManage.color;
      skillToEdit.dependencies = this.skillToManage.dependencies;
      skillToEdit.page = this.skillToManage.page;

      await this.api.editSkill(this.courseID, clearEmptyValues(skillToEdit)).toPromise();
      this.getSkillTreeInfo(this.skillTreeInView.id).skills = await this.api.getSkillsOfSkillTree(this.skillTreeInView.id, null, null, null).toPromise();
      this.buildSkillsTable(this.skillTreeInView.id);

      this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = false;
      ModalService.closeModal('skill-manage');
      AlertService.showAlert(AlertType.SUCCESS, 'Skill \'' + this.skillToManage.name + '\' edited');
      this.resetSkillToManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async deleteSkill(skill: Skill): Promise<void> {
    this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = true;

    await this.api.deleteSkill(this.courseID, skill.id).toPromise();
    this.getSkillTreeInfo(this.skillTreeInView.id).skills = await this.api.getSkillsOfSkillTree(this.skillTreeInView.id, null, null, null).toPromise();
    this.buildSkillsTable(this.skillTreeInView.id);

    this.getSkillTreeInfo(this.skillTreeInView.id).loading.skills = false;
    ModalService.closeModal('skill-delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'Skill \'' + skill.name + '\' deleted');
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

  async importSkills() {
    if (this.fSkillsImport.valid) {
      this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = true;

      const file = await ResourceManager.getBase64(this.skillsImportData.file);
      const nrSkillsImported = await this.api.importModuleItems(this.courseID, "Skills", "Tiers", file, this.skillsImportData.replace).toPromise();


      this.getSkillTreeInfo(this.skillTreeInView.id).loading.tiers = false;
      ModalService.closeModal('tier-import');
      AlertService.showAlert(AlertType.SUCCESS, nrSkillsImported + ' ' + Tier + (nrSkillsImported != 1 ? 's' : '') + ' imported');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');

  }

  resetImport(){
    this.skillsImportData = {file: null, replace: true};
    this.fSkillsImport.resetForm();
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

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


  // TIERS

  initTierToManage(tier?: Tier): TierManageData {
    const tierData: TierManageData = {
      name: tier?.name ?? null,
      reward: tier?.reward ?? null,
      costType: tier?.costType ?? 'fixed',
      cost: tier?.cost ?? 0,
      increment: tier?.increment ?? 0,
      minRating: tier?.minRating ?? 3
    };
    if (tier) tierData.id = tier.id;
    return tierData;
  }

  resetTierToManage() {
    this.tierMode = null;
    this.tierToManage = this.initTierToManage();
    this.fTier.resetForm();
  }


  // SKILLS

  initSkillToManage(skill?: Skill): SkillManageData {
    const skillData: SkillManageData = {
      tierID: skill?.tierID ? 'id-' + skill.tierID : null,
      name: skill?.name ?? null,
      color: skill?.color ?? null,
      page: skill?.page ?? null,
      dependencies: skill?.dependencies ? _.cloneDeep(skill.dependencies) : []
    };
    if (skill) {
      skillData.id = skill.id;
      skillData.ruleID = skill.ruleID;
    }
    return skillData;
  }

  resetSkillToManage() {
    this.skillMode = null;
    this.skillToManage = this.initSkillToManage();
    this.fSkill.resetForm();
  }

  getTierOptions(): {value: string, text: string}[] {
    return this.getSkillTreeInfo(this.skillTreeInView.id).tiers.map(tier => {
      return {value: 'id-' + tier.id, text: tier.name};
    });
  }

  getDependencyOptions(): ({value: string, text: string, innerHTML: string} | {label: string, options: {value: string, text: string}[]})[] {
    const skillTreeInfo = this.getSkillTreeInfo(this.skillTreeInView.id);
    const options: ({value: string, text: string, innerHTML: string} | {label: string, options: {value: string, text: string}[]})[] = [];

    const skillTier = skillTreeInfo.tiers.find(tier => tier.id === parseInt(this.skillToManage.tierID.substring(3)));
    skillTreeInfo.tiers.forEach(tier => {
      if (!tier.isWildcard() && tier.position < skillTier.position) {
        const tierSkills = skillTreeInfo.skills.filter(skill => skill.tierID === tier.id);
        const tierOptions = tierSkills.map(skill => {
          return {value: 'id-' + skill.id, text: skill.name};
        });
        options.push({label: tier.name, options: tierOptions});
      }
    });

    if (addWildcardOption(skillTreeInfo.tiers, skillTreeInfo.skills))
      options.unshift({value: 'id-0', text: Tier.WILDCARD, innerHTML: '<span class="text-secondary">' + Tier.WILDCARD + '</span>'})

    return options;

    function addWildcardOption(tiers: Tier[], skills: Skill[]): boolean {
      const wildcardTier: Tier = tiers.find(tier => tier.isWildcard());
      if (!wildcardTier.isActive) return false;
      return skills.filter(skill => skill.tierID === wildcardTier.id && skill.isActive).length > 0;
    }
  }

  addDependency() {
    if (this.fDependency.valid) {
      const dep = getDependency(this.dependency, this.getSkillTreeInfo(this.skillTreeInView.id).skills);
      this.skillToManage.dependencies.push(dep);
      this.fDependency.resetForm();

    } else AlertService.showAlert(AlertType.ERROR, 'No skills selected for dependency');

    function getDependency(dependency: string[], skills: Skill[]): Skill[] {
      return dependency.map(value => {
        const skillID = parseInt(value.substring(3));
        if (skillID !== 0) return skills.find(skill => skill.id === skillID);
        else return new Skill(0, 0, Tier.WILDCARD, false, false, false, 0, 0, []);
      })
    }
  }

  removeDependency(index: number) {
    this.skillToManage.dependencies.splice(index, 1);
  }

  getComboText(combo: Skill[]): string {
    let str = '';
    for (let i = 0; i < combo.length; i++) {
      const skill = combo[i];
      str += skill.name + (i != combo.length - 1 ? ' + ' : '');
    }
    return str;
  }

  filterSkillsByTier(skills: Skill[], tierID: number): Skill[] {
    return skills.filter(skill => skill.tierID === tierID && skill.isActive);
  }

  showDependencies(): boolean {
    const skillTier = this.getSkillTreeInfo(this.skillTreeInView.id).tiers.find(tier => tier.id === parseInt(this.skillToManage.tierID.substring(3)));
    if (skillTier.position === 0 || skillTier.isWildcard()) return false;
    return true;
  }

  goToSkillPage(skill: Skill) {
    this.router.navigate(['./skills', skill.id, 'preview'], {relativeTo: this.route.parent.parent})
  }

}

export interface TierManageData {
  id?: number,
  name: string,
  reward: number,
  costType: 'fixed' | 'incremental' | 'exponential',
  cost: number,
  increment: number,
  minRating: number
}

export interface SkillManageData {
  id?: number,
  ruleID?: number,
  tierID: string,
  name: string,
  color: string,
  page: string,
  dependencies: Skill[][]
}
