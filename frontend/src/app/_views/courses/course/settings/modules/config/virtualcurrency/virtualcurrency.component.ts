import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {ActionsToRemove} from "../../../../../../../_domain/virtualcurrency/actionstoremove";
import {exists} from "../../../../../../../_utils/misc/misc";
import {Course} from "../../../../../../../_domain/courses/course";
import {Team} from "../../../../../../../_domain/teams/team";
import {TeamData} from "../teams/teams.component";
import {User} from "../../../../../../../_domain/users/user";
import {Tier} from "../../../../../../../_domain/skills/tier";

@Component({
  selector: 'app-virtualcurrency',
  templateUrl: './virtualcurrency.component.html',
  styleUrls: ['./virtualcurrency.component.scss']
})
export class VirtualcurrencyComponent implements OnInit {

  loading: boolean;
  hasUnsavedChanges: boolean;
  isSkillModuleEnabled: boolean;

  courseID: number;

  course: Course;
  actionsToRemove: ActionsToRemove[] = [];
  participationTypes = ["attended lab", "attended lecture", "course emperor", "forum add discussion", "forum add post","forum update post","forum upload post","graded post","hall of fame","initial bonus","lab grade", "lab king","page viewed","participated in focus groups","participated in lecture","peerforum add discussion","peerforum add post","peerforum upload post","peergraded post","popular choice award (presentation)","presentation grade","presentation king","questionnaire resumed","questionnaire submitted","questionnaire viewed","quiz created","quiz grade","quiz king","quiz viewed","replied to questionnaires","resource view","subscribe forum","subscribe peerforum","suggested presentation subject","unsubscribe forum","unsubscribe eerforum","url viewed"];

  skillsVars = {
    skillCost: null,
    wildcardCost: null,
    attemptRating: null,
    costFormula: null,
    incrementCost: null,
  }
  newActionToRemove: ActionToRemoveData = {
    name: "",
    description: "",
    type: "",
    tokens: null,
  };

  mode: 'add' | 'edit';

  actionToRemoveToEdit: ActionsToRemove;
  actionToRemoveToDelete: ActionsToRemove;

  isRemoveActionModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  isTestModalOpen: boolean;

  types: [];


  saving: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getActionsToRemove()
      this.getCurrencySkillVars();
      this.isSkillEnabled();
    });
  }

  getActionsToRemove() {
    this.loading = true;
    /*this.api.getActionsToRemove(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        actionsToRemove => this.actionsToRemove = actionsToRemove,
        error => ErrorService.set(error)
      );*/
  }

  getCurrencySkillVars() {
    this.loading = true;
    this.api.getCurrencySkillVars(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        vars => {
          this.skillsVars.skillCost = vars.skillCost;
          this.skillsVars.wildcardCost = vars.wildcardCost;
          this.skillsVars.attemptRating = vars.attemptRating;
          this.skillsVars.costFormula = vars.costFormula;
          this.skillsVars.incrementCost = vars.incrementCost;
        },
        error => ErrorService.set(error)
      )
  }

  saveCurrencySkillVars() {
    this.loading = true;

    const currSkillsVars = {
      skillCost: this.skillsVars.skillCost,
      wildcardCost: this.skillsVars.wildcardCost,
      attemptRating: this.skillsVars.attemptRating,
      costFormula: this.skillsVars.costFormula,
      incrementCost: this.skillsVars.incrementCost

    }

    this.api.setCurrencySkillVars(this.courseID, currSkillsVars)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => this.getCurrencySkillVars(),
        error => ErrorService.set(error)
      )
  }

  isSkillVarReadyToSubmit(): boolean {
    return exists(this.skillsVars.skillCost) && /*!this.skillsVars.skillCost.isEmpty() &&*/
      exists(this.skillsVars.wildcardCost) && /*!this.skillsVars.wildcardCost.isEmpty() &&*/
      exists(this.skillsVars.attemptRating) && /*!this.skillsVars.attemptRating.isEmpty() &&*/
      exists(this.skillsVars.costFormula) && /*!this.skillsVars.costFormula.isEmpty() &&*/
      exists(this.skillsVars.incrementCost) /*&& !this.skillsVars.incrementCost.isEmpty()*/;
  }

  isSkillEnabled(): void {
    this.api.isSkillsEnabled(this.courseID).subscribe(myBool =>
      this.isSkillModuleEnabled = myBool);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initItem(type: 'actionToAward', item?: any): void {
    if (type === 'actionToAward') {
      this.newActionToRemove = {
        name: item?.name || "",
        description: item?.description || "",
        type: item?.type || "",
        tokens: item?.tokens || null
      };
      if (this.mode === 'edit') this.newActionToRemove.id = item.id;
      this.actionToRemoveToEdit = item;
    }
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

  isReadyToSubmit(type: 'actionToAward') {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    // Validate inputs
    //if (type === 'team' && this.newTeam && this.newTeam.teamMembers) return (isValid(this.newTeam.teamName) )  ;
    return true;
  }

}

export interface CurrencySkillsVars {
  skillCost: number,
  wildcardCost: number,
  attemptRating: number,
  costFormula: "0" | "1" | "2",
  incrementCost: string,
}

export interface ActionToRemoveData {
  id?: number,
  name: string,
  description: string,
  type: string,
  tokens: number,
}

