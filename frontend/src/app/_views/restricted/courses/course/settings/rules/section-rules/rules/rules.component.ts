import {Component, OnInit, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {Course} from "../../../../../../../../_domain/courses/course";
import {RuleTag} from "../../../../../../../../_domain/rules/RuleTag";
import { tabInfo } from "../../../../../../../../_components/inputs/code/input-code/input-code.component"
import {Subject} from "rxjs";

import * as _ from "lodash";
import { Rule } from "src/app/_domain/rules/rule";
import {clearEmptyValues} from "../../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import {RuleSection} from "../../../../../../../../_domain/rules/RuleSection";
import {ModalService} from "../../../../../../../../_services/modal.service";

@Component({
  selector: 'app-rules',
  templateUrl: 'rules.component.html'
})
export class RulesComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    refreshing: false
  };

  course: Course;                               // Specific course in which rule system is being manipulated
  section: RuleSection;                         // Section in which rule is in

  rule: Rule;                                   // Rule being manipulated (null if new rule is being created)
  ruleToManage: RuleManageData;                 // Manage data
  tags: RuleTag[];                              // Rule tags
  nameTags : {value: string, text: string}[];   // Tags with names formatted for the select-input

  // Autogame metadata
  metadata: {[variable: string]: number}[];
  parsedMetadata: string;
  showAlert: boolean = false;

  whenTabs: tabInfo[];
  thenTabs: tabInfo[];
  additionalToolsTabs: tabInfo[];

  functions: { moduleId: string, name: string, keyword: string, description: string, args: {name: string, optional: boolean, type: any}[] }[];

  // Input-select for assigning rules to tags
  previousSelected: string[];
  setTags: Subject<{value: string, text: string, innerHTML?: string, selected: boolean}[]> = new Subject();

  @ViewChild('r', {static: false}) r: NgForm;                       // rule form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router
  ) {  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async ngOnInit() {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getTags(courseID);
      await this.getMetadata();
      await this.getRuleFunctions(courseID);

      this.route.params.subscribe(async childParams => {
        const sectionID = childParams.sectionId;
        await this.getSection(sectionID);
        const segment = this.route.snapshot.url[this.route.snapshot.url.length - 1].path;

        if (segment === 'new-rule') {
          // Prepare for creation
          this.ruleToManage = initRuleToManage(this.course.id, this.section.id);
          this.previousSelected = [];

        } else {
          await this.getRule(parseInt(segment));

          // Prepare for edition
          this.ruleToManage = initRuleToManage(this.course.id, this.section.id, this.rule);
          this.ruleToManage.tags = this.ruleToManage.tags.map(tag => {return tag.id + '-' + tag.name})

          this.previousSelected = _.cloneDeep(this.ruleToManage.tags);
        }

        this.prepareCodeInputTabs();
        this.loading.page = false;
      })
    })
  }

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getTags(courseID: number): Promise<void> {
    this.loading.refreshing = true;
    this.tags = await this.api.getTags(courseID).toPromise();

    // Get names in another variable for input-select
    let nameTags = [];
    for (let i = 0; i < this.tags.length ; i++){
      nameTags.push({value: this.tags[i].id + '-' + this.tags[i].name, text: this.tags[i].name});
    }
    this.nameTags = _.cloneDeep(nameTags);
    this.loading.refreshing = false;
  }

  prepareCodeInputTabs(){
    this.whenTabs =
      [{ name: 'Code', type: "code", show: true, value: this.ruleToManage.whenClause,
        mode: "python", placeholder: "Rule \'When\' clause", customFunctions: this.functions}]

    this.thenTabs =
      [{ name: 'Code', type: "code", show: true, value: this.ruleToManage.thenClause,
        mode: "python", placeholder: "Rule \'Then\' clause", customFunctions: this.functions}]

    this.additionalToolsTabs =
      [{ name: 'Metadata', type: "code", show: true, value: this.parsedMetadata, placeholder: "Autogame global variables:"},
       { name: 'Preview Function', type: "code" , show: false},
       { name: 'Output', type: "output", show: false }]
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

  async getRuleFunctions(courseID: number){
    this.functions = await this.api.getRuleFunctions(courseID).toPromise();
  }

  async getSection(sectionID: number): Promise<void> {
    this.section = await this.api.getSectionById(this.course.id, sectionID).toPromise();
  }

  async getRule(ruleID: number): Promise<void> {
    this.rule = await this.api.getRuleById(this.course.id, ruleID).toPromise();
  }

  /*** ------------------------------------------------ ***/
  /*** -------------------- Actions ------------------- ***/
  /*** ------------------------------------------------ ***/


  async doAction(action: string): Promise<void>{
    if (action === 'add rule' || action === 'edit rule') {
      if (this.r.valid) {
        this.loading.action = true;
        await this.assignTags();
        (action === 'add rule') ? await this.api.createRule(clearEmptyValues(this.ruleToManage)).toPromise() :
          await this.api.editRule(clearEmptyValues(this.ruleToManage)).toPromise();

        AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + this.ruleToManage.name + '\' added');
        this.loading.action = false;

        await this.router.navigate(['rule-system/sections/' + this.section.id], {relativeTo: this.route.parent});

      } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');

    } else if (action === 'prepare closing') {
      ModalService.openModal('exit-management');
    } else if (action === 'exit page'){
      await this.router.navigate(['rule-system/sections/' + this.section.id], {relativeTo: this.route.parent});

    }
  }

  async previewRule(event: string){
    console.log(event);
    await this.api.previewRule(clearEmptyValues(this.ruleToManage)).toPromise();
  }

  /*async createRule() {

    const newRule = await this.api.createRule(clearEmptyValues(this.ruleToManage)).toPromise();
    newRule.tags = await this.api.getRuleTags(newRule.course, newRule.id).toPromise();

    //this.sectionRules.push(newRule);
    // FIXME -- falta dar sort?

  }

  async editRule(): Promise<void> {

    const ruleEdited = await this.api.editRule(clearEmptyValues(this.ruleToManage)).toPromise();
    ruleEdited.tags = await this.api.getRuleTags(ruleEdited.course, ruleEdited.id).toPromise();

    //const index = this.sectionRules.findIndex(rule => rule.id === ruleEdited.id);
    //this.sectionRules.splice(index, 1, ruleEdited);

  }*/

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  // Function to format tags before sending it to DB
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

  /*** --------------------------------------------- ***/
  /*** -------------- Data Management -------------- ***/
  /*** --------------------------------------------- ***/


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


export function initRuleToManage(courseID: number, sectionID: number, rule?: Rule): RuleManageData {
  const ruleData: RuleManageData = {
    course: rule?.course ?? courseID,
    section: rule?.section ?? sectionID,
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
