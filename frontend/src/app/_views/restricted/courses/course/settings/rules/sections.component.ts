import {Component, OnInit, ViewChild} from '@angular/core';
import {Reduce} from "../../../../../../_utils/lists/reduce";
import {Action} from 'src/app/_domain/modules/config/Action';
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";

import {Rule} from "../../../../../../_domain/rules/rule";
import {Course} from "../../../../../../_domain/courses/course";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {ModalService} from "../../../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {RuleSection} from "../../../../../../_domain/rules/RuleSection";
import {RuleTag} from "../../../../../../_domain/rules/RuleTag";
import {ThemingService} from "../../../../../../_services/theming/theming.service";

import * as _ from "lodash";
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";
import {tabInfo} from "../../../../../../_components/inputs/code/input-code/input-code.component";

@Component({
  selector: 'app-rules',
  templateUrl: './sections.component.html',
  styleUrls: ['./sections.component.scss']
})
export class SectionsComponent implements OnInit {

  loading = {
    page: true,
    action: false
  }

  course: Course;                                   // Specific course in which rule system is being manipulated

  originalSections: RuleSection[] = [];             // Sections of course
  sections: RuleSection[] = [];                     // Copy of original sections (used as auxiliary variable for setting priority)
  nrRules: {[sectionId: number]: number} = [];      // Dictionary with number of rules per section
  filteredSections: RuleSection[] = [];             // Section search

  // Section actions -- general manipulation (create/edit/remove/set priority/see details)
  sectionActions: {action: Action | string, icon?: string, outline?: boolean, dropdown?: {action: Action | string, icon?: string}[],
      color?: "ghost" | "primary" | "secondary" | "accent" | "neutral" | "info" | "success" | "warning" | "error", disable?: boolean}[]  = [
      {action: 'metadata', icon: 'tabler-book', color: 'secondary'},
      {action: 'manage tags', icon: 'tabler-tags', color: 'secondary'},
      {action: 'add section', icon: 'feather-plus-circle', color: 'primary'}];

  tagMode : 'manage tags' | 'add tag' | 'remove tag' | 'edit tag';    // available actions for tags
  sectionMode: 'add section' | 'edit section' | 'remove section' | 'metadata';  // available actions for sections

  arrangeSections: boolean = false;

  // MANAGE DATA
  section: RuleSection;
  sectionToManage: SectionManageData;

  // SEARCH & FILTER
  reduce = new Reduce();
  sectionsToShow: RuleSection[] = [];

  metadata: {[variable: string]: number}[];
  parsedMetadata: string;
  metadataCodeInput: tabInfo[];

  @ViewChild('s', {static: false}) s: NgForm;       // Section form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    public themeService: ThemingService
  ) { }


  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getCourseSections(courseID);

      this.loading.page = false;

    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }


  async getCourseSections(courseID: number): Promise<void> {
    this.originalSections = (await this.api.getCourseSections(courseID).toPromise()).sort(function (a, b) {
      return a.position - b.position; });
    this.filteredSections = this.originalSections;
    this.sectionsToShow = this.originalSections;

    for (let i = 0; i <  this.originalSections.length; i++){
      // Get number of rules per section
      this.nrRules[this.originalSections[i].id] = (await this.api.getRulesOfSection(this.course.id, this.originalSections[i].id).toPromise()).length;
    }
  }


  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.originalSections, query);
  }

  filterSections(searchQuery?: string) {
    if (searchQuery) {
      let sections: RuleSection[] = [];
      for (let i = 0; i < this.filteredSections.length; i++){
        if (((this.filteredSections[i].name).toLowerCase()).includes(searchQuery.toLowerCase())) sections.push(this.filteredSections[i]);
      }
      this.sectionsToShow = sections;
    }
    else this.sectionsToShow = this.originalSections;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareManagement(action: string, section?: RuleSection) {
    // Actions for sections general manipulation (adding/editing/removing/metadata actions)
     if (action === 'metadata'|| action === 'add section' || action === 'edit section' || action === 'remove section') {
       this.sectionMode = action;

       if (action !== 'metadata') this.sectionToManage = this.initSectionToManage(section);
       else{
         await this.parseMetadata();
         this.metadataCodeInput =
           [{ name: 'Metadata', type: "code", show: true, value: this.parsedMetadata,
             placeholder: "Autogame global variables:"}];
       }

       let modal = (action === 'remove section') ? action : (action === 'metadata') ?
         'manage-metadata' : 'manage-section';
       ModalService.openModal(modal);

     }

    // Actions for manipulation INSIDE sections (seeing details -- aka everything related to the rules)
    else if (action === 'see section'){
       await this.router.navigate(['rule-system/sections/' + section.id ], {relativeTo: this.route.parent});
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

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + this.sectionToManage.name + '\' removed');

    ModalService.closeModal('remove section');
    this.resetSectionManage();
  }

  async saveSectionPriority(): Promise<void>{
    this.loading.action = true;

    for (let i = 0; i < this.originalSections.length; i++){
      this.originalSections[i].position = i;
      let section = this.initSectionToManage(this.originalSections[i]);
      await this.api.editSection(section).toPromise();
    }

    this.filterSections();
    this.resetSectionManage();

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Sections\' priority saved successfully');
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Tags ------------------- ***/
  /*** --------------------------------------------- ***/

  /*async assignRules(event: Rule[]) {
    for (let i = 0; i < event.length; i++) {
      this.ruleToManage = initRuleToManage(this.course.id, event[i].section, event[i]);
      this.ruleToManage.tags = (event[i].tags).map(tag => {
        return initTagToManage(this.course.id, tag)
      });
      await editRule(this.api, this.course.id, this.ruleToManage, this.courseRules);
    }
  }*/

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

  async drop(event: CdkDragDrop<string[]>) {
    this.sections = _.cloneDeep(this.originalSections);
    moveItemInArray(this.originalSections, event.previousIndex, event.currentIndex);
    setTimeout(() => this.arrangeSections = false, 2000);

    if (JSON.stringify(this.sections) !== JSON.stringify(this.originalSections)) {
      await this.saveSectionPriority();
    }
  }

  showWarning(){
    if (this.sectionsToShow.length > 0) this.arrangeSections = true;
  }

  /*** --------------------------------------------- ***/
  /*** ----------------- Manage Data --------------- ***/
  /*** --------------------------------------------- ***/

  async parseMetadata() {
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

  async saveMetadata(){
    this.loading.action = true;

    let updatedMetadata = _.cloneDeep(this.parsedMetadata);
    // remove comment lines from metadata and clean code before updating
    updatedMetadata = updatedMetadata.replace(/#[^\n]*\n/g, '');  // comments
    updatedMetadata = updatedMetadata.replace(/\n\s*\n/g, '');    // empty lines
    updatedMetadata = updatedMetadata.replace(/(\s*:\s*)/g, ":"); // " : " or ": " or " :" replacing with ":"

    await this.api.updateMetadata(this.course.id, updatedMetadata).toPromise();

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Metadata updated successfully');
    ModalService.closeModal('manage-metadata');
  }

  resetSectionManage(){
    this.sectionMode = null;
    this.sections = null;
    this.parsedMetadata = null;
    if (this.s) this.s.resetForm();
  }


  initSectionToManage(section?: RuleSection): SectionManageData{
    const sectionData: SectionManageData = {
      course: section?.course ?? this.course.id,
      name: section?.name ?? null,
      position: section?.position ?? null
    };
    if (section) sectionData.id = section.id;
    return sectionData;
  }

}

export interface SectionManageData {
  id?: number,
  course?: number,
  name?: string,
  position?: number
}
