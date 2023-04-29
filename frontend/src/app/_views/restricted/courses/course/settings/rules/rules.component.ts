import {Component, OnInit, ViewChild} from '@angular/core';
import {Reduce} from "../../../../../../_utils/lists/reduce";
import {Action} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";

import {Rule} from "../../../../../../_domain/rules/rule";
import {Course} from "../../../../../../_domain/courses/course";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ModalService} from "../../../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {RuleSection} from "../../../../../../_domain/rules/RuleSection";
import {RuleTag} from "../../../../../../_domain/rules/RuleTag";
import {ThemingService} from "../../../../../../_services/theming/theming.service";

import * as _ from "lodash";
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";

@Component({
  selector: 'app-rules',
  templateUrl: './rules.component.html',
  styleUrls: ['./rules.component.scss']
})
export class RulesComponent implements OnInit {

  loading = {
    page: true,
    action: false
  }

  course: Course;                                   // Specific course in which rule system is being manipulated
  courseRules: Rule[];                              // Rules of course

  tags: RuleTag[];                                  // Tags of course

  originalSections: RuleSection[] = [];             // Sections of course
  sections: RuleSection[] = [];                     // Copy of original sections (used as auxiliary variable for setting priority)
  nrRules: {[sectionId: number]: number} = [];    // Dictionary with number of rules per section
  filteredSections: RuleSection[] = [];             // Section search

  // Section actions -- general manipulation (create/edit/remove/set priority/see details)
  sectionActions: {action: Action | string, icon?: string, outline?: boolean, dropdown?: {action: Action | string, icon?: string}[],
      color?: "ghost" | "primary" | "secondary" | "accent" | "neutral" | "info" | "success" | "warning" | "error", disable?: boolean}[]  = [
      {action: 'sections\' priorities', icon: 'jam-box', color: 'secondary', disable: true},
      {action: 'manage tags', icon: 'tabler-tags', color: 'secondary'},
      {action: 'add section', icon: 'feather-plus-circle', color: 'primary'}];

  tagMode : 'manage tags' | 'add tag' | 'remove tag' | 'edit tag';    // available actions for tags
  sectionMode: 'see section' | 'add section' | 'edit section' | 'remove section' | 'manage sections priority';  // available actions for sections

  // MANAGE DATA
  section: RuleSection;
  sectionToManage: SectionManageData;
  ruleToManage: RuleManageData;

  // SEARCH & FILTER
  reduce = new Reduce();
  searchQuery: string;

  @ViewChild('s', {static: false}) s: NgForm;       // Section form

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

      this.loading.page = false;

    });
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
  }

  async getCourseSections(courseID: number): Promise<void> {
    this.originalSections = (await this.api.getCourseSections(courseID).toPromise()).sort(function (a, b) {
      return a.position - b.position; });
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

      // Get number of rules per section
      this.nrRules[this.originalSections[i].id] = (await this.api.getRulesOfSection(this.course.id, this.originalSections[i].id).toPromise()).length;
    }
  }

  async getTags(courseID: number): Promise<void> {
    this.tags = await this.api.getTags(courseID).toPromise();
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.originalSections, query);
  }

  filterSections(sectionSearch: RuleSection): RuleSection[]{
    return this.filteredSections.filter(section => section.name === sectionSearch.name);
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareManagement(action: string, section?: RuleSection) {
    // Actions for sections general manipulation (adding/editing/removing/prioritizing actions)
     if (action === 'sections\' priorities'|| action === 'add section' || action === 'edit section' || action === 'remove section') {
       this.sections = _.cloneDeep(this.originalSections);
       this.sectionMode = (action === 'sections\' priorities') ? 'manage sections priority' : action;

       this.sectionToManage = this.initSectionToManage(section);

       let modal = (action === 'remove section') ? action : (action === 'sections\' priorities') ?
         'manage-sections-priority' : 'manage-section';
       ModalService.openModal(modal);

     }

    // Actions for manipulation INSIDE sections (seeing details -- aka everything related to the rules)
    else if (action === 'see section'){
       this.sections = _.cloneDeep(this.originalSections);
       this.sectionMode = action;
       this.section = section;

       await buildTable(this.api, this.themeService, this.course.id, section);

    }

    // Actions related to Tags' general manipulation
    else if (action === 'manage tags' || action === 'add tag' || action === 'remove tag' || action === 'edit tag') {
       this.tagMode = action;
       ModalService.openModal(action);
     }
  }

  async doAction(action: string){
    if (action === 'add section'){
      await this.createSection();
    } else if (action === 'edit section'){
      await this.editSection();
    } else if (action === 'remove section'){
      await this.removeSection();
    }
  }

  async createSection(): Promise<void> {
    if (this.s.valid) {
      this.loading.action = true;

      const newSection = await this.api.createSection(clearEmptyValues(this.sectionToManage)).toPromise();
      this.originalSections.unshift(newSection);

      this.toggleSectionPriority();
      this.resetSectionManage();

      this.loading.action = false;

      AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + newSection.name + '\' added');
      ModalService.closeModal('manage-section');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editSection(): Promise<void>{
    if (this.s.valid) {
      this.loading.action = true;

      const sectionEdited = await this.api.editSection(clearEmptyValues(this.sectionToManage)).toPromise();
      const index = this.originalSections.findIndex(section => section.id === sectionEdited.id);
      this.originalSections.splice(index, 1, sectionEdited);

      this.loading.action = false;
      AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + sectionEdited.name + '\' edited');

      ModalService.closeModal('manage-section');
      this.resetSectionManage();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async removeSection(): Promise<void> {
    this.loading.action = true;

    await this.api.deleteSection(this.sectionToManage.id).toPromise();

    const index = this.originalSections.findIndex(el => el.id === this.sectionToManage.id);
    this.originalSections.removeAtIndex(index);

    this.toggleSectionPriority();

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + this.sectionToManage.name + '\' removed');

    ModalService.closeModal('remove section');
    this.resetSectionManage();
  }

  async saveSectionPriority(): Promise<void>{
    this.loading.action = true;
    this.originalSections = this.sections;

    for (let i = 0; i < this.originalSections.length; i++){
      this.originalSections[i].position = i;
      let section = this.initSectionToManage(this.originalSections[i]);
      await this.api.editSection(section).toPromise();
    }

    this.resetSectionManage();

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Sections\' priority saved successfully');
    ModalService.closeModal('manage-sections-priority');
  }

  async closeSectionManagement(event: Rule[]) {
    this.courseRules = event;

    this.sectionToManage = null;
    this.sectionMode = null;
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Tags ------------------- ***/
  /*** --------------------------------------------- ***/

  async assignRules(event: Rule[]) {
    for (let i = 0; i < event.length; i++) {
      this.ruleToManage = initRuleToManage(this.course.id, event[i].section, event[i]);
      this.ruleToManage.tags = (event[i].tags).map(tag => {
        return initTagToManage(this.course.id, tag)
      });
      await editRule(this.api, this.course.id, this.ruleToManage, this.courseRules);
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

  drop(event: CdkDragDrop<string[]>) {
    moveItemInArray(this.sections, event.previousIndex, event.currentIndex);
  }

  toggleSectionPriority(){
    this.sectionActions[0].disable = this.originalSections.length <= 1;
  }

  /*** --------------------------------------------- ***/
  /*** ----------------- Manage Data --------------- ***/
  /*** --------------------------------------------- ***/

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

  resetSectionManage(){
    this.sectionMode = null;
    this.sections = null;
    this.s.resetForm();
  }

}

// DATA MANAGEMENT GLOBAL INTERFACES
export interface TagManageData {
  id?: number,
  course?: number,
  name?: string,
  color?: string,
  ruleNames?: string[]
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

// GLOBAL FUNCTIONS

export function initTagToManage(courseId: number, tag?: RuleTag): TagManageData {
  const tagData: TagManageData = {
    course: tag?.course ?? courseId,
    name : tag?.name ?? null,
    color : tag?.color ?? "#5E72E4",
    ruleNames: tag?.rules?.map(rule => rule.name) ?? []
  };
  if (tag) { tagData.id = tag.id; }
  return tagData;
}

export function initRuleToManage(courseId: number, sectionId: number, rule?: Rule): RuleManageData {
  const ruleData: RuleManageData = {
    course: rule?.course ?? courseId,
    section: rule?.section ?? sectionId,
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

export async function editRule(api: ApiHttpService, courseId: number, ruleToManage: RuleManageData, courseRules: Rule[]): Promise<void> {

  const ruleEdited = await api.editRule(clearEmptyValues(ruleToManage)).toPromise();
  ruleEdited.tags = await api.getRuleTags(ruleEdited.course, ruleEdited.id).toPromise();

  const index = courseRules.findIndex(rule => rule.id === ruleEdited.id);
  courseRules.splice(index, 1, ruleEdited);

}

export async function buildTable(api: ApiHttpService, themeService: ThemingService ,courseId: number, section: RuleSection): Promise<void> {
  section.loadingTable = true;

  section.showTable = false;
  setTimeout(() => section.showTable = true, 0);

  const table: { type: TableDataType; content: any }[][] = [];

  const rules = await api.getRulesOfSection(courseId, section.id).toPromise();
  rules.forEach(rule => {
    table.push([
      {type: TableDataType.NUMBER, content: {value: rule.position, valueFormat: 'none'}},
      {type: TableDataType.TEXT, content: {text: rule.name}},
      {type: TableDataType.CUSTOM, content: {html: '<div class="flex flex-col gap-2">' +
            rule.tags.sort((a,b) => a.name.localeCompare(b.name))
              .map(tag => '<div class="badge badge-md badge-' + themeService.hexaToColor(tag.color) + '">' + tag.name + '</div>').join('') +
            '</div>', searchBy: rule.tags.map(tag => tag.name).join(' ') }},
      {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: rule.isActive}},
      {type: TableDataType.ACTIONS, content: {actions: [
            Action.EDIT,
            {action: 'Duplicate', icon: 'tabler-copy', color: 'primary'},
            {action: 'Increase priority', icon: 'tabler-arrow-narrow-up', color: 'primary', disabled: rule.position === 0 },
            {action: 'Decrease priority', icon: 'tabler-arrow-narrow-down', color: 'primary', disabled: rule.position === rules.length - 1 },
            Action.REMOVE,
            Action.EXPORT]}
      }
    ]);
  });

  section.data = _.cloneDeep(table);
  section.loadingTable = false;
}

