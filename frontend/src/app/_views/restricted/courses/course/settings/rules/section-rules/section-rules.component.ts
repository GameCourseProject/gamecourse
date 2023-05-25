import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {RuleSection} from "../../../../../../../_domain/rules/RuleSection";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {Action} from "../../../../../../../_domain/modules/config/Action";
import {ModalService} from "../../../../../../../_services/modal.service";
import {Course} from "../../../../../../../_domain/courses/course";
import {buildTable, initSectionToManage, RuleManageData, SectionManageData} from "../sections.component";
import {Rule} from "../../../../../../../_domain/rules/rule";
import {DownloadManager} from "../../../../../../../_utils/download/download-manager";
import {RuleTag} from "../../../../../../../_domain/rules/RuleTag";

export {SectionManageData} from "../sections.component";

import * as _ from "lodash";
import {ResourceManager} from "../../../../../../../_utils/resources/resource-manager";
import {ThemingService} from "../../../../../../../_services/theming/theming.service";
import {Subject} from "rxjs";
import {TableDataType} from "../../../../../../../_components/tables/table-data/table-data.component";

@Component({
  selector: 'app-rule-sections-management',
  templateUrl: './section-rules.component.html',
})
export class SectionRulesComponent implements OnInit {

  //@Input() course: Course;                                          // Specific course in which rule system is being manipulated
  //@Input() courseRules: Rule[];                                     // Rules of course
  //@Input() section?: RuleSection;                                   // Section (comes from DB - used for seeing details inside like rules etc, not manipulating the section itself)
  //@Input() tags: RuleTag[];                                         // Course tags
  //@Input() metadata: string;

  course: Course;
  courseRules: Rule[]; // FIXME -- not sure if needed
  sectionRules: Rule[];
  section: RuleSection;
  tags: RuleTag[];
  metadata: {[variable: string]: number}[];
  parsedMetadata: string;


  // Available modes regarding section management
  // @Input() mode: 'see section' | 'add section' | 'edit section' | 'remove section' | 'metadata';

  //@Output() newCourseRules = new EventEmitter<Rule[]>();            // Changed section rules to be emitted

  loading = {
    page: true,
    action: false
  };
  refreshing: boolean;
  showAlert: boolean = false;

  ruleEdit: string = "";                                            // Name of rule to be edited
  ruleMode: 'add rule' | 'edit rule' | 'remove rule';               // Available actions for rules
  interruptedMode: 'add rule' | 'edit rule' | 'remove rule';        // (Auxiliar) Available actions for rules
  ruleToManage: RuleManageData;                                     // Manage data
  //ruleTags: string[];                                             // Tags from a specific rule
  row: number;                                                      // Row identifying rule in table that its being manipulated

  // Input-select for assigning rules to tags
  previousSelected: string[];
  setTags: Subject<{value: string, text: string, innerHTML?: string, selected: boolean}[]> = new Subject();
  nameTags : {value: string, text: string}[];                       // Tags with names formatted for the select-input

  @ViewChild('r', {static: false}) r: NgForm;                       // rule form

  // Importing action
  importData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  functions: { moduleId: string, name: string, keyword: string, description: string, args: {name: string, optional: boolean, type: any}[] }[];

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
      await this.getCourseRules(courseID);
      await this.getTags(courseID);
      await this.getMetadata();
      await this.getRuleFunctions(courseID);

      this.route.params.subscribe(async childParams => {
        const sectionID = childParams.id;
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

  async getCourseRules(courseID: number): Promise<void> {
    this.courseRules = (await this.api.getCourseRules(courseID).toPromise()).sort(function (a, b) {
      return a.position - b.position; });
  }

  async getTags(courseID: number): Promise<void> {
    this.tags = await this.api.getTags(courseID).toPromise();

    // Get names in another variable for input-select
    let nameTags = [];
    for (let i = 0; i < this.tags.length ; i++){
      nameTags.push({value: this.tags[i].id + '-' + this.tags[i].name, text: this.tags[i].name});
    }
    this.nameTags = _.cloneDeep(nameTags);
  }

  async getRuleFunctions(courseID: number){
    this.functions = await this.api.getRuleFunctions(courseID).toPromise();
  }

  async getSection(courseID: number, sectionID: number) {
    this.section = await this.api.getSectionById(courseID, sectionID).toPromise();

    // Prepare information for table:
    const auxSection = initSectionToManage(this.course.id);
    this.section.headers = auxSection.headers;
    this.section.data = auxSection.data;
    this.section.options = auxSection.options;
    this.section.loadingTable = auxSection.loadingTable;
    this.section.showTable = auxSection.showTable;
  }

  async getSectionRules(sectionID: number){
    this.sectionRules = await this.api.getRulesOfSection(this.course.id, sectionID).toPromise();
  }

  async getMetadata() {
    this.metadata = await this.api.getMetadata(this.course.id).toPromise();

    this.parsedMetadata = "";
    if (Object.keys(this.metadata).length > 0){
      this.parsedMetadata =
        "# This is a quick reference for global variables in AutoGame's rule edition. How to use?\n" +
        "# e.g. get total number of Alameda lectures\n" +
        "# nrLectures = METADATA[\"all_lectures_alameda\"] + METADATA[\"invited_alameda\"]\n\n";

      for (const data of Object.keys(this.metadata)){
        this.parsedMetadata += (data + " : " + this.metadata[data] + "\n");
      }
    }

  }

  async buildTable(): Promise<void> {
    this.section.loadingTable = true;

    this.section.showTable = false;
    setTimeout(() => this.section.showTable = true, 0);

    const table: { type: TableDataType; content: any }[][] = [];

    console.log(this.sectionRules);
    this.sectionRules.forEach(rule => {
      table.push([
        {type: TableDataType.NUMBER, content: {value: rule.position, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: rule.name}},
        {type: TableDataType.CUSTOM, content: {html: '<div class="flex flex-col gap-2">' +
              rule.tags.sort((a,b) => a.name.localeCompare(b.name))
                .map(tag => '<div class="badge badge-md badge-' + this.themeService.hexaToColor(tag.color) + '">' + tag.name + '</div>').join('') +
              '</div>', searchBy: rule.tags.map(tag => tag.name).join(' ') }},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: rule.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [
              Action.EDIT,
              {action: 'Duplicate', icon: 'tabler-copy', color: 'primary'},
              {action: 'Increase priority', icon: 'tabler-arrow-narrow-up', color: 'primary', disabled: rule.position === 0 },
              {action: 'Decrease priority', icon: 'tabler-arrow-narrow-down', color: 'primary', disabled: rule.position === this.sectionRules.length - 1 },
              Action.REMOVE,
              Action.EXPORT]}
        }
      ]);
    });

    this.section.data = _.cloneDeep(table);
    this.section.loadingTable = false;
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

      this.refreshing = true;
      this.ruleMode = 'add rule';
      this.previousSelected = [];
      // this.ruleTags = [];
      this.ruleToManage = this.initRuleToManage();

      //this.getTagNames();
      //this.scroll();
      //ModalService.openModal('manage-rule');
      this.refreshing = false;

    } else if (action === 'add tag'){
      ModalService.openModal(action);
    }
  }

  async doAction(action: string): Promise<void>{
    if (action === 'add rule' || action === 'edit rule'){
      if (this.r.valid) {
        this.loading.action = true;
        await this.assignTags();
        (action === 'add rule') ? await this.createRule() : await this.editRule();

        await buildTable(this.api, this.themeService, this.course.id, this.section);
        //ModalService.closeModal('manage-rule');
        AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + this.ruleToManage.name + '\' added');
        this.resetRuleManage();
        this.previousSelected = [];
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

  async editRule(): Promise<void> {

    const ruleEdited = await this.api.editRule(clearEmptyValues(this.ruleToManage)).toPromise();
    ruleEdited.tags = await this.api.getRuleTags(ruleEdited.course, ruleEdited.id).toPromise();

    const index = this.courseRules.findIndex(rule => rule.id === ruleEdited.id);
    this.courseRules.splice(index, 1, ruleEdited);

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

    //this.newCourseRules.emit(this.courseRules);
    // TODO -- add tags here later?

    //this.mode = null;
    // FIXME -- not sure this is needed?
    this.resetRuleManage();
    //'./skills', skill.id, 'preview'


    this.router.navigate(['rule-system'], {relativeTo: this.route.parent});


    this.loading.page = false;

  }

  async previewRule(event: string){
    console.log(event);
    await this.api.previewRule(clearEmptyValues(this.ruleToManage)).toPromise();
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
        this.ruleToManage = this.initRuleToManage(ruleToActOn);

        ModalService.openModal('delete-rule');

      } else if (action === Action.EDIT) {

        if (this.ruleMode === 'edit rule' || this.ruleMode == 'add rule') {
          this.row = row;
          this.interruptedMode = 'edit rule';
          this.discardModal();
          return;
        }

        this.refreshing = true;
        this.ruleMode = 'edit rule';
        this.ruleToManage = this.initRuleToManage(ruleToActOn);
        this.ruleToManage.tags = this.ruleToManage.tags.map(tag => {return tag.id + '-' + tag.name})
        this.ruleEdit = _.cloneDeep(this.ruleToManage.name);

        // for the tags in the input-select
        // FIXME -- not working
        //this.getTagNames();
        this.previousSelected = this.ruleToManage.tags.map(tag => {return tag.id + '-' + tag.name})
        // this.ruleTags = this.ruleToManage.tags.map(tag => {return tag.id + '-' + tag.name});

        //this.scroll();
        this.refreshing = false;
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
    this.ruleToManage = this.initRuleToManage(rule1);
    this.ruleToManage.position = rule2.position;
    await this.editRule();

    this.ruleToManage = this.initRuleToManage(rule2);
    this.ruleToManage.position = auxRule.position;
    await this.editRule();

    this.courseRules.sort(function (a, b) { return a.position - b.position; });

    await buildTable(this.api, this.themeService, this.course.id, this.section);

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Rule priorities changed successfully');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  async assignTags() {
    if (this.ruleToManage.tags.length > 0){
      let tags = [];

      for (let i = 0;  i < this.ruleToManage.tags.length; i++){
        const data = this.ruleToManage.tags[i].split(/-(.*)/s);
        const tag = this.tags.find(element => element.id === parseInt(data[0]) && element.name === data[1]);
        tags.push(tag.id);
      }
      this.ruleToManage.tags = tags;
    }
  }

  updateTags(selectedTags: any[]): void {
    if (selectedTags.length > this.previousSelected.length){
      const tagToAdd = selectedTags.filter(tagName => !this.previousSelected.includes(tagName))[0];

      if (!selectedTags.includes(tagToAdd)) selectedTags.push(tagToAdd);

    } else {
      const tagToDelete = this.previousSelected.filter(tagName => !selectedTags.includes(tagName))[0];

      if (selectedTags.includes(tagToDelete)) selectedTags.splice(selectedTags.indexOf(tagToDelete), 1);
    }

    this.nameTags.map(option => {
      option['selected'] = selectedTags.includes(option.value);
      return option;
    });
  }

  changeMetadata(newMetadata: string){
    this.showAlert = true;
    //this.metadata = newMetadata;
    // TODO
    // show alert saying that this changes for all metadata in this course (so its
    // general for all rules) + only save metadata in course when save button is pressed
    // for now just update input metadata with new string
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
    //this.refreshing = true;
    //setTimeout(() => this.refreshing = false, 0);

    document.getElementById("rule-content").scrollIntoView({behavior: 'smooth'});

  }

  discardModal(){
    ModalService.openModal('exit-management');
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Data Management -------------- ***/
  /*** --------------------------------------------- ***/

  resetRuleManage(){
    //this.ruleTags = [];
    this.ruleMode = null;
    this.ruleToManage = this.initRuleToManage();
    if (this.r) this.r.resetForm();
  }

  initRuleToManage(rule?: Rule): RuleManageData {
    const ruleData: RuleManageData = {
      course: rule?.course ?? this.course.id,
      section: rule?.section ?? this.section.id,
      name: rule?.name ?? null,
      description: rule?.description ?? null,
      whenClause: rule?.whenClause ?? null,
      thenClause: rule?.thenClause ?? null,
      position: rule?.position ?? null,
      isActive: rule?.isActive ?? true,
      tags: rule?.tags ?? []
    };
    if (rule) {
      ruleData.id = rule.id;
    }
    return ruleData;
  }
}
