import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {RuleSection} from "../../../../../../../_domain/rules/RuleSection";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";
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
  templateUrl: './rule-sections-management.component.html',
  styleUrls: ['rule-sections-management.component.scss']
})
export class RuleSectionsManagementComponent implements OnInit{

  @Input() course: Course;                                          // Specific course in which rule system is being manipulated
  @Input() courseRules: Rule[];                                     // Rules of course
  @Input() sections: RuleSection[];                                 // Sections
  @Input() section?: RuleSection;                                   // Section (comes from DB - used for seeing details inside like rules etc, not manipulating the section itself)
  @Input() sectionToManage?: SectionManageData;                     // Section to manipulate (edit/remove/add/change priority)

  @Input() mode: 'see section' | 'add section' | 'edit section' | 'remove section' | 'manage sections priority';                                  // Available modes regarding section management

  @Input() sectionActions: {action: Action | string, icon?: string, outline?: boolean, dropdown?: {action: Action | string, icon?: string}[],
    color?: "ghost" | "primary" | "secondary" | "accent" | "neutral" | "info" | "success" | "warning" | "error", disable?: boolean}[];            // Available actions when listing sections

  @Output() newSections = new EventEmitter<RuleSection[]>();        // Changed sections to be emitted
  @Output() newSectionActions = new EventEmitter<any[]>();          // Changed section actions to be emitted
  @Output() newCourseRules = new EventEmitter<Rule[]>();            // Changed section rules to be emitted

  loading = {
    page: false,
    action: false
  };

  ruleMode: 'add rule' | 'edit rule' | 'remove rule';
  ruleTags: string[];
  ruleToManage: RuleManageData;
  ruleToDelete: Rule;

  // FIXME -- could be reduced to one?
  @ViewChild('s', {static: false}) s: NgForm;       // Section form
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


  doActionOnTable(section: RuleSection, action: string, row: number, col: number, value?: any){
    // TODO -- will contain actions inside table related to rules of a specific section

  }

  async doAction(action: string, rule?: Rule): Promise<void>{
    if (action === 'add section'){
      await this.createSection();
    } else if (action === 'edit section'){
      await this.editSection();
    } else if (action === 'remove section'){
      await this.removeSection();
    } else if (action === 'add rule' || 'edit rule'){
      if (this.r.valid) {
        this.loading.action = true;
        await this.assignTags();
        let rule = (action === 'add rule') ? await this.createRule() : await editRule();

        ModalService.closeModal('manage-rule');
        this.resetRuleManage();
        AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + rule.name + '\' added');
        this.loading.action = false;

      } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');

    } else if (action === 'remove rule'){
      this.loading.action = true;
      await this.deleteRule(rule);

      ModalService.closeModal('delete-rule');
      this.mode = null;
      this.ruleToDelete = null;

      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + rule.name + '\' removed');
      this.loading.action = false;
    }
  }

  async createSection(): Promise<void> {
    if (this.s.valid) {
      this.loading.action = true;

      // FIXME : create position --- NEEDS ABSTRACTION
      this.sectionToManage.position = this.sections.length + 1;

      const newSection = await this.api.createSection(clearEmptyValues(this.sectionToManage)).toPromise();
      this.sections.push(newSection);

      this.toggleSectionPriority();
      this.resetSectionManage();

      this.newSections.emit(this.sections);
      this.newSectionActions.emit(this.sectionActions);
      this.loading.action = false;

      AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + newSection.name + '\' added');
      ModalService.closeModal('manage-section');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editSection(): Promise<void>{
    if (this.s.valid) {
      this.loading.action = true;

      const sectionEdited = await this.api.editSection(clearEmptyValues(this.sectionToManage)).toPromise();
      const index = this.sections.findIndex(section => section.id === sectionEdited.id);
      this.sections.splice(index, 1, sectionEdited);

      this.newSections.emit(this.sections);
      //this.newSectionActions.emit(this.sectionActions);

      this.loading.action = false;
      AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + sectionEdited.name + '\' edited');

      ModalService.closeModal('manage-section');
      this.resetSectionManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async removeSection(): Promise<void> {
    this.loading.action = true;

    //const rules = await this.api.getRulesOfSection(this.course.id,this.sectionToManage.id).toPromise();
    await this.api.deleteSection(this.sectionToManage.id).toPromise();

    const index = this.sections.findIndex(el => el.id === this.sectionToManage.id);
    this.sections.removeAtIndex(index);

    this.toggleSectionPriority();

    this.newSections.emit(this.sections);
    this.newSectionActions.emit(this.sectionActions);
    this.loading.action = false;

    AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + this.sectionToManage.name + '\' removed');
    ModalService.closeModal('remove section');
    this.mode = null;
  }

  saveSectionPriority(){
    this.newSections.emit(this.sections);
    AlertService.showAlert(AlertType.SUCCESS, 'Sections\' priority saved successfully');
    ModalService.closeModal('manage-sections-priority');
  }

  async createRule(): Promise<Rule> {

    // FIXME create position --- NEEDS ABSTRACTION
    const sectionRules = await this.api.getRulesOfSection(this.course.id, this.ruleToManage.section).toPromise();
    this.ruleToManage.position = sectionRules.length + 1;

    const newRule = await this.api.createRule(clearEmptyValues(this.ruleToManage)).toPromise();
    newRule.tags = await this.api.getRuleTags(newRule.course, newRule.id).toPromise();

    this.courseRules.push(newRule);

    const section = this.sections.find(section => section.id === newRule.section);
    await buildTable(section);

    return newRule;
  }


  async deleteRule(rule: Rule): Promise<void> {
    let section = this.sections.find(section => section.id === rule.section);

    await this.api.deleteRule(rule.section, rule.id).toPromise();
    const index = this.courseRules.findIndex(el => el.id === rule.id);
    this.courseRules.removeAtIndex(index);

    await buildTable(section);
  }

  async assignTags() {
    if (this.ruleTags.length > 0){
      let courseTags = await this.api.getTags(this.course.id).toPromise();
      let tags = [];

      for (let i = 0;  i < this.ruleTags.length; i++){
        const data = this.ruleTags[i].split(/-(.*)/s);
        const tag = courseTags.find(element => element.id === parseInt(data[0]) && element.name === data[1]);
        tags.push(RuleTag.toJason(tag));
      }
      this.ruleToManage.tags = tags;
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  drop(event: CdkDragDrop<string[]>) {
    moveItemInArray(this.sections, event.previousIndex, event.currentIndex);
  }

  toggleSectionPriority(){
    this.sectionActions[0].disable = this.sections.length <= 1;
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Data Management -------------- ***/
  /*** --------------------------------------------- ***/

  resetSectionManage(){
    this.mode = null;
    this.s.resetForm();
  }

  resetRuleManage(){
    this.ruleMode = null;
    this.ruleToManage = initRuleToManage();
    this.r.resetForm();
  }

}

