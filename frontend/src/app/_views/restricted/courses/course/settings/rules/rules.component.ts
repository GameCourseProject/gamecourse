import {Component, OnInit, ViewChild} from '@angular/core';

import {Reduce} from "../../../../../../_utils/lists/reduce";
import {Action} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";


import {Rule} from "../../../../../../_domain/rules/rule";
import {Course} from "../../../../../../_domain/courses/course";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ModalService} from "../../../../../../_services/modal.service";
import {CourseRule} from "../../../../../../_domain/rules/course-rule";
import {ResourceManager} from "../../../../../../_utils/resources/resource-manager";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import {DownloadManager} from "../../../../../../_utils/download/download-manager";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {RuleSection} from "../../../../../../_domain/rules/RuleSection";
import {RuleTag} from "../../../../../../_domain/rules/RuleTag";

@Component({
  selector: 'app-rules',
  templateUrl: './rules.component.html'
})
export class RulesComponent implements OnInit {

  loading ={
    page: true,
    action: false,
    table: true,
  }

  rules: Rule[];
  filteredRules: Rule[];
  course: Course;
  courseRules: CourseRule[];

  sections: RuleSection[];
  filteredSections: RuleSection[];
  sectionToManage: SectionManageData = this.initSectionToManage();
  @ViewChild('f', {static: false}) f: NgForm;

  mode: 'add section' | 'edit section' |'add rule' | 'edit rule' | 'select';
  ruleToManage: CourseRuleManageData = this.initRuleToManage();

  reduce = new Reduce();
  searchQuery: string;

  importData: {file: File, replace: boolean} = {file: null, replace: true};
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getCourseSections(courseID);
      await this.getCourseRules(courseID);
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
  }

  async getCourseSections(courseID: number): Promise<void> {
    this.sections = (await this.api.getCourseSections(courseID).toPromise()).sort((a, b) => a.name.localeCompare(b.name));
    this.filteredSections = this.sections;
  }

  async getRules(courseID: number) : Promise<void> {
    this.rules = (await this.api.getRules().toPromise())
      .sort((a,b) => a.name.localeCompare(b.name));
    this.filteredRules = this.rules;
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.rules, query);
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Table ----------------- ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: '#', align: 'left'},
    {label: 'Rule Name', align: 'left'},
    {label: 'Tags', align: 'middle'},
    {label: 'Active', align: 'middle'},
    {label: 'Actions'}
  ];
  data: {type: TableDataType, content: any}[][];
  tableOptions = {
    order: [ 0, 'asc' ],        // default order -> column 0 ascendant
    columnDefs: [ // not sure
      { type: 'natural', targets: [0, 1, 2, 3, 4] }, // natural means number or string
      //{ searchable: false, targets: [4, 6, 7]},
      { orderable: false, targets: 1 }
    ]
  }

  buildTable(): void {
    this.loading.table = true;

    const table: { type: TableDataType, content: any }[][] = [];
    this.courseRules.forEach(rule => {
      table.push([
        {type: TableDataType.NUMBER, content: {value: rule.id, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: rule.name}},
        {type: TableDataType.TEXT, content: ""},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: rule.isActiveInCourse}},
        {type: TableDataType.ACTIONS, content: {actions: [
          Action.EDIT, Action.REMOVE]} // FALTA DUPLICATE E MUDAR PRIORIDADE. ADICIONAR VIEW E IMPORT?
        }
      ]);
    });

    this.data = table;
    this.loading.table = false;
  }

  doActionOnTable(action: string, row: number, col: number, value?: any): void{
    const ruleToActOn = this.courseRules[row];

    if (action === 'value changed'){
      if (col === 7) this.toggleActive(ruleToActOn);
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ----------------- ***/
  /*** --------------------------------------------- ***/

  doAction(action: string) {
    if (action === Action.IMPORT){
      ModalService.openModal('import');

    } else if (action === Action.EXPORT){
      this.exportRules(this.courseRules);

    } else if (action === 'Create new section'){
      this.mode = 'add section';
      this.sectionToManage = this.initSectionToManage();
      ModalService.openModal('manage-section');

    } else if (action === 'Create new rule') {
      this.mode = 'add rule';
      this.ruleToManage = this.initRuleToManage();
      ModalService.openModal('manage-rule');
    }
  }

  async toggleActive(courseRule: CourseRule){
    this.loading.action = true;

    courseRule.isActiveInCourse = !courseRule.isActiveInCourse;
    await this.api.setCourseRuleActive(this.course.id, courseRule.id, courseRule.isActiveInCourse).toPromise();

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
    if (this.f.valid) {
      this.loading.action = true;

      const newRule = await this.api.createRule(this.course.id, clearEmptyValues(this.ruleToManage)).toPromise();
      this.courseRules.push(newRule);
      this.buildTable();

      this.loading.action = false;
      ModalService.closeModal('manage-rule');
      this.resetManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + newRule.name + '\' added to course');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editRule(): Promise<void> {
    // TODO
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Sections ----------------- ***/
  /*** --------------------------------------------- ***/

  initSectionToManage(section?: RuleSection): SectionManageData {
    const sectionData: SectionManageData = {
      name: section?.name ?? null
    };
    if (section) sectionData.id = section.id;
    return sectionData;
  }

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

  /*** --------------------------------------------- ***/
  /*** -------------------- Tags ------------------- ***/
  /*** --------------------------------------------- ***/

  // TODO

  /*** --------------------------------------------- ***/
  /*** ------------------- Helpers ----------------- ***/
  /*** --------------------------------------------- ***/

  filterSections(sectionSearch: RuleSection): RuleSection[]{
    return this.filteredSections.filter(section => section.name === sectionSearch.name);
  }

  filterRules(ruleSearch: Rule): Rule[]{
    return this.filteredRules.filter(rule => rule.name === ruleSearch.name);
  }

  initRuleToManage(rule?: CourseRule): CourseRuleManageData {
    const ruleData: CourseRuleManageData = {
      sectionId: rule?.sectionId ?? null,
      name: rule?.name ?? null,
      description: rule?.description ?? null,
      when: rule?.when ?? null,
      then: rule?.then ?? null,
      position: rule?.position ?? null,
      isActive: rule?.isActive ?? null,
      tags: rule?.tags ?? null
    };
    if (rule) ruleData.id = rule.id;
    return ruleData;
  }

  resetImport(){
    this.importData = {file:null, replace: true};
    this.fImport.resetForm();
  }

  resetManage(){
    this.mode = null;
    this.ruleToManage = this.initRuleToManage();
    this.f.resetForm();
  }

  resetSectionManage(){
    this.mode = null;
    this.sectionToManage = this.initSectionToManage();
    this.f.resetForm();
  }

}

export interface CourseRuleManageData {
  id?: number,
  sectionId?: number,
  name?: string,
  description?: string,
  when?: string,
  then?: string,
  position?: number,
  isActive?: boolean,
  tags?: RuleTag[]
}

export interface SectionManageData {
  id?: number,
  name?: string,
  rules?: Rule[]
}
