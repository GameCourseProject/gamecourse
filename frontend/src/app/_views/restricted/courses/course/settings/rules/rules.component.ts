import {Component, OnInit, ViewChild} from '@angular/core';

import {Reduce} from "../../../../../../_utils/lists/reduce";
import {Action} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";

import {Rule} from "../../../../../../_domain/rules/rule";
import {Course} from "../../../../../../_domain/courses/course";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ModalService} from "../../../../../../_services/modal.service";
import {ResourceManager} from "../../../../../../_utils/resources/resource-manager";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import {DownloadManager} from "../../../../../../_utils/download/download-manager";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {RuleSection} from "../../../../../../_domain/rules/RuleSection";
import {RuleTag} from "../../../../../../_domain/rules/RuleTag";
import {ThemingService} from "../../../../../../_services/theming/theming.service";

@Component({
  selector: 'app-rules',
  templateUrl: './rules.component.html'
})
export class RulesComponent implements OnInit {

  loading ={
    page: true,
    action: false,
    table: false,
  }

  course: Course;
  courseRules: Rule[];                    // all rules relating to course
  filteredRules: Rule[];                  // rule search

  nameTags : {value: string, text: string}[] = [];  // move to backend later maybe?;

  sections: RuleSection[];                // sections of page
  filteredSections: RuleSection[];        // section search

  mode: 'add section' | 'edit section' | 'add rule' | 'edit rule' | 'select' | 'add new tag';

  // MANAGE DATA
  sectionToManage: SectionManageData = this.initSectionToManage();
  ruleToManage: RuleManageData = this.initRuleToManage();
  tagToManage: TagManageData = this.initTagToManage();
  ruleToDelete: Rule;
  //defaultTag: RuleTag;

  reduce = new Reduce();
  searchQuery: string;

  @ViewChild('f', {static: false}) f: NgForm;
  @ViewChild('r', {static: false}) r: NgForm;
  @ViewChild('t', {static: false}) t: NgForm;       // tag form
  importData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public themeService: ThemingService
  ) { }


  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getCourseSections(courseID);
      await this.getCourseRules(courseID);

      // init default tag
      //const defaultTag = this.initTagToManage();
      //defaultTag.name = "extra-credit";
      //defaultTag.color = this.colorToHexa("primary");
      //this.defaultTag = await this.api.createTag(this.course.id, clearEmptyValues(defaultTag)).toPromise();

      this.loading.page = false;

      this.buildTable();
    });
  }

  get Action(): typeof Action {
    return Action;
  }

  get ModalService(): typeof ModalService{
    return ModalService;
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getCourseRules(courseID: number): Promise<void> {
    this.courseRules = (await this.api.getCourseRules(courseID).toPromise()).sort((a, b) => a.name.localeCompare(b.name));
    this.filteredRules = this.courseRules;
  }

  async getCourseSections(courseID: number): Promise<void> {
    this.sections = (await this.api.getCourseSections(courseID).toPromise()).sort((a, b) => a.name.localeCompare(b.name));
    this.filteredSections = this.sections;
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.courseRules, query);
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Table ----------------- ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Execution Order', align: 'left'},
    {label: 'Name', align: 'left'},
    {label: 'Tags', align: 'middle'},
    {label: 'Active', align: 'middle'},
    {label: 'Actions'}
  ];
  data: {type: TableDataType, content: any}[][];
  tableOptions = {
    order: [ 0, 'asc' ],        // default order -> column 0 ascendant
    columnDefs: [ // not sure
      { type: 'natural', targets: [0,1] }, // natural means number or string
      { searchable: false, targets: [2,3] },
      { orderable: false, targets: [2,3] }
    ]
  }

  buildTable(): void {
    this.loading.table = true;

    const table: { type: TableDataType, content: any }[][] = [];
    this.courseRules.forEach(rule => {
      table.push([
        {type: TableDataType.NUMBER, content: {value: rule.position, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: rule.name}},
        {type: TableDataType.CUSTOM, content: {html: '<div class="gap-2">' +
              rule.tags.sort((a,b) => a.name.localeCompare(b.name))
                .map(tag => '<div class="badge badge-sm badge-' + tag.color + '">' + tag.name + '</div>').join('') +
                '</div>', searchBy: rule.tags.map(tag => tag.name).join(' ') }},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: rule.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [
          Action.VIEW,
          Action.EDIT,
          {action: 'Duplicate', icon: 'tabler-copy', color: 'primary'},
          {action: 'Increase priority', icon: 'tabler-arrow-narrow-up', color: 'primary'},
          {action: 'Decrease priority', icon: 'tabler-arrow-narrow-down', color: 'primary'},
          Action.REMOVE,
          Action.EXPORT]}
        }
      ]);
    });

    this.data = table;
    this.loading.table = false;
  }

  doActionOnTable(action: string, row: number, col: number, value?: any): void{
    const ruleToActOn = this.courseRules[row];

    if (action === 'value changed'){
      if (col === 5) this.toggleActive(ruleToActOn);

    } else if (action === Action.REMOVE) {
      this.ruleToDelete = ruleToActOn;
      ModalService.openModal('delete-verification');

    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  doAction(action: string, section?: RuleSection) {
    if (action === Action.IMPORT){
      ModalService.openModal('import');

    } else if (action === Action.EXPORT) {
      this.exportRules(this.courseRules);

    } else if (action === 'Add new section'){
      this.mode = 'add section';
      this.sectionToManage = this.initSectionToManage();
      ModalService.openModal('manage-section');

    } else if (action === 'Create rule') {
      this.mode = 'add rule';
      this.ruleToManage = this.initRuleToManage();

      this.ruleToManage.sectionId = section.id;
      console.log(this.ruleToManage.sectionId);
      //if (!this.ruleToManage.tags.includes(this.defaultTag)){
      //  this.ruleToManage.tags.push(this.defaultTag);
      //}

      ModalService.openModal('manage-rule');

    } else if (action === 'add tag') {
      this.mode = 'add new tag';
      this.tagToManage = this.initTagToManage();
      ModalService.openModal('manage-tag');
    }

  }

  async deleteRule(rule: Rule): Promise<void> {
    this.loading.action = true;

    await this.api.deleteRule(rule.sectionId, rule.id).toPromise();
    const index = this.courseRules.findIndex(el => el.id === rule.id);
    this.courseRules.removeAtIndex(index);
    this.buildTable();

    this.loading.action = false;
    ModalService.closeModal('delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + rule.name + '\' was removed from this section');
  }

  async toggleActive(rule: Rule){
    this.loading.action = true;

    rule.isActive = !rule.isActive;
    await this.api.setCourseRuleActive(this.course.id, rule.id, rule.isActive).toPromise();

    this.loading.action = false;
  }

  async importRules(): Promise<void> {
    if (this.fImport.valid){
      this.loading.action = true;

      const file = await ResourceManager.getText(this.importData.file);
      const nrRulesImported = await this.api.importCourseRules(this.course.id, file, this.importData.replace).toPromise();

      await this.getCourseRules(this.course.id);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('import');
      this.resetImport();
      AlertService.showAlert(AlertType.SUCCESS, nrRulesImported + 'rule' + (nrRulesImported != 1 ? 's' : '') + 'imported');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
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

  async createRule(): Promise<void> {
    if (this.r.valid) {
      this.loading.action = true;

      console.log(this.ruleToManage);
      // create position --- NEEDS ABSTRACTION
      const sectionRules = await this.getRulesOfSection(this.course.id, this.ruleToManage.sectionId);
      this.ruleToManage.position = sectionRules.length + 1;
      console.log(this.ruleToManage);

      const newRule = await this.api.createRule(this.course.id, clearEmptyValues(this.ruleToManage)).toPromise();
      this.courseRules.push(newRule);
      console.log(this.courseRules);

      this.buildTable();
      this.loading.action = false;
      ModalService.closeModal('manage-rule');
      this.resetRuleManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + newRule.name + '\' added to course');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editRule(): Promise<void> {
    // TODO
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Sections ----------------- ***/
  /*** --------------------------------------------- ***/

  async createSection(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      const newSection = await this.api.createSection(this.course.id, clearEmptyValues(this.sectionToManage)).toPromise();
      this.sections.push(newSection);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('manage-section');
      this.resetSectionManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + newSection.name + '\' added to course');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editSection(): Promise<void>{
    // TODO
  }

  async getRulesOfSection(courseId: number, sectionId: number, active?: boolean): Promise<Rule[]>
  {
    let rulesOfSection = await this.api.getRulesOfSection(courseId, sectionId, active).toPromise();
    return rulesOfSection;
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Tags ------------------- ***/
  /*** --------------------------------------------- ***/

  getTagNames(tags: RuleTag[]): {value: string, text: string}[] {
    for (let i = 0; i < tags.length ; i++){
      this.nameTags.push({value: 't-' + tags[i].name, text: tags[i].name});
    }
    return this.nameTags;
  }

  async createTag(): Promise<void> {
    if (this.t.valid) {
      this.loading.action = true;

      const color = this.colorToHexa(this.tagToManage.color);
      this.tagToManage.color = color;

      const newTag = await this.api.createTag(this.course.id, clearEmptyValues(this.tagToManage)).toPromise();
      this.ruleToManage.tags.push(newTag);
      console.log(this.ruleToManage.tags);
      //adicionar uma especie de buildTable() para dar refresh a valores do select? -- pensar nisto

      this.loading.action = false;
      ModalService.closeModal('manage-tag');
      this.resetTagManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Tag \'' + newTag.name + '\' added to current rule');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  /*removeTag(tag: TagManageData){
      this.selectedTags.splice(this.selectedTags.indexOf(tag), 1);
  }

  submitTags(){
    this.submitedTags = [];
    for (let i = 0; i < this.selectedTags.length ; i++){
      let newTag = new RuleTag(this.selectedTags[i].id, this.selectedTags[i].name, this.selectedTags[i].color);
      this.submitedTags.push(newTag);
    }
    ModalService.closeModal('manage-tag');
    this.resetTagManage();
  }*/

  /*** --------------------------------------------- ***/
  /*** ------------------- Helpers ----------------- ***/
  /*** --------------------------------------------- ***/

  getColors(): {value: string, text: string}[]{
    let colors : {value: string, text: string}[] = [];

    colors.push({value: "primary", text: "Indigo"});
    colors.push({value: "secondary", text: "Pink"});
    colors.push({value: "accent", text: "Teal"});
    colors.push({value: "info", text: "Blue"});
    colors.push({value: "success", text: "Green"});
    colors.push({value: "warning", text: "Amber"});
    colors.push({value: "error", text: "Red"});

    return colors;
  }

  // Backend does not admit 'primary', 'secondary', etc
  // Function translates it to color in hexadecimal to be sent to backend
  colorToHexa(color: string) : string {
    if (this.themeService.getTheme() === "light"){
      switch (color) {
        case "primary" : return "#5E72E4";
        case "secondary" : return "#EA6FAC";
        case "accent" : return "#1EA896";
        case "info" : return "#38BFF8";
        case "success" : return "#36D399";
        case "warning" : return "#FBB50A";
        case "error" : return "#EF6060";
      }
    }
    else {
      switch (color) {
        case "primary" : return "#5E72E4";
        case "secondary" : return "#EA6FAC";
        case "accent" : return "#1EA896";
        case "info" : return "#38BFF8";
        case "success" : return "#36D399";
        case "warning" : return "#FBBD23";
        case "error" : return "#EF6060";
      }
    }
    return "";
  }

  findSectionName(rule: RuleManageData): string{
    let section = this.sections.find(el => el.id === rule.sectionId);

    if (section){
      return section.name;
    }
    return "";
  }

  filterSections(sectionSearch: RuleSection): RuleSection[]{
    return this.filteredSections.filter(section => section.name === sectionSearch.name);
  }

  filterRules(ruleSearch: Rule): Rule[]{
    return this.filteredRules.filter(rule => rule.name === ruleSearch.name);
  }

  initSectionToManage(section?: RuleSection): SectionManageData {
    const sectionData: SectionManageData = {
      name: section?.name ?? null
    };
    if (section) sectionData.id = section.id;
    return sectionData;
  }

  initRuleToManage(rule?: Rule): RuleManageData {
    const ruleData: RuleManageData = {
      sectionId: rule?.sectionId ?? null,
      name: rule?.name ?? null,
      description: rule?.description ?? null,
      when: rule?.when ?? null,
      then: rule?.then ?? null,
      position: rule?.position ?? null,
      tags: rule?.tags ?? []
    };
    if (rule) {
      ruleData.id = rule.id;
    }
    return ruleData;
  }

  initTagToManage(tag?: RuleTag): TagManageData {
    const tagData: TagManageData = {
      name : tag?.name ?? null,
      color : tag?.color ?? null
    };
    if (tag) tagData.id = tag.id;
    return tagData;
  }

  resetImport(){
    this.importData = {file:null, replace: true};
    this.fImport.resetForm();
  }

  resetRuleManage(){
    this.mode = null;
    this.ruleToManage = this.initRuleToManage();
    this.f.resetForm();
  }

  resetSectionManage(){
    this.mode = null;
    this.sectionToManage = this.initSectionToManage();
    this.f.resetForm();
  }

  resetTagManage() {
    this.mode = "add rule";
    this.tagToManage = this.initTagToManage();
    this.t.resetForm();
  }

}

export interface RuleManageData {
  id?: number,
  sectionId?: number,
  name?: string,
  description?: string,
  when?: string,
  then?: string,
  position?: number,
  tags?: RuleTag[]
}

export interface SectionManageData {
  id?: number,
  name?: string,
  rules?: Rule[]
}

export interface TagManageData {
  id?: number,
  name?: string,
  color?: string
}
