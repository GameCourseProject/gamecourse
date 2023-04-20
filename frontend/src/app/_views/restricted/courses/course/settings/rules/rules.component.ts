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

import * as _ from "lodash";
@Component({
  selector: 'app-rules',
  templateUrl: './rules.component.html',
  styleUrls: ['./rules.component.scss']
})
export class RulesComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    //table: false
  }
  //refreshing: boolean = true;

  course: Course;
  courseRules: Rule[];                    // all rules relating to course
  filteredRules: Rule[];                  // rule search

  nameTags : {value: string, text: string}[];
  //defaultTag: RuleTag = this.initTagToManage();
  //selectedTags: string[];
  //availableTags: any[] = [];
  tags: RuleTag[];
  selectedTags: string[];

  originalSections: RuleSection[] = [];
  sections: RuleSection[] = [];                // sections of page
  filteredSections: RuleSection[] = [];        // section search
  sectionActions: {action: Action | string, icon?: string, outline?: boolean, dropdown?: {action: Action | string, icon?: string}[],
      color?: "ghost" | "primary" | "secondary" | "accent" | "neutral" | "info" | "success" | "warning" | "error", disable?: boolean}[]  = [
      {action: 'sections\' priorities', icon: 'jam-box', color: 'secondary', disable: true},
      {action: 'manage tags', icon: 'tabler-tags', color: 'secondary'},
      {action: 'add section', icon: 'feather-plus-circle', color: 'primary'}];

  mode: 'add rule' | 'edit rule' | 'remove rule';
  tagMode : 'manage tags' | 'add tag' | 'remove tag' | 'edit tag';
  sectionMode: 'add section' | 'edit section' | 'remove section' | 'manage sections priority'; // FIXME ? -- remove section might not be in here but in the mode variable

  // MANAGE DATA
  sectionToManage: SectionManageData;
  //sectionToDelete: RuleSection;
  ruleToManage: RuleManageData = this.initRuleToManage();
  ruleToDelete: Rule;

  reduce = new Reduce();
  searchQuery: string;
/*
  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Execution Order', align: 'left'},
    {label: 'Name', align: 'left'},
    {label: 'Tags', align: 'middle'},
    {label: 'Active', align: 'middle'},
    {label: 'Actions'}
  ];
  tableOptions = {
    order: [ 0, 'asc' ],        // default order -> column 0 ascendant
    columnDefs: [
      { type: 'natural', targets: [0,1] },
      { searchable: false, targets: [2,3] },
      { orderable: false, targets: [2,3] }
    ]
  }
  data: {[sectionId: number]: {type: TableDataType, content: any}[][]};
*/
  @ViewChild('r', {static: false}) r: NgForm;       // rule form
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
      await this.getTags(courseID);

      /*
      for (let i = 0; i < this.originalSections.length; i++ ){
        await this.buildTable(this.originalSections[i]);
      }
      console.log(this.originalSections);
      */

      this.loading.page = false;

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
    this.courseRules = (await this.api.getCourseRules(courseID).toPromise()).sort(function (a, b) {
      return a.position - b.position; });

    // Get tags for each rule -> Tags are on different table (RULE_TAGS)
    /*for (let i=0; i < this.courseRules.length; i++){
      this.courseRules[i].tags = await this.api.getRuleTags(courseID, this.courseRules[i].id).toPromise();
    }
*/
    this.filteredRules = this.courseRules;
  }

  async getCourseSections(courseID: number): Promise<void> {
    this.originalSections = (await this.api.getCourseSections(courseID).toPromise()).sort((a, b) => a.name.localeCompare(b.name));
    this.filteredSections = this.originalSections;

    if (this.originalSections.length > 1){
      this.sectionActions[0].disable = false;
    }

    // initialize information for tables
    const auxSection = this.initSectionToManage();
    for (let i = 0; i <  this.originalSections.length; i++){
      this.originalSections[i].headers = auxSection.headers;
      this.originalSections[i].data = auxSection.data;
      this.originalSections[i].options = auxSection.options;
      this.originalSections[i].loadingTable = auxSection.loadingTable;
      this.originalSections[i].showTable = auxSection.showTable;
    }
/*
    let info = {}
    for (let i = 0 ; i < this.originalSections.length; i++){
      info[this.originalSections[i].id] = [];
    }
    this.data = info;*/
    //this.data = this.originalSections.map(section => { return {[section.id - 1]: []}});
  }

  async getTags(courseID: number): Promise<void> {
    this.tags = await this.api.getTags(courseID).toPromise();

    this.getTagNames();
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.courseRules, query);
  }

  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  async buildTable(section: RuleSection) {
    section.loadingTable = true;

    const table: { type: TableDataType; content: any }[][] = [];

    const rules = await this.api.getRulesOfSection(this.course.id, section.id).toPromise();
    rules.forEach(rule => {
      table.push([
        {type: TableDataType.NUMBER, content: {value: rule.position, valueFormat: 'none'}},
        {type: TableDataType.TEXT, content: {text: rule.name}},
        {type: TableDataType.CUSTOM, content: {html: '<div class="flex flex-col gap-2">' +
              rule.tags.sort((a,b) => a.name.localeCompare(b.name))
                .map(tag => '<div class="badge badge-md badge-' + this.hexaToColor(tag.color) + '">' + tag.name + '</div>').join('') +
                '</div>', searchBy: rule.tags.map(tag => tag.name).join(' ') }},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: rule.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [
          Action.EDIT,
          {action: 'Duplicate', icon: 'tabler-copy', color: 'primary'},
          {action: 'Increase priority', icon: 'tabler-arrow-narrow-up', color: 'primary'},
          {action: 'Decrease priority', icon: 'tabler-arrow-narrow-down', color: 'primary'},
          Action.REMOVE,
          Action.EXPORT]}
        }
      ]);
    });

    section.data = _.cloneDeep(table);
    //this.data[section.id] = _.cloneDeep(table);
    section.loadingTable = false;
    section.showTable = true;
  }

  async closeSectionManagement(event: RuleSection[]) {
    this.originalSections = event;

    if (this.sectionMode === 'add section') {
      // NOTE: If new section is added, is at the end (also considering 1 section added per action)
      await this.buildTable(this.originalSections[this.originalSections.length - 1]);
    }

    this.sectionToManage = null;
    this.sectionMode = null;
    this.sections = null;
  }

  doActionOnTable(section: RuleSection, action: string, row: number, col: number, value?: any): void{
    const ruleToActOn = this.courseRules[row];

    if (action === 'value changed rule'){
      if (col === 5) this.toggleActive(ruleToActOn);

    } else if (action === Action.REMOVE) {
      this.ruleToDelete = ruleToActOn;
      this.mode = 'remove rule';
      ModalService.openModal('delete-rule');

    } else if (action === Action.EDIT) {
      this.mode = 'edit rule';
      this.ruleToManage = this.initRuleToManage(ruleToActOn);

      // for the input-select
      this.selectedTags = this.ruleToManage.tags.map(tag => {return tag.id + '-' + tag.name});

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

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareModal(action: string, section?: RuleSection) {
    if (action === Action.IMPORT){
      ModalService.openModal('import');

    } else if (action === Action.EXPORT) {
      await this.exportRules(this.courseRules);

    } else if (action === 'add section' || action === 'edit section' || action === 'remove section' ||
      action === 'sections\' priorities') {

      this.sections = _.cloneDeep(this.originalSections);
      this.sectionMode = (action === 'sections\' priorities') ? 'manage sections priority' : action;
      this.sectionToManage = this.initSectionToManage(section);

      let modal = (action === 'remove section') ? action : (action === 'sections\' priorities') ?
        'manage-sections-priority' : 'manage-section';
      ModalService.openModal(modal);

    } else if (action === 'Create rule') {
      this.mode = 'add rule';
      this.selectedTags = [];
      this.ruleToManage = this.initRuleToManage();

      this.ruleToManage.section = section.id;
      this.ruleToManage.course = this.course.id;

      ModalService.openModal('manage-rule');

    } else if (action === 'manage tags' || action === 'add tag' || action === 'remove tag' || action === 'edit tag'){
      this.tagMode = action;
      ModalService.openModal(action);

    }

  }

  async doAction(action: string, rule?: Rule){
    this.loading.action = true;
    if (action === 'add rule' || action === 'edit rule'){
      if (this.r.valid) {

        this.assignTags();
        let rule = (action === 'add rule') ? await this.createRule() : await this.editRule();

        ModalService.closeModal('manage-rule');
        this.resetRuleManage();
        AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + rule.name + '\' added');

      } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
    } else if (action === 'remove rule'){

      await this.deleteRule(rule);

      ModalService.closeModal('delete-rule');
      this.mode = null;
      this.ruleToDelete = null;

      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + rule.name + '\' removed');
    }
    this.loading.action = false;
  }

  async deleteRule(rule: Rule): Promise<void> {
    let section = this.originalSections.find(section => section.id === rule.section);

    await this.api.deleteRule(rule.section, rule.id).toPromise();
    const index = this.courseRules.findIndex(el => el.id === rule.id);
    this.courseRules.removeAtIndex(index);

    await this.buildTable(section);
  }

  async toggleActive(rule: Rule){
    this.loading.action = true;

    rule.isActive = !rule.isActive;
    await this.api.setCourseRuleActive(this.course.id, rule.id, rule.isActive).toPromise();

    this.loading.action = false;
  }

  async importRules(): Promise<void> {
    // FIXME -- check if it works
    if (this.fImport.valid){
      this.loading.action = true;

      const file = await ResourceManager.getText(this.importData.file);
      const nrRulesImported = await this.api.importCourseRules(this.course.id, file, this.importData.replace).toPromise();

      await this.getCourseRules(this.course.id);
      //this.buildTable();

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

  async createRule(): Promise<Rule> {

      // FIXME create position --- NEEDS ABSTRACTION
      const sectionRules = await this.api.getRulesOfSection(this.course.id, this.ruleToManage.section).toPromise();
      this.ruleToManage.position = sectionRules.length + 1;

      const newRule = await this.api.createRule(clearEmptyValues(this.ruleToManage)).toPromise();
      newRule.tags = await this.api.getRuleTags(newRule.course, newRule.id).toPromise();

      this.courseRules.push(newRule);

      const section = this.originalSections.find(section => section.id === newRule.section);
      await this.buildTable(section);

      return newRule;
  }

  async editRule(): Promise<Rule> {

      const ruleEdited = await this.api.editRule(clearEmptyValues(this.ruleToManage)).toPromise();
      ruleEdited.tags = await this.api.getRuleTags(ruleEdited.course, ruleEdited.id).toPromise();

      const index = this.courseRules.findIndex(rule => rule.id === ruleEdited.id);
      this.courseRules.splice(index, 1, ruleEdited);

      const section = this.originalSections.find(section => section.id === ruleEdited.section);
      await this.buildTable(section);

      return ruleEdited;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Sections ----------------- ***/
  /*** --------------------------------------------- ***/



  /*** --------------------------------------------- ***/
  /*** -------------------- Tags ------------------- ***/
  /*** --------------------------------------------- ***/

  getTagNames() {
    let nameTags = [];
    for (let i = 0; i < this.tags.length ; i++){
      nameTags.push({value: this.tags[i].id + '-' + this.tags[i].name, text: this.tags[i].name});
    }
    this.nameTags = nameTags;
  }

  async assignRules(event: Rule[]){
    for (let i = 0; i < event.length; i++){
      this.ruleToManage = this.initRuleToManage(event[i]);
      await this.editRule();
    }
  }

  /*
  async createTag(): Promise<void> {
    if (this.t.valid) {
      this.loading.action = true;

      this.tagToManage.course = this.course.id;

      this.tagToManage.color = this.colorToHexa(this.tagToManage.color);

      const newTag = await this.api.createTag(clearEmptyValues(this.tagToManage)).toPromise();
      const obj = RuleTag.toJason(newTag);
      this.availableTags.push(obj);

      // Add to input select NOT WORKING!! FIXME
      this.selectedTags.push('' + newTag.id + '-' + newTag.name);

      this.loading.action = false;
      ModalService.closeModal('manage-tag');
      this.resetTagManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Tag \'' + newTag.name + '\' created');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }
*/

  assignTags() {
    if (this.selectedTags.length > 0){
      let tags = [];
      for (let i = 0;  i < this.selectedTags.length; i++){
        const data = this.selectedTags[i].split(/-(.*)/s);
        const tag = this.tags.find(element => element.id === parseInt(data[0]) && element.name === data[1]);
        tags.push(RuleTag.toJason(tag));
      }
      this.ruleToManage.tags = tags;
    }
  }

  /* async createDefaultTag(courseID: number): Promise<void> {
     const defaultTag = this.initTagToManage();
     defaultTag.course = courseID;
     defaultTag.name = "extra-credit";
     defaultTag.color = this.colorToHexa("primary");

     const obj = await this.api.createTag(clearEmptyValues(defaultTag)).toPromise();
     this.availableTags.push(obj);
   }
   removeTag(tag: TagManageData){
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

  hexaToColor(color: string) : string {
    if (this.themeService.getTheme() === "light"){
      switch (color) {
        case "#5E72E4" : return "primary";
        case "#EA6FAC" : return "secondary";
        case "#1EA896" : return "accent";
        case "#38BFF8" : return "info";
        case "#36D399" : return "success";
        case "#FBB50A" : return "warning";
        case "#EF6060" : return "error";
      }
    }
    else {
      switch (color) {
        case "#5E72E4" : return "primary";
        case "#EA6FAC" : return "secondary";
        case "#1EA896" : return "accent";
        case "#38BFF8" : return "info";
        case "#36D399" : return "success";
        case "#FBBD23" : return "warning";
        case "#EF6060" : return "error";
      }
    }
    return "";
  }

  findSectionName(rule: RuleManageData): string{
    let section = this.originalSections.find(el => el.id === rule.section);

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

  initRuleToManage(rule?: Rule): RuleManageData {
    const ruleData: RuleManageData = {
      course: rule?.course ?? null,
      section: rule?.section ?? null,
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

  resetImport(){
    this.importData = {file:null, replace: true};
    this.fImport.resetForm();
  }

  resetRuleManage(){
    this.mode = null;
    this.ruleToManage = this.initRuleToManage();
    this.r.resetForm();
  }

  initSectionToManage(section?: RuleSection): SectionManageData {
    const sectionData: SectionManageData = {
      course: section?.course ?? this.course.id,
      name: section?.name ?? null,
      position: section?.position ?? null,
      headers: [
        {label: 'Execution Order', align: 'left'},
        {label: 'Name', align: 'left'},
        {label: 'Tags', align: 'middle'},
        {label: 'Active', align: 'middle'},
        {label: 'Actions'}],
      data: section?.data ?? [],
      options: {
        order: [ 0, 'asc' ],        // default order -> column 0 ascendant
        columnDefs: [
          { type: 'natural', targets: [0,1] },
          { searchable: false, targets: [2,3] },
          { orderable: false, targets: [2,3] }
        ]
      },
      loadingTable: false,
      showTable: false
    };
    if (section) sectionData.id = section.id;
    return sectionData;
  }

}

export interface RuleManageData {
  id?: number,
  course?: number,
  section?: number,
  name?: string,
  description?: string,
  whenClause?: string,
  thenClause?: string,
  position?: number,
  isActive?: boolean,
  tags?: any[]
}

export interface SectionManageData {
  id?: number,
  course?: number,
  name?: string,
  position?: number,
  headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
  data: {type: TableDataType, content: any}[][],
  options?: any,
  loadingTable: boolean,
  showTable: boolean
}

