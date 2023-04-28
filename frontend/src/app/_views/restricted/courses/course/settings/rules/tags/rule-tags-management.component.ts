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

import {initTagToManage, TagManageData} from "../rules.component";
import * as _ from "lodash";
import {timeout} from "rxjs/operators";
import {Subject} from "rxjs";

export {TagManageData} from "../rules.component";

@Component({
  selector: 'app-rule-tags-management',
  templateUrl: './rule-tags-management.component.html'
})
export class RuleTagsManagementComponent implements OnInit {

  @Input() course: Course;
  @Input() mode: 'manage tags' | 'add tag' | 'remove tag' | 'edit tag';

  @Input() tags: RuleTag[];
  @Input() rules: Rule[];

  @Output() newTags = new EventEmitter<RuleTag[]>();
  @Output() newRules = new EventEmitter<Rule[]>();

  // FIXME -- Should consider light and dark theme
  colors: string[] = ["#5E72E4", "#EA6FAC", "#1EA896", "#38BFF8", "#36D399", "#FBBD23", "#EF6060"];

  loading = {
    management: false,
    action: false,
    refreshing: false
  };

  tagToManage: TagManageData;
  tagEdit: string = "";
  //tagRules: string[] = [];
  ruleNames: {value: any, text: string}[];
  previousSelected: string[];
  setRules: Subject<{value: string, text: string, innerHTML?: string, selected: boolean}[]> = new Subject();

  @ViewChild('t', {static: false}) t: NgForm;       // tag form

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public themeService: ThemingService
  ) { }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  ngOnInit(): void {
    this.route.parent.params.subscribe();
    this.ruleNames = _.cloneDeep(this.getRuleNames()); // FIXME -- test if works
  }

  getRuleNames(): {value: any, text: string}[] {
    return Object.values(this.rules).map(rule => {
      return {value: rule.name, text: rule.name}
    });
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async prepareModal(action: string, tag?: RuleTag) {
    this.tagToManage = initTagToManage(this.course.id, tag);
    this.previousSelected = tag ? this.tagToManage.ruleNames : [];

    console.log("linha 83-AQUI: ", this.tagToManage);

    this.mode = switchMode(action);

    function switchMode(action?: string) {
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
      //this.tagRules = [];

      if (this.mode === 'edit tag'){
        //this.tagRules = (await this.getRulesWithTag(tag)).map(rule => { return rule.name; });
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
      const index = this.tags.findIndex(tag => tag.id === this.tagToManage.id);
      this.tags.splice(index, 1);

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
          this.assignRules(tag);

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
    this.newTags.emit(this.tags);
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

  /*getRuleIds(): number[]{
    return this.tagRules.map(tagRule => {
      let rules = this.rules.map(rule => {
        return rule.name
      });
      let ruleIndex = rules.findIndex(element => element === tagRule);
      return this.rules[ruleIndex].id ?? null
    });
  }*/

  assignRules(tag: RuleTag): void {
    let rulesToEmit: Rule[] = [];
    let ruleNames = this.tagToManage.ruleNames;
    console.log("TagToManage.ruleNames: ",this.tagToManage.ruleNames);

    for (let i = 0; i < ruleNames.length; i++) {
      const rule = this.rules.find(rule => rule.name === ruleNames[i]);
      console.log("HERE: ", rule);
      //rule.tags.push(tag);
      rulesToEmit.push(rule);
    }

    console.log(rulesToEmit);
    if (rulesToEmit.length > 0) this.newRules.emit(rulesToEmit);
  }

  updateRules(selectedRuleNames: string[]): void {
    console.log("selectedRuleNames: ", selectedRuleNames);
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

  /*initSelect(){
    this.previousSelected = [];
    return {rulesToAdd: [], ruleNames: []};
  }

   Comment for now:
  resetSelect(){
    this.mode = null;
    this.initSelect();
    this.t.resetForm();
  }*/

  /*** --------------------------------------------- ***/
  /*** ----------------- Manage Data --------------- ***/
  /*** --------------------------------------------- ***/

  resetTagManage() {
    this.tagToManage = initTagToManage(this.course.id);
    //this.tagRules = [];
    //this.initSelect();
    this.t.resetForm();
  }
}


