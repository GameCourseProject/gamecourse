import {Component, OnInit, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {Course} from "../../../../../../../../_domain/courses/course";
import {RuleTag} from "../../../../../../../../_domain/rules/RuleTag";
import {
  customFunction,
  codeTab,
  outputTab,
  referenceManualTab
} from "../../../../../../../../_components/inputs/code/input-code/input-code.component"
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

  // CODE-INPUT VARIABLES
  whenTabs: codeTab[];
  thenTabs: codeTab[];
  additionalToolsTabs: (codeTab | outputTab | referenceManualTab )[];
  functions: customFunction[];
  ELfunctions: customFunction[];

  // Input-select for assigning rules to tags
  previousSelected: string[];
  setTags: Subject<{value: string, text: string, innerHTML?: string, selected: boolean}[]> = new Subject();

  tabOutput: string;

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
      await this.getCustomFunctions(courseID);

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
      [{ name: 'Code', type: "code", active: true, value: this.ruleToManage.whenClause,
        mode: "python", placeholder: "Rule \'When\' clause",
        customFunctions: this.functions.concat(this.ELfunctions)}];

    this.thenTabs =
      [{ name: 'Code', type: "code", active: true, value: this.ruleToManage.thenClause,
        mode: "python", placeholder: "Rule \'Then\' clause", customFunctions: this.functions }]

    this.additionalToolsTabs =
      [{ name: 'Manual', type: "manual", active: false, customFunctions: this.functions.concat(this.ELfunctions) },
       { name: 'Metadata', type: "code", active: true, value: this.parsedMetadata, placeholder: "Autogame global variables:"},
       { name: 'Preview Function', type: "code", active: false, placeholder: "TODO", readonly: true},
       { name: 'Preview Rule', type: "output", active: false, running: null, value: null }]
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

  async getCustomFunctions(courseID: number){
    this.functions = await this.api.getRuleFunctions(courseID).toPromise();

    for (let i = 0; i < this.functions.length; i++) {
      let description = this.functions[i].description;
      let returnType = description.indexOf(":returns:");
      let finalString = description.slice(returnType);
      this.functions[i].returnType = finalString.replace(":returns:", "-> ");

      this.functions[i].description = description.slice(0, returnType);
    }

    this.ELfunctions = await this.api.getELFunctions().toPromise();
    this.ELfunctions.map(ELfunction => ELfunction.returnType = "-> " + ELfunction.returnType);
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

        await this.saveMetadata();

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

  async saveMetadata(){
    let updatedMetadata = _.cloneDeep(this.parsedMetadata);
    // remove comment lines from metadata and clean code before updating
    updatedMetadata = updatedMetadata.replace(/#[^\n]*\n/g, '');  // comments
    updatedMetadata = updatedMetadata.replace(/\n\s*\n/g, '');    // empty lines
    updatedMetadata = updatedMetadata.replace(/(\s*:\s*)/g, ":"); // " : " or ": " or " :" replacing with ":"

    await this.api.updateMetadata(this.course.id, updatedMetadata).toPromise();
  }

  async previewRule(){
    this.ruleToManage.whenClause = this.parseFunctions(this.ruleToManage.whenClause);
    this.ruleToManage.thenClause = this.parseFunctions(this.ruleToManage.thenClause);

    await this.api.previewRule(clearEmptyValues(this.ruleToManage)).toPromise();
  }

  async getPreviewRuleOutput() {
    this.tabOutput = await this.api.getPreviewRuleOutput(this.course.id).toPromise();
    setTimeout(()=> this.tabOutput = null, 4000);
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

  // Parses functions and adds 'gc.library' if needed before sending it to backend
  parseFunctions(clause: string): string{
    const regexPattern: RegExp = /\b([a-zA-Z_][a-zA-Z0-9_]*)\(/g;
    let clauseArray = clause.match(regexPattern)?.map(match => match.slice(0, -1)) || [];

    for (let i = 0; i < clauseArray.length; i++){
      let ELfunction = this.ELfunctions.find(myFunction => myFunction.keyword === clauseArray[i]);

      if (ELfunction){
        // needs 'gc.library.' to work
        let regex = new RegExp(`\\b${clauseArray[i]}`, "g");
        clause = clause.replace(regex, "gc." + ELfunction.name + "." + ELfunction.keyword);
      }
    }

    return clause;
  }

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
