import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {RuleSection} from "../../../../../../../_domain/rules/RuleSection";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {Action} from "../../../../../../../_domain/modules/config/Action";
import {ModalService} from "../../../../../../../_services/modal.service";
import {Course} from "../../../../../../../_domain/courses/course";
import {buildTable, editRule, initRuleToManage, RuleManageData, SectionManageData} from "../rules.component";
import {Rule} from "../../../../../../../_domain/rules/rule";
import {DownloadManager} from "../../../../../../../_utils/download/download-manager";
import {RuleTag} from "../../../../../../../_domain/rules/RuleTag";

export {SectionManageData, RuleManageData} from "../rules.component";

import * as _ from "lodash";
import {ResourceManager} from "../../../../../../../_utils/resources/resource-manager";
import {ThemingService} from "../../../../../../../_services/theming/theming.service";
@Component({
  selector: 'app-rule-sections-management',
  templateUrl: './rule-sections-management.component.html'
})
export class RuleSectionsManagementComponent implements OnInit{

  @Input() course: Course;                                          // Specific course in which rule system is being manipulated
  @Input() courseRules: Rule[];                                     // Rules of course
  @Input() section?: RuleSection;                                   // Section (comes from DB - used for seeing details inside like rules etc, not manipulating the section itself)
  @Input() tags: RuleTag[];                                         // Course tags

  // Available modes regarding section management
  @Input() mode: 'see section' | 'add section' | 'edit section' | 'remove section' | 'manage sections priority';

  @Output() newCourseRules = new EventEmitter<Rule[]>();            // Changed section rules to be emitted

  loading = {
    page: false,
    action: false
  };
  refreshing: boolean;

  ruleEdit: string = "";                                            // Name of rule to be edited
  ruleMode: 'add rule' | 'edit rule' | 'remove rule';               // Available actions for rules
  interruptedMode: 'add rule' | 'edit rule' | 'remove rule';        // (Auxiliar) Available actions for rules
  ruleToManage: RuleManageData;                                     // Manage data
  ruleTags: string[];                                               // Tags from a specific rule
  row: number;                                                      // Row identifying rule in table that its being manipulated

  nameTags : {value: string, text: string}[];                       // Tags with names formatted for the select-input

  @ViewChild('r', {static: false}) r: NgForm;                       // rule form

  // Importing action
  importData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fImport', { static: false }) fImport: NgForm;


  functions: { moduleId: string, name: string, keyword: string, description: string, args: {name: string, optional: boolean, type: any}[] }[];
  // options: any[] = [ "panic", "park", "portugal", "password"];    // FIXME -- to be replaced with other functions from autogame

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
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
      await this.getRuleFunctions(courseID);
    });
  }

  async getRuleFunctions(courseID: number){
    this.functions = await this.api.getRuleFunctions(courseID).toPromise();
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

      if (this.ruleMode === 'edit rule' || this.ruleMode == 'add rule') {
        this.interruptedMode = 'add rule';
        this.discardModal();
        return;
      }

      this.ruleMode = 'add rule';
      this.ruleTags = [];
      this.ruleToManage = initRuleToManage(this.course.id, this.section.id);

      this.getTagNames();
      this.scroll();
      //ModalService.openModal('manage-rule');

    }
  }

  async doAction(action: string): Promise<void>{
    if (action === 'add rule' || action === 'edit rule'){
      if (this.r.valid) {
        this.loading.action = true;
        await this.assignTags();
        (action === 'add rule') ? await this.createRule() : await editRule(this.api, this.course.id, this.ruleToManage, this.courseRules);

        await buildTable(this.api, this.themeService, this.course.id, this.section);
        ModalService.closeModal('manage-rule');
        AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + this.ruleToManage.name + '\' added');
        this.resetRuleManage();
        this.loading.action = false;

      } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');

    } else if (action === 'remove rule'){
      this.loading.action = true;
      await this.deleteRule();

      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + this.ruleToManage.name + '\' removed');
      ModalService.closeModal('delete-rule');
      this.resetRuleManage();
      this.loading.action = false;
    }
  }

  async createRule(): Promise<Rule> {

    const newRule = await this.api.createRule(clearEmptyValues(this.ruleToManage)).toPromise();
    newRule.tags = await this.api.getRuleTags(newRule.course, newRule.id).toPromise();

    this.courseRules.push(newRule);

    return newRule;
  }

  async importRules(): Promise<void> {
    if (this.fImport.valid){
      this.loading.action = true;

      const file = await ResourceManager.getText(this.importData.file);
      const nrRulesImported = await this.api.importRules(this.course.id, this.section.id, file, this.importData.replace).toPromise();

      this.courseRules = await this.api.getCourseRules(this.course.id).toPromise();
      await buildTable(this.api, this.themeService, this.course.id, this.section);

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

  closeManagement(){
    this.loading.page = true;

    this.newCourseRules.emit(this.courseRules);
    // TODO -- add tags here later?

    this.mode = null;
    this.resetRuleManage();

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
      await this.toggleActive(ruleToActOn);

    } else if (col === 4){
      if (action === Action.REMOVE) {
        this.ruleMode = 'remove rule';
        this.ruleToManage = initRuleToManage(this.course.id, this.section.id, ruleToActOn);

        ModalService.openModal('delete-rule');

      } else if (action === Action.EDIT) {

        if (this.ruleMode === 'edit rule' || this.ruleMode == 'add rule') {
          this.row = row;
          this.interruptedMode = 'edit rule';
          this.discardModal();
          return;
        }

        this.ruleMode = 'edit rule';
        this.ruleToManage = initRuleToManage(this.course.id, this.section.id, ruleToActOn);
        this.ruleEdit = _.cloneDeep(this.ruleToManage.name);

        // for the tags in the input-select
        // FIXME -- not working
        // this.getTagNames();
        // this.ruleTags = this.ruleToManage.tags.map(tag => {return tag.id + '-' + tag.name});

        this.scroll();
        // ModalService.openModal('manage-rule');

      } else if ( action === Action.DUPLICATE) {
        await this.duplicateRule(ruleToActOn);

      } else if (action === 'increase priority') {
        const rule = this.courseRules[row - 1];
        await this.changePriority(ruleToActOn, rule);

      } else if (action === 'decrease priority') {
        const rule = this.courseRules[row + 1];
        await this.changePriority(ruleToActOn, rule);

      }  else if (action === Action.EXPORT || action === Action.EXPORT + ' all rules') {
        await this.exportRules([ruleToActOn]);
      }
    }
  }

  async deleteRule(): Promise<void> {

    await this.api.deleteRule(this.ruleToManage.section, this.ruleToManage.id).toPromise();
    const index = this.courseRules.findIndex(el => el.id === this.ruleToManage.id);
    this.courseRules.removeAtIndex(index);

    await buildTable(this.api, this.themeService, this.course.id, this.section);
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
    this.courseRules.unshift(newRule);
    await buildTable(this.api, this.themeService, this.course.id, this.section);

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + newRule.name + '\' added');
  }

  async changePriority(rule1: Rule, rule2: Rule){
    this.loading.action = true;

    let auxRule = _.cloneDeep(rule1);
    let rule = initRuleToManage(this.course.id, this.section.id, rule1);
    rule.position = rule2.position;
    await editRule(this.api, this.course.id, rule, this.courseRules);

    rule = initRuleToManage(this.course.id, this.section.id, rule2);
    rule.position = auxRule.position;
    await editRule(this.api, this.course.id, rule, this.courseRules);

    this.courseRules.sort(function (a, b) { return a.position - b.position; });

    await buildTable(this.api, this.themeService, this.course.id, this.section);

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Rule priorities changed successfully');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  async assignTags() {
    if (this.ruleTags.length > 0){
      let tags = [];

      for (let i = 0;  i < this.ruleTags.length; i++){
        const data = this.ruleTags[i].split(/-(.*)/s);
        const tag = this.tags.find(element => element.id === parseInt(data[0]) && element.name === data[1]);
        tags.push(RuleTag.toJason(tag));
      }
      this.ruleToManage.tags = tags;
    }
  }

  getTagNames() {
    let nameTags = [];
    for (let i = 0; i < this.tags.length ; i++){
      nameTags.push({value: this.tags[i].id + '-' + this.tags[i].name, text: this.tags[i].name});
    }
    this.nameTags = nameTags;
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

    if (this.interruptedMode === 'add rule') {
      this.interruptedMode = null;
      await this.prepareModal("Create rule");

    } else if (this.interruptedMode === 'edit rule') {
      this.interruptedMode = null;
      await this.doActionOnTable(Action.EDIT, this.row,4); // Col 4 has all additional actions
    }
  }

  scroll(){
    // NOTE: card with rule info to update
    this.refreshing = true;
    setTimeout(() => this.refreshing = false, 0);

    document.getElementById("rule-content").scrollIntoView({behavior: 'smooth'});

  }

  discardModal(){
    ModalService.openModal('exit-management');
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Data Management -------------- ***/
  /*** --------------------------------------------- ***/

  resetRuleManage(){
    this.ruleTags = [];
    this.ruleMode = null;
    this.ruleToManage = initRuleToManage(this.course.id, this.section.id);
    if (this.r) this.r.resetForm();
  }

}

