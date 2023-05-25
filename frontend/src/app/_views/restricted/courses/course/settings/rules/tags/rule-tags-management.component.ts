import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {NgForm} from "@angular/forms";
import {Course} from "../../../../../../../_domain/courses/course";
import {ActivatedRoute} from "@angular/router";
import {RuleTag} from "../../../../../../../_domain/rules/RuleTag";
import {ThemingService} from "../../../../../../../_services/theming/theming.service";
import {clearEmptyValues} from "../../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../../_services/alert.service";
import {ModalService} from "../../../../../../../_services/modal.service";
import {Rule} from "../../../../../../../_domain/rules/rule";

import {initTagToManage, TagManageData} from "../sections.component";
import * as _ from "lodash";
import {Subject} from "rxjs";

export {TagManageData} from "../sections.component";

@Component({
  selector: 'app-rule-tags-management',
  templateUrl: './rule-tags-management.component.html'
})
export class RuleTagsManagementComponent implements OnInit {

  @Input() course: Course;                                                    // Specific course in which rule system is being manipulated
  @Input() mode: 'manage tags' | 'add tag' | 'remove tag' | 'edit tag';       // Available actions regarding tag management

  tags: RuleTag[];                                                   // Course tags
  rules: Rule[];                                                     // Course rules

  //@Output() newTags = new EventEmitter<RuleTag[]>();                          // Changed tags to be emitted
  //@Output() newRules = new EventEmitter<Rule[]>();                            // Changed rules to be emitted
  @Output() tagMode = new EventEmitter<'manage tags' | 'add tag' | 'remove tag' | 'edit tag'>();                            // Changed rules to be emitted

  // Available colors for tags
  colors: string[] = ["#5E72E4", "#EA6FAC", "#1EA896", "#38BFF8", "#36D399", "#FBBD23", "#EF6060"];

  loading = {
    management: false,
    action: false,
    refreshing: false
  };

  tagToManage: TagManageData;                                                  // Manage Data
  tagEdit: string = "";                                                        // String with name of tag to be edited

  // Input-select for assigning tags to rules
  previousSelected: string[];
  setRules: Subject<{value: string, text: string, innerHTML?: string, selected: boolean}[]> = new Subject();
  ruleNames: {value: any, text: string}[];                                     // Rule Names for input-select

  @ViewChild('t', {static: false}) t: NgForm;                                  // tag form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public themeService: ThemingService
  ) { }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async ngOnInit() {
    this.route.parent.params.subscribe();
    await this.getTags();
    await this.getRuleNames();
  }

  async getRuleNames()  {
    this.rules = await this.api.getCourseRules(this.course.id).toPromise();
    this.ruleNames = _.cloneDeep(Object.values(this.rules).map(rule => {
      return {value: rule.name, text: rule.name}
    }));
  }

  async getTags(): Promise<void> {
    this.tags = await this.api.getTags(this.course.id).toPromise();
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareModal(action: string, tag?: RuleTag) {
    this.tagToManage = initTagToManage(this.course.id, tag);
    this.previousSelected = tag ? this.tagToManage.ruleNames : [];


    this.mode = switchMode(action);

    function switchMode(action: string) {
      switch (action) {
        case "create" : return 'add tag';
        case "edit" : return 'edit tag';
        case "delete" : return 'remove tag';
      }
      return "manage tags";
    }

    if (this.mode === 'remove tag'){
      ModalService.openModal(this.mode);

    } else if (this.mode === 'add tag' || this.mode === 'edit tag') {
      this.loading.refreshing = true;
      setTimeout(() => this.loading.refreshing = false, 0);

      if (this.mode === 'edit tag'){
        this.tagEdit = _.cloneDeep(this.tagToManage.name);
      }

      ModalService.openModal('create-and-edit-tag');
    }
  }

  async doAction(action: string): Promise<void> {

    if (action === 'remove tag') {
      // remove from DB
      await this.api.removeTag(this.course.id, this.tagToManage.id).toPromise();

      // remove from UI
      const tag = this.tags.find(tag => tag.id === this.tagToManage.id);
      const index = this.tags.findIndex(tag => tag.id === this.tagToManage.id);
      this.tags.splice(index, 1);

      this.assignRules(tag, action);

      AlertService.showAlert(AlertType.SUCCESS, "Tag deleted successfully");
      ModalService.closeModal(action);

    } else if (action === 'add tag' || action === 'edit tag') {
      if (this.t.valid){
        const color = this.colors.find(color => color === this.tagToManage.color);

        if (color) {
          let tag: RuleTag; let message: string;

          if (action === 'edit tag'){
            tag = await this.api.editTag(this.tagToManage).toPromise(); // Update DB

            // Update UI
            const index = this.tags.findIndex(tag => tag.id === this.tagToManage.id);
            this.tags.splice(index, 1, tag);

            message = "Tag edited successfully"

          } else {
            tag = await this.api.createTag(clearEmptyValues(this.tagToManage)).toPromise(); // Update DB
            this.tags.push(tag); // Update UI
            message = "Tag created successfully";
          }

          this.assignRules(tag, action);

          AlertService.showAlert(AlertType.SUCCESS, message);

          ModalService.closeModal('create-and-edit-tag');
          this.resetTagManage();
          this.previousSelected = [];

        } else { AlertService.showAlert(AlertType.ERROR, "Tag color must be selected from one of the available options"); }
      } else { AlertService.showAlert(AlertType.ERROR, "Invalid form"); }

    }
  }

  closeManagement(){
    ModalService.closeModal('manage tags');
    this.mode = null;
    this.tagMode.emit(null);
    //.newTags.emit(this.tags);
  }

  async getRulesWithTag(tag: RuleTag): Promise<Rule[]>{
    return await this.api.getRulesWithTag(tag.id).toPromise();
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  hexaToColor(color:string): string{
    return this.themeService.hexaToColor(color);
  }

  async assignRules(tag: RuleTag, action: string): Promise<void> {
    //let rulesToEmit: Rule[] = [];
    let ruleNames = this.tagToManage.ruleNames;

    for (let i = 0; i < ruleNames.length; i++) {
      const rule = this.rules.find(rule => rule.name === ruleNames[i]);

      let hasTag = rule.tags.includes(tag);
      if (hasTag && action === 'remove tag'){
        let index = rule.tags.findIndex(ruleTag => ruleTag.id === tag.id);
        rule.tags.splice(index, 1, tag);

      } else if (!hasTag && (action === 'add tag' || action === 'edit tag')){
        rule.tags.push(tag);
      }

      //let ruleToManage = initRuleToManage(this.course.id, rule.section, rule);
      await this.api.editRule(rule).toPromise();
      //rulesToEmit.push(rule);
    }

    //if (rulesToEmit.length > 0) this.newRules.emit(rulesToEmit);
  }

  updateRules(selectedRuleNames: string[]): void {
    if (selectedRuleNames.length > this.previousSelected.length){ // adding rule
      const ruleToAdd = selectedRuleNames.filter(ruleName => !this.previousSelected.includes(ruleName))[0];

      if (!selectedRuleNames.includes(ruleToAdd)) selectedRuleNames.push(ruleToAdd);

    } else { // removing rule
      const ruleToRemove = this.previousSelected.filter(ruleName => !selectedRuleNames.includes(ruleName))[0];

      if (selectedRuleNames.includes(ruleToRemove)) selectedRuleNames.splice(selectedRuleNames.indexOf(ruleToRemove), 1);
    }

    // Select them
    this.ruleNames.map(option => {
      option['selected'] = selectedRuleNames.includes(option.value);
      return option;
    });

    this.setRules.next(this.ruleNames as {value: any, text: string, innerHTML: string, selected: boolean}[]);
    this.previousSelected = selectedRuleNames;
  }

  /*** --------------------------------------------- ***/
  /*** ----------------- Manage Data --------------- ***/
  /*** --------------------------------------------- ***/

  resetTagManage() {
    this.tagToManage = initTagToManage(this.course.id);
    this.t.resetForm();
  }
}


