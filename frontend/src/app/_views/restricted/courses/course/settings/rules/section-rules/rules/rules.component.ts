import {Component, OnInit} from "@angular/core";
import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../../../_domain/courses/course";
import {RuleTag} from "../../../../../../../../_domain/rules/RuleTag";
import {Subject} from "rxjs";

import * as _ from "lodash";
import { Rule } from "src/app/_domain/rules/rule";

@Component({
  selector: 'app-rules',
  templateUrl: 'rules.component.html'
})
export class RulesComponent implements OnInit {

  course: Course;
  rule: Rule;

  tags: RuleTag[];
  nameTags : {value: string, text: string}[];                       // Tags with names formatted for the select-input


  // Autogame metadata
  metadata: {[variable: string]: number}[];
  parsedMetadata: string;

  // Input-select for assigning rules to tags
  previousSelected: string[];
  setTags: Subject<{value: string, text: string, innerHTML?: string, selected: boolean}[]> = new Subject();

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) {  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async ngOnInit(){
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getTags(courseID);
      await this.getMetadata();

      this.route.params.subscribe(async childParams => {
        const ruleID = childParams.id;
        await this.getRule(ruleID);
      })
    })
  }

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getTags(courseID: number): Promise<void> {
    this.tags = await this.api.getTags(courseID).toPromise();

    // Get names in another variable for input-select
    let nameTags = [];
    for (let i = 0; i < this.tags.length ; i++){
      nameTags.push({value: this.tags[i].id + '-' + this.tags[i].name, text: this.tags[i].name});
    }
    this.nameTags = _.cloneDeep(nameTags);
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

  async getRule(ruleID: number): Promise<void> {
    this.rule = await this.api.getRuleById(this.course.id, ruleID).toPromise();
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

}
