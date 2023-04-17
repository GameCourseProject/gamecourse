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
  templateUrl: './rules.component.html'
})
export class RulesComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    table: false,
    list: false
  }
  refreshing: boolean = true;

  course: Course;
  courseRules: Rule[];                    // all rules relating to course
  filteredRules: Rule[];                  // rule search

  //nameTags : {value: string, text: string}[] = [];  // move to backend later maybe?;
  //defaultTag: RuleTag = this.initTagToManage();
  //selectedTags: string[];
  //availableTags: any[] = [];
  tags: RuleTag[];
  selectedTags: string[];

  originalSections: RuleSection[] = [];
  sections: RuleSection[] = [];                // sections of page
  filteredSections: RuleSection[] = [];        // section search
  sectionActions: {action: Action | string, icon?: string, outline?: boolean, dropdown?: {action: Action | string, icon?: string}[],
      color?: "ghost" | "primary" | "secondary" | "accent" | "neutral" | "info" | "success" | "warning" | "error", disable?: boolean}[]  = [ {action: 'sections\' priorities', icon: 'jam-box', color: 'secondary', disable: true},
      {action: 'manage tags', icon: 'tabler-tags', color: 'secondary'},
      {action: 'add section', icon: 'feather-plus-circle', color: 'primary'}
      ];

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

      for (let i = 0; i < this.originalSections.length; i++ ){
        await this.buildTable(this.originalSections[i]);
      }

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
    this.courseRules = (await this.api.getCourseRules(courseID).toPromise()).sort((a, b) => a.name.localeCompare(b.name));

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
    let info = {}
    for (let i = 0 ; i < this.originalSections.length; i++){
      info[this.originalSections[i].id - 1] = [];
    }
    this.data = info;
    console.log(this.data);
    //this.data = this.originalSections.map(section => { return {[section.id - 1]: []}});
  }



  async getTags(courseID: number): Promise<void> {
    this.tags = await this.api.getTags(courseID).toPromise();
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

  async buildTable(section: RuleSection) {
    this.refreshing = true;
    setTimeout(() => this.refreshing = false, 0);

    this.loading.table = true;

    const table: { type: TableDataType; content: any }[][] = [];

    const rules = await this.api.getRulesOfSection(this.course.id, section.id).toPromise();
    rules.forEach(rule => {
      table.push([
        {type: TableDataType.NUMBER, content: {value: rule.position ?? 1, valueFormat: 'none'}}, // FIXME -- should not have '?? 1'
        {type: TableDataType.TEXT, content: {text: rule.name}},
        {type: TableDataType.CUSTOM, content: {html: '<div class="gap-2">' +
              rule.tags.sort((a,b) => a.name.localeCompare(b.name))
                .map(tag => '<div class="badge badge-sm badge-' + this.hexaToColor(tag.color) + '">' + tag.name + '</div>').join('') +
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

    this.data[section.id - 1] = _.cloneDeep(table);
    console.log(this.data[section.id-1]);
    this.loading.table = false;
    return table;
  }

  dataValues(sectionId: number){
    return Object.values(this.data[sectionId - 1]);
  }

  async hey (event: RuleSection[]) {
    this.originalSections = event;

    if (this.sectionMode === 'add section') {
      // NOTE: If new section is added, is at the end (also considering 1 section added per action)
      await this.buildTable(this.originalSections[-1]);
    }

    this.sectionToManage = null;
    this.sectionMode = null;
    this.sections = null;
  }

  doActionOnTable(action: string, row: number, col: number, value?: any): void{
    const ruleToActOn = this.courseRules[row];

    if (action === 'value changed rule'){
      if (col === 5) this.toggleActive(ruleToActOn);

    } else if (action === Action.REMOVE) {
      this.ruleToDelete = ruleToActOn;
      this.mode = 'remove rule';
      ModalService.openModal('delete-verification');

    } else if (action === Action.EDIT) {
      this.mode = 'edit rule';
      this.ruleToManage = this.initRuleToManage(ruleToActOn);

      // for the input-select
      /*if (!this.selectedTags) {
        let tagSelect = this.getTagNames(this.ruleToManage.tags);
        this.selectedTags = [];

        for (let i = 0; i < tagSelect.length; i++){
          this.selectedTags.push(tagSelect[i].value);
        }

      }
      if (!this.availableTags) this.getTags(this.course.id);*/
      ModalService.openModal('manage-rule')
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async doAction(action: string, section?: RuleSection) {
    if (action === Action.IMPORT){
      ModalService.openModal('import');

    } else if (action === Action.EXPORT) {
      await this.exportRules(this.courseRules);

    } else if (action === 'add section' || action === 'edit section' || action === 'sections\' priorities') {

      let modal;
      this.sections = _.cloneDeep(this.originalSections);

      if (action === 'add section' || action === 'edit section'){
        this.sectionMode = action;
        modal = 'manage-section';

      } else if (action === 'sections\' priorities') {
        this.sectionMode = 'manage sections priority';
        modal = 'manage-sections-priority';
      }

      this.sectionToManage = this.initSectionToManage(section);
      if (modal) ModalService.openModal(modal);

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

    } else if (action === 'Remove Section') {
      //this.mode = 'remove section';
      //this.sectionToDelete = section;
      //ModalService.openModal('delete-verification');
    }

  }

  async deleteRule(rule: Rule): Promise<void> {
    this.loading.action = true;

    let section = this.originalSections.find(section => section.id === rule.section);

    await this.api.deleteRule(rule.section, rule.id).toPromise();
    const index = this.courseRules.findIndex(el => el.id === rule.id);
    this.courseRules.removeAtIndex(index);

    await this.buildTable(section);

    this.loading.action = false;
    ModalService.closeModal('delete-verification');
    this.mode = null;
    this.ruleToDelete = null;

    AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + rule.name + '\' removed');
  }

  async deleteSection(section: RuleSection): Promise<void> {
    this.loading.action = true;

    const rules = await this.api.getRulesOfSection(this.course.id,section.id).toPromise();
    await this.api.deleteSection(section.id, rules).toPromise();

    const index = this.originalSections.findIndex(el => el.id === section.id);
    this.originalSections.removeAtIndex(index);

    if (this.originalSections.length <= 1){
      this.sectionActions[0].disable = true;
    }

    this.loading.action = false;
    ModalService.closeModal('delete-verification');
    this.mode = null;
    //this.sectionToDelete = null;

    AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + section.name + '\' removed');

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

  async createRule(): Promise<void> {
    if (this.r.valid) {
      this.loading.action = true;

      // FIXME create position --- NEEDS ABSTRACTION
      const sectionRules = await this.api.getRulesOfSection(this.course.id, this.ruleToManage.section).toPromise();
      this.ruleToManage.position = sectionRules.length + 1;

      //this.findTag();   // sets tags for rule creation
      const newRule = await this.api.createRule(clearEmptyValues(this.ruleToManage)).toPromise();
      newRule.tags = await this.api.getRuleTags(newRule.course, newRule.id).toPromise();

      this.courseRules.push(newRule);

      const section = this.originalSections.find(section => section.id === newRule.section);
      await this.buildTable(section);

      this.loading.action = false;
      ModalService.closeModal('manage-rule');
      this.resetRuleManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + newRule.name + '\' added');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editRule(): Promise<void> {
    if (this.r.valid) {
      this.loading.action = true;

      //this.findTag();     // set tags for rule edition

      const ruleEdited = await this.api.editRule(clearEmptyValues(this.ruleToManage)).toPromise();
      ruleEdited.tags = await this.api.getRuleTags(ruleEdited.course, ruleEdited.id).toPromise();

      const index = this.courseRules.findIndex(rule => rule.id === ruleEdited.id);
      this.courseRules.splice(index, 1, ruleEdited);

      const section = this.originalSections.find(section => section.id === ruleEdited.section);
      await this.buildTable(section);

      this.loading.action = false;
      ModalService.closeModal('manage-rule');
      this.resetRuleManage();
      AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + ruleEdited.name + '\' edited');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Sections ----------------- ***/
  /*** --------------------------------------------- ***/



  /*** --------------------------------------------- ***/
  /*** -------------------- Tags ------------------- ***/
  /*** --------------------------------------------- ***/

  getTagNames(tags: RuleTag[]): {value: string, text: string}[] {
    let nameTags = [];
    for (let i = 0; i < tags.length ; i++){
      nameTags.push({value: tags[i].id + '-' + tags[i].name, text: tags[i].name});
    }
    return nameTags;
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

  findTag() {
    let tags = [];
    let obj;

    // should be changed if there is a very high amount of tags per course!
    for (let i = 0; i < this.selectedTags.length; i++){
      // if tag "3-extra-credit", "id" will be 3 and "name" will be "extra-credit"
      const myString = this.selectedTags[i].split(/-(.*)/s);
      const id = myString[0];
      const name = myString[1];

      for (let j = 0; j < this.availableTags.length; j++){
        if (this.availableTags[j].id === id && this.availableTags[j].name === name){
          obj = RuleTag.toJason(this.availableTags[j]);
          tags.push(obj);
        }
      }
    }

    this.ruleToManage.tags = tags;
  }
*/
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
      position: section?.position ?? null
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
  position?: number
}

