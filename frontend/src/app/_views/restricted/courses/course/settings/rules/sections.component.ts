import {Component, OnInit, ViewChild} from '@angular/core';
import {Reduce} from "../../../../../../_utils/lists/reduce";
import {Action} from 'src/app/_domain/modules/config/Action';

import {Course} from "../../../../../../_domain/courses/course";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {ModalService} from "../../../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {RuleSection} from "../../../../../../_domain/rules/RuleSection";
import {ThemingService} from "../../../../../../_services/theming/theming.service";

import * as _ from "lodash";
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";
import {CodeTab} from "../../../../../../_components/inputs/code/input-code/input-code.component";

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
  filteredSections: RuleSection[] = [];             // Section search

  metadata: {[variable: string]: number}[];
  parsedMetadata: string;
  metadataCodeInput: CodeTab[];

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

    for (let i = 0; i <  this.originalSections.length; i++) {
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
        if (((this.filteredSections[i].name).toLowerCase()).includes(searchQuery.toLowerCase())) {
          sections.push(this.filteredSections[i]);
        }
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
      else {
        await this.parseMetadata();
        this.metadataCodeInput =
          [{ name: 'Metadata', type: "code", active: true, value: this.parsedMetadata,
            debug: false, placeholder: "Autogame global variables:"}];
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
      this.sectionToManage.loading = true;

      const newSection = await this.api.createSection(clearEmptyValues(this.sectionToManage)).toPromise();
      this.originalSections.unshift(newSection);

      this.resetSectionManage();

      this.sectionToManage.loading = false;

      AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + newSection.name + '\' added');
      ModalService.closeModal('manage-section');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editSection(): Promise<void>{
    if (this.s.valid) {
      this.sectionToManage.loading = true;

      const sectionEdited = await this.api.editSection(clearEmptyValues(this.sectionToManage)).toPromise();
      const index = this.originalSections.findIndex(section => section.id === sectionEdited.id);
      this.originalSections.splice(index, 1, sectionEdited);

      this.sectionToManage.loading = false;
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
  }

  /*** --------------------------------------------- ***/
  /*** ------------------- Helpers ----------------- ***/
  /*** --------------------------------------------- ***/

  async toggleStatus(section: RuleSection){
    section.loading = true;

    let newSection = await this.api.editSection(section).toPromise();

    const index = this.originalSections.findIndex(section => section.id === newSection.id);
    this.originalSections.splice(index, 1, newSection);

    section.loading = false;
    AlertService.showAlert(AlertType.SUCCESS, 'Section \'' + newSection.name + '\' ' + (newSection.isActive ? 'enabled' : 'disabled'));
  }

  dragAndDropDisabled(): boolean {
    // Check if any section.name is "Graveyard"
    return this.sections.some((section) => section.name === "Graveyard");
  }

  async drop(event: CdkDragDrop<string[]>) {
    this.sections = _.cloneDeep(this.originalSections);

    moveItemInArray(this.originalSections, event.previousIndex, event.currentIndex);
    setTimeout(() => this.arrangeSections = false, 2000);

    if (this.originalSections[this.originalSections.length - 1].name !== 'Graveyard') {
      AlertService.showAlert(AlertType.ERROR, '\'Graveyard\' section must be at the end');
      this.originalSections = this.sections;
      await this.saveSectionPriority();
      return;
    }

    if (JSON.stringify(this.sections) !== JSON.stringify(this.originalSections)) {
      await this.saveSectionPriority();
      AlertService.showAlert(AlertType.SUCCESS, 'Sections\' priority saved successfully');
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
    updatedMetadata = updatedMetadata
      .replace(/#.*/g, '') // Remove comments
      .replace(/^\s*[\r\n]/gm, '') // Remove empty lines
      .replace(/(\s*:\s*)/g, ':'); // Replace ' : ', ' :' and ': ' with ':'


    await this.api.updateMetadata(this.course.id, updatedMetadata).toPromise();
    AlertService.showAlert(AlertType.SUCCESS, 'Metadata updated successfully');
    ModalService.closeModal('manage-metadata');
    this.parsedMetadata = null;
    this.loading.action = false;
  }

  resetSectionManage(){
    this.sectionMode = null;
    this.sections = null;
    this.parsedMetadata = null;
    if (this.s) this.s.resetForm();
  }


  initSectionToManage(section?: RuleSection): SectionManageData {
    const sectionData: SectionManageData = {
      course: section?.course ?? this.course.id,
      name: section?.name ?? null,
      position: section?.position ?? null,
      isActive: section?.isActive ?? true,
      loading: false,
      roleNames: section?.roles?.map(role => role.name) ?? []
    };
    if (section) sectionData.id = section.id;
    return sectionData;
  }

}

export interface SectionManageData {
  id?: number,
  course?: number,
  name?: string,
  position?: number,
  isActive?: boolean,
  loading?: boolean,
  roleNames?: string[]
}
