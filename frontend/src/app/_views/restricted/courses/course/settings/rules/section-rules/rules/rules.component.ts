import {Component, OnInit, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import {Course} from "../../../../../../../../_domain/courses/course";
import {RuleTag} from "../../../../../../../../_domain/rules/RuleTag";
import {
  CodeTab,
  CustomFunction,
  OutputTab,
  ReferenceManualTab
} from "../../../../../../../../_components/inputs/code/input-code/input-code.component"
import {Subject} from "rxjs";

import * as _ from "lodash";
import {Rule} from "src/app/_domain/rules/rule";
import {clearEmptyValues} from "../../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import {RuleSection} from "../../../../../../../../_domain/rules/RuleSection";
import {ModalService} from "../../../../../../../../_services/modal.service";
import {moveItemInArray} from "@angular/cdk/drag-drop";
import {ThemingService} from "../../../../../../../../_services/theming/theming.service";

@Component({
  selector: 'app-rules',
  templateUrl: 'rules.component.html',
  styleUrls: ['./rule.component.scss']
})
export class RulesComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    discard: false,
    refreshing: false
  };

  course: Course;                               // Specific course in which rule system is being manipulated
  section: RuleSection;                         // Section in which rule is in

  rule: Rule;                                   // Rule being manipulated (null if new rule is being created)
  ruleToManage: RuleManageData;                 // Manage data
  tags: RuleTag[];                              // Rule tags
  nameTags : {value: string, text: string}[];   // Tags with names formatted for the select-input

  // Tutorial Modal
  showToast: boolean = false;
  gettingStartedIndex: number;
  gettingStarted: boolean;                      // Modal for tutorial at beginning
  videoElement: HTMLVideoElement | null = null;

  // Autogame metadata
  metadata: {[variable: string]: number}[];
  parsedMetadata: string;
  showAlert: boolean = false;

  // CODE-INPUT VARIABLES
  whenTabs: CodeTab[];
  thenTabs: CodeTab[];
  additionalToolsTabs: (CodeTab | OutputTab | ReferenceManualTab )[];
  debugSelection: string;
  functions: CustomFunction[];
  ELfunctions: CustomFunction[];
  namespaces: string[];
  isCompleted: boolean;

  // Input-select for assigning rules to tags
  previousSelected: string[];
  setTags: Subject<{value: string, text: string, innerHTML?: string, selected: boolean}[]> = new Subject();

  @ViewChild('r', {static: false}) r: NgForm;                       // rule form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    private themingService: ThemingService
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

        this.isIncomplete();
        this.prepareCodeInputTabs();
        if (!localStorage.getItem('viewedRulesTutorial')) {
          this.openTutorial();
          localStorage.setItem('viewedRulesTutorial', 'true');
        }
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
      [{ name: 'Code', type: "code", active: true,
        highlightQuery: '[] # COMPLETE THIS:',
        value: this.ruleToManage.whenClause,
        debug: true,
        mode: "python", placeholder: "Rule \'When\' clause",
        customFunctions: this.functions.concat(this.ELfunctions)}];

    this.thenTabs =
      [{ name: 'Code', type: "code", active: true,
        highlightQuery: '[] # COMPLETE THIS:',
        value: this.ruleToManage.thenClause,
        debug: false,
        mode: "python", placeholder: "Rule \'Then\' clause", customFunctions: this.functions }]

    this.additionalToolsTabs =
      [{ name: 'Manual', type: "manual", active: false, customFunctions: this.functions.concat(this.ELfunctions),
        namespaces: this.namespaces },
       { name: 'Metadata', type: "code", active: true, value: this.parsedMetadata, debug: false, placeholder: "Autogame global variables:"},
       { name: 'Debug', type: "output", active: false, running: null, debugOutput: true, value: null,
         tutorialMessage: '<strong>Select a breakpoint and start debugging!<br></strong> (Results will only show up until the 1st breakpoint)',
         runMessage: 'Start debugging' },
       { name: 'Preview Rule', type: "output", active: false, running: null, debugOutput: false, runMessage: 'Preview Rule', value: null }]
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

    // Remove 'gc' and 'transform' functions (not needed for rule editor)
    let index = this.functions.findIndex(fn => fn.keyword === 'gc');
    this.functions.splice(index, 1);
    index = this.functions.findIndex(fn => fn.keyword === 'transform');
    this.functions.splice(index, 1);

    for (let i = 0; i < this.functions.length; i++) {
      let description = this.functions[i].description;
      const startMarker = ":example:";
      const startIndex = description.indexOf(startMarker);

      if (startIndex !== -1) {
        this.functions[i].example = description.substring(startIndex + startMarker.length).trim();
        description = description.substring(0, startIndex).trim();
      }

      // Now 'description' contains the modified string without ':example:' and 'exampleText' contains the extracted text.
      this.functions[i].description = description;
      this.functions[i].returnType = "-> " + this.functions[i].returnType;
    }

    this.ELfunctions = await this.api.getELFunctions().toPromise();

    // set namespaces of functions
    let names = this.functions.concat(this.ELfunctions)
      .map(fn => fn.name).sort((a, b) => a.localeCompare(b));   // order by name
    this.namespaces = Array.from(new Set(names).values())
    moveItemInArray(this.namespaces, this.namespaces.indexOf('gamerules'), this.namespaces.length - 1);      // leave 'gamerules' at the end of array
  }

  async getSection(sectionID: number): Promise<void> {
    this.section = await this.api.getSectionById(this.course.id, sectionID).toPromise();
  }

  async getRule(ruleID: number): Promise<void> {
    this.rule = await this.api.getRuleById(this.course.id, ruleID).toPromise();
  }

  isIncomplete () {
    // FIXME -- hardcoded
    let query = "logs = [] # COMPLETE THIS:";

    // Should only check when editing rules (new rules have empty clauses)
    if (this.ruleToManage.whenClause && this.ruleToManage.thenClause) {
      this.isCompleted = !this.ruleToManage.whenClause.includes(query) && !this.ruleToManage.thenClause.includes(query);
    }
  }

  /*** ------------------------------------------------ ***/
  /*** -------------------- Actions ------------------- ***/
  /*** ------------------------------------------------ ***/

  async doAction(action: string): Promise<void>{
    if (action === 'add rule' || action === 'edit rule') {
      if (this.r.valid && this.ruleToManage.whenClause && this.ruleToManage.thenClause) {
        this.loading.action = true;
        await this.assignTags();
        (action === 'add rule') ? await this.api.createRule(clearEmptyValues(this.ruleToManage)).toPromise() :
          await this.api.editRule(clearEmptyValues(this.ruleToManage)).toPromise();

        await this.saveMetadata();

        AlertService.showAlert(AlertType.SUCCESS, 'Rule \'' + this.ruleToManage.name + '\' ' + (action === 'add rule' ? 'added': 'edited'));
        this.loading.action = false;

        await this.router.navigate(['rule-system/sections/' + this.section.id], {relativeTo: this.route.parent});

      } else if (!this.ruleToManage.whenClause || !this.ruleToManage.thenClause) {
        AlertService.showAlert(AlertType.ERROR, '\'When\' and \'Then\' clauses cannot be empty');

      } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');

    } else if (action === 'prepare closing') {
      ModalService.openModal('exit-management');
    } else if (action === 'exit page'){
      this.loading.discard = true;
      await this.router.navigate(['rule-system/sections/' + this.section.id], {relativeTo: this.route.parent});
      this.loading.discard = false;
    } else if (action === 'saving incomplete') {
      this.loading.discard = true;
      this.ruleToManage.isActive = false;
      await this.doAction('edit rule');
      this.loading.discard = false;
    }
  }

  async saveMetadata(){
    let updatedMetadata = _.cloneDeep(this.parsedMetadata);

    // remove comment lines from metadata and clean code before updating
    updatedMetadata = updatedMetadata
      .replace(/#.*/g, '') // Remove comments
      .replace(/^\s*[\r\n]/gm, '') // Remove empty lines
      .replace(/(\s*:\s*)/g, ':'); // Replace ' : ', ' :' and ': ' with ':'

    await this.api.updateMetadata(this.course.id, updatedMetadata).toPromise();
  }

  async preview(indexTab: number){
    let tab = this.additionalToolsTabs[indexTab];

    if (tab.active && (tab as OutputTab).running){
      // Debug
      if (indexTab === 2){
        console.log(this.debugSelection);
        if (this.debugSelection) {
          // FIXME
          // await this.api.previewFunction(this.course.id, selection.library, selection.functionName, selection.argumentsArray).toPromise();
          this.debugSelection = null;
        } else {
          (tab as OutputTab).running = false;
          AlertService.showAlert(AlertType.ERROR, 'No breakpoints selected');
        }

      }
      // Preview Rule
      else if (indexTab === 3){
        this.ruleToManage.whenClause = this.parseFunctions(this.ruleToManage.whenClause);
        this.ruleToManage.thenClause = this.parseFunctions(this.ruleToManage.thenClause);

        await this.api.previewRule(clearEmptyValues(this.ruleToManage)).toPromise();
      }
    }

  }

  // when refresh button is pressed
  async getPreviewOutput(indexTab: number){
    let tab = this.additionalToolsTabs[indexTab];

    if (tab.active && (tab as OutputTab).running) {

      // Debug
      if (indexTab === 2){
        (tab as OutputTab).value = await this.api.getPreviewFunctionOutput(this.course.id).toPromise();
      }

      // Preview Rule
      else if (indexTab === 3){
        (tab as OutputTab).value = await this.api.getPreviewRuleOutput(this.course.id).toPromise();
      }
    }
  }

  openTutorial(){
    this.gettingStartedIndex = 1;
    this.gettingStarted = true;
    ModalService.openModal('getting-started');
  }

  closeTutorial(){
    this.gettingStartedIndex = 0;
    this.gettingStarted = false;
    ModalService.closeModal('getting-started');
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

  async changeMetadata(newMetadata: string){
    this.showAlert = true;
    this.parsedMetadata = newMetadata;
  }

  getTheme(){
    return this.themingService.getTheme();
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
  isComplete?: boolean
  tags?: any[],
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
    isComplete: null,
    tags: rule?.tags ?? []
  };
  if (rule) {
    ruleData.id = rule.id;
  }
  return ruleData;
}
