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
import {SectionManageData, RuleManageData, initRuleToManage, editRule, buildTable } from "../rules.component";
import {Rule} from "../../../../../../../_domain/rules/rule";
import {DownloadManager} from "../../../../../../../_utils/download/download-manager";
import {RuleTag} from "../../../../../../../_domain/rules/RuleTag";
export {SectionManageData, RuleManageData} from "../rules.component";

@Component({
  selector: 'app-rule-sections-management',
  templateUrl: './rule-sections-management.component.html'
})
export class RuleSectionsManagementComponent implements OnInit{

  @Input() course: Course;                                          // Specific course in which rule system is being manipulated
  @Input() courseRules: Rule[];                                     // Rules of course
  @Input() sections: RuleSection[];                                 // Sections
  @Input() section?: RuleSection;                                   // Section (comes from DB - used for seeing details inside like rules etc, not manipulating the section itself)
  @Input() tags: RuleTag[];                                         // Course tags

  @Input() mode: 'see section' | 'add section' | 'edit section' | 'remove section' | 'manage sections priority';                                  // Available modes regarding section management

  @Output() newSections = new EventEmitter<RuleSection[]>();        // Changed sections to be emitted
  @Output() newCourseRules = new EventEmitter<Rule[]>();            // Changed section rules to be emitted

  loading = {
    page: false,
    action: false
  };

  ruleMode: 'add rule' | 'edit rule' | 'remove rule';
  ruleToManage: RuleManageData;
  ruleTags: string[];

  nameTags: {value: string, text: string}[];

  @ViewChild('r', {static: false}) r: NgForm;       // rule form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
  ) { }

  get Action(): typeof Action {
    return Action;
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  ngOnInit(): void {
    this.route.parent.params.subscribe();
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareModal(action: string){
    if (action === Action.IMPORT){
      ModalService.openModal('import');

    } else if (action === Action.EXPORT) {
      await this.exportRules(this.courseRules);

    } else if (action === 'Create rule') {
      this.ruleMode = 'add rule';
      this.ruleTags = [];
      this.ruleToManage = initRuleToManage();

      this.ruleToManage.section = this.section.id;
      this.ruleToManage.course = this.course.id;

      this.getTagNames();
      ModalService.openModal('manage-rule');

    }
  }

  async exportRules(rules: Rule[]): Promise<void> {
    if (rules.length === 0)
      AlertService.showAlert(AlertType.WARNING, 'There are no rules in the course to export');

    else {
      this.loading.action = true;

      const contents = await this.api.exportCourseRules(this.course.id, rules.map(rule => rule.id)).toPromise();
      DownloadManager.downloadAsCSV((this.course.short ?? this.course.name) + '-' + (rules.length === 1 ? rules[0].name : 'rules'), contents);

      this.loading.action = false;
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  async doActionOnTable(section: RuleSection, action: string, row: number, col: number, value?: any): Promise<void>{
    const ruleToActOn = this.courseRules[row];

    if (action === 'value changed rule'){
      if (col === 5) await this.toggleActive(ruleToActOn);

    } else if (action === Action.REMOVE) {
      this.ruleMode = 'remove rule';
      this.ruleToManage = initRuleToManage(ruleToActOn);

      ModalService.openModal('delete-rule');

    } else if (action === Action.EDIT) {
      this.ruleMode = 'edit rule';
      this.ruleToManage = initRuleToManage(ruleToActOn);

      // for the tags in the input-select
      this.getTagNames();
      this.ruleTags = this.ruleToManage.tags.map(tag => {return tag.id + '-' + tag.name});

      ModalService.openModal('manage-rule')
    } else if ( action === Action.DUPLICATE){
      // TODO
    } else if (action === Action.MOVE_UP){
      // TODO
    } else if ( action === Action.MOVE_DOWN) {
      // TODO
    } else if (action === Action.EXPORT){
      // TODO
    }
  }

  async doAction(action: string, rule?: Rule): Promise<void>{
    if (action === 'add rule' || 'edit rule'){
      if (this.r.valid) {
        this.loading.action = true;
        await this.assignTags();
        (action === 'add rule') ? await this.createRule() : await editRule(this.api, this.course.id, this.ruleToManage, this.courseRules, this.sections);

        await buildTable(this.api, this.course.id, this.section);
        ModalService.closeModal('manage-rule');
        AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + this.ruleToManage.name + '\' added');
        this.resetRuleManage();
        this.loading.action = false;

      } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');

    } else if (action === 'remove rule'){
      this.loading.action = true;
      await this.deleteRule();

      ModalService.closeModal('delete-rule');
      this.resetRuleManage();

      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + rule.name + '\' removed');
      this.loading.action = false;
    }
  }

  async createRule(): Promise<Rule> {

    // FIXME create position --- NEEDS ABSTRACTION
    const sectionRules = await this.api.getRulesOfSection(this.course.id, this.ruleToManage.section).toPromise();
    this.ruleToManage.position = sectionRules.length + 1;

    const newRule = await this.api.createRule(clearEmptyValues(this.ruleToManage)).toPromise();
    newRule.tags = await this.api.getRuleTags(newRule.course, newRule.id).toPromise();

    this.courseRules.push(newRule);

    return newRule;
  }

  async deleteRule(): Promise<void> {

    await this.api.deleteRule(this.ruleToManage.section, this.ruleToManage.id).toPromise();
    const index = this.courseRules.findIndex(el => el.id === this.ruleToManage.id);
    this.courseRules.removeAtIndex(index);

    await buildTable(this.api, this.course.id, this.section);
  }

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



  async toggleActive(rule: Rule){
    this.loading.action = true;

    rule.isActive = !rule.isActive;
    await this.api.setCourseRuleActive(this.course.id, rule.id, rule.isActive).toPromise();

    this.loading.action = false;
  }

  async importRules(): Promise<void> {
    // FIXME -- check if it works
    /*if (this.fImport.valid){
      this.loading.action = true;

      const file = await ResourceManager.getText(this.importData.file);
      const nrRulesImported = await this.api.importCourseRules(this.course.id, file, this.importData.replace).toPromise();

      await this.getCourseRules(this.course.id);
      //this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('import');
      this.resetImport();
      AlertService.showAlert(AlertType.SUCCESS, nrRulesImported + 'rule' + (nrRulesImported != 1 ? 's' : '') + 'imported');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');*/
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getTagNames() {
    let nameTags = [];
    for (let i = 0; i < this.tags.length ; i++){
      nameTags.push({value: this.tags[i].id + '-' + this.tags[i].name, text: this.tags[i].name});
    }
    this.nameTags = nameTags;
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Data Management -------------- ***/
  /*** --------------------------------------------- ***/

  resetRuleManage(){
    this.ruleMode = null;
    this.ruleToManage = initRuleToManage();
    this.r.resetForm();
  }

}

