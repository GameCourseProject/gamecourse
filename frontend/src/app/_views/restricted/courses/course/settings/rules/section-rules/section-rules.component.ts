import {Component, OnInit, ViewChild} from "@angular/core";
import {RuleSection} from "../../../../../../../_domain/rules/RuleSection";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {Action} from "../../../../../../../_domain/modules/config/Action";
import {ModalService} from "../../../../../../../_services/modal.service";
import {Course} from "../../../../../../../_domain/courses/course";
import {Rule} from "../../../../../../../_domain/rules/rule";
import {DownloadManager} from "../../../../../../../_utils/download/download-manager";
import {RuleManageData, initRuleToManage} from "./rules/rules.component";

import * as _ from "lodash";
import {ResourceManager} from "../../../../../../../_utils/resources/resource-manager";
import {ThemingService} from "../../../../../../../_services/theming/theming.service";

import {TableDataType} from "../../../../../../../_components/tables/table-data/table-data.component";

export { RuleManageData } from "./rules/rules.component";

@Component({
  selector: 'app-rule-sections-management',
  templateUrl: './section-rules.component.html',
})
export class SectionRulesComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    table: false
  };

  course: Course;                 // Specific course in which rule system is being manipulated
  sectionRules: Rule[];           // Rules of specific section
  section: RuleSection;           // Section being manipulated

  ruleToManage: RuleManageData;   // rule being manipulated in table
  removeMode: boolean = false;    // to show when removing a rule

  @ViewChild('r', {static: false}) r: NgForm;                       // rule form

  // Importing action
  importData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  // TABLE
  table: {
    headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
    data: {type: TableDataType, content: any}[][],
    options: any,
    showTable: boolean;
  } = {
    headers: [
      {label: 'Execution Order', align: 'left'},
      {label: 'Name', align: 'left'},
      {label: 'Tags', align: 'middle'},
      {label: 'Active', align: 'middle'},
      {label: 'Actions'}],
    data: null,
    options: {
      order: [ 0, 'asc' ],        // default order -> column 0 ascendant
      columnDefs: [
        { type: 'natural', targets: [0,1] },
        { searchable: false, targets: [2,3] },
        { orderable: false, targets: [2,3] }
      ]
    },
    showTable: false
  }

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    private themeService: ThemingService
  ) { }

  get Action(): typeof Action {
    return Action;
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async ngOnInit() {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      this.route.params.subscribe(async childParams => {
        const sectionID = childParams.sectionId;
        await this.getSection(this.course.id, sectionID);
        await this.getSectionRules(sectionID);
        await this.buildTable();
        this.loading.page = false;
      })
    });
  }

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }


  async getSection(courseID: number, sectionID: number) {
    this.section = await this.api.getSectionById(courseID, sectionID).toPromise();
  }

  async getSectionRules(sectionID: number){
    this.sectionRules = (await this.api.getRulesOfSection(this.course.id, sectionID).toPromise()).sort(function (a, b) {
      return a.position - b.position; });
  }

  async buildTable(): Promise<void> {
    this.loading.table = true;

    this.table.showTable = false;
    setTimeout(() => this.table.showTable = true, 0);

    const data: { type: TableDataType; content: any }[][] = [];

    this.sectionRules.forEach(rule => {
      data.push([
        {type: TableDataType.NUMBER, content: {value: rule.position, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: rule.name}},
        {type: TableDataType.CUSTOM, content: {html: '<div class="flex flex-col gap-2">' +
              rule.tags.sort((a,b) => a.name.localeCompare(b.name))
                .map(tag => '<div class="badge badge-md badge-' + this.themeService.hexaToColor(tag.color) + '">' + tag.name + '</div>').join('') +
              '</div>', searchBy: rule.tags.map(tag => tag.name).join(' ') }},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: rule.isActive, toggleDisabled: this.section.name === 'Graveyard' }},
        {type: TableDataType.ACTIONS, content: {actions: [
              Action.EDIT,
              {action: 'Duplicate', icon: 'tabler-copy', color: 'primary', disabled: this.section.name === 'Graveyard'},
              {action: 'Increase priority', icon: 'tabler-arrow-narrow-up', color: 'primary', disabled: rule.position === 0 || this.section.name === 'Graveyard' },
              {action: 'Decrease priority', icon: 'tabler-arrow-narrow-down', color: 'primary', disabled: rule.position === this.sectionRules.length - 1 || this.section.name === 'Graveyard'},
              Action.REMOVE,
              Action.EXPORT]}
        }
      ]);
    });

    this.table.data = _.cloneDeep(data);
    this.loading.table = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareModal(action: string){
    if (action === Action.IMPORT + ' rule(s)'){
      ModalService.openModal('import');

    } else if (action === Action.EXPORT) {
      let rules = await this.api.getRulesOfSection(this.course.id, this.section.id).toPromise();
      await this.exportRules(rules);

    } else if (action === 'Create rule') {
      await this.router.navigate(['rule-system/sections/' + this.section.id + '/new-rule'], {relativeTo: this.route.parent});

    } /*else if (action === 'add tag'){
      ModalService.openModal(action);
    }*/
  }

  async doAction(action: string): Promise<void>{
     if (action === 'remove rule') {
      this.loading.action = true;
      await this.deleteRule();

      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + this.ruleToManage.name + '\' removed');
      ModalService.closeModal('delete-rule');
      this.resetRuleManage();
      this.loading.action = false;
    } else if (action === 'close-uncompleted-rule'){
       this.loading.action = true;
       await this.buildTable();
       ModalService.closeModal('uncompleted-rule');
       this.loading.action = false;
     }
  }

  async importRules(): Promise<void> {
    if (this.fImport.valid){
      this.loading.action = true;

      const file = await ResourceManager.getText(this.importData.file);
      const nrRulesImported = await this.api.importRules(this.course.id, this.section.id, file, this.importData.replace).toPromise();

      this.sectionRules = await this.api.getRulesOfSection(this.course.id, this.section.id).toPromise();
      await this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('import');
      this.resetImport();
      AlertService.showAlert(AlertType.SUCCESS, nrRulesImported + ' rule' + (nrRulesImported != 1 ? 's' : '') + ' imported');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async exportRules(rules: Rule[]): Promise<void> {
    if (rules.length === 0)
      AlertService.showAlert(AlertType.WARNING, 'There are no rules in the section to export');

    else {
      this.loading.action = true;

      let ruleIds = rules.map(rule => rule.id);
      const contents = await this.api.exportRules(this.course.id, ruleIds).toPromise();
      DownloadManager.downloadAsCSV((this.course.short ?? this.course.name) + '-' + (this.section.name) + '-' + (rules.length === 1 ? rules[0].name : 'rules'), contents);

      this.loading.action = false;
    }
  }

  async closeManagement(){
    this.loading.page = true;

    await this.router.navigate(['rule-system'], {relativeTo: this.route.parent});

    this.loading.page = false;

  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  async doActionOnTable(action: string, row: number, col: number): Promise<void>{
    let sectionRules = (await this.api.getRulesOfSection(this.course.id, this.section.id).toPromise()).sort(function (a, b) {
      return a.position - b.position; });
    const ruleToActOn = sectionRules[row];
    action = action.toLowerCase();

    if (action === 'value changed rule' && col === 3) {
      if (this.isUncompleted(ruleToActOn)) {
        ModalService.openModal('uncompleted-rule');
      } else await this.toggleActive(ruleToActOn);

    } else if (col === 4){
      if (action === Action.REMOVE) {
        this.ruleToManage = initRuleToManage(this.course.id, this.section.id, ruleToActOn);
        this.removeMode = true;

        ModalService.openModal('delete-rule');

      } else if (action === Action.EDIT) {
        await this.router.navigate(['rule-system/sections/' + this.section.id + '/rules/' + ruleToActOn.id], {relativeTo: this.route.parent});

      } else if ( action === Action.DUPLICATE) {
        await this.duplicateRule(ruleToActOn);

      } else if (action === 'increase priority') {
        const rule = this.sectionRules[row - 1];
        await this.changePriority(ruleToActOn, rule);

      } else if (action === 'decrease priority') {
        const rule = this.sectionRules[row + 1];
        await this.changePriority(ruleToActOn, rule);

      }  else if (action === Action.EXPORT || action === Action.EXPORT + ' all rules') {
        await this.exportRules([ruleToActOn]);
      }
    }
  }

  async deleteRule(): Promise<void> {
    await this.api.deleteRule(this.ruleToManage.section, this.ruleToManage.id).toPromise();
    const index = this.sectionRules.findIndex(el => el.id === this.ruleToManage.id);
    this.sectionRules.removeAtIndex(index);

    await this.getSectionRules(this.section.id);
    await this.buildTable();

    this.removeMode = false;
  }

  async toggleActive(rule: Rule) {
    this.loading.action = true;

    rule.isActive = !rule.isActive;
    await this.api.setCourseRuleActive(rule.id, rule.isActive).toPromise();

    this.loading.action = false;
  }

  async duplicateRule(rule: Rule){
    this.loading.action = true;

    const newRule = await this.api.duplicateRule(rule.id).toPromise();
    this.sectionRules.unshift(newRule);
    await this.buildTable();

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + newRule.name + '\' added');
  }

  async changePriority(rule1: Rule, rule2: Rule){
    this.loading.action = true;

    let auxRule = _.cloneDeep(rule1);
    this.ruleToManage = initRuleToManage(this.course.id, this.section.id, rule1);
    this.ruleToManage.position = rule2.position;
    this.ruleToManage.tags = this.ruleToManage.tags.map(tag => {return tag.id});
    await this.editRule();

    this.ruleToManage = initRuleToManage(this.course.id, this.section.id, rule2);
    this.ruleToManage.position = auxRule.position;
    this.ruleToManage.tags = this.ruleToManage.tags.map(tag => {return tag.id});
    await this.editRule();

    this.sectionRules.sort(function (a, b) { return a.position - b.position; });

    await this.buildTable();

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Rule priorities changed successfully');
  }

  async editRule(): Promise<void> {

    const ruleEdited = await this.api.editRule(clearEmptyValues(this.ruleToManage)).toPromise();
    ruleEdited.tags = await this.api.getRuleTags(ruleEdited.course, ruleEdited.id).toPromise();

    const index = this.sectionRules.findIndex(rule => rule.id === ruleEdited.id);
    this.sectionRules.splice(index, 1, ruleEdited);

  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  // FIXME -- hardcoded
  isUncompleted (rule: Rule): boolean {
    let query = "logs = [] # COMPLETE THIS:";
    return rule.whenClause.includes(query) || rule.thenClause.includes(query);
  }

  onFileSelected(files: FileList, type: 'file'): void {
    this.importData.file = files.item(0);
  }

  resetImport(){
    this.importData = {file:null, replace: true};
    this.fImport.resetForm();
  }

  async exitManagement(){

    this.resetRuleManage();
    ModalService.closeModal('exit-management');

  }

  /*** --------------------------------------------- ***/
  /*** -------------- Data Management -------------- ***/
  /*** --------------------------------------------- ***/

  resetRuleManage(){
    this.ruleToManage = initRuleToManage(this.course.id, this.section.id);
    if (this.r) this.r.resetForm();
  }
}
