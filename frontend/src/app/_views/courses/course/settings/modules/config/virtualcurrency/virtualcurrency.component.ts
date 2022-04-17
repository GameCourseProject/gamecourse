import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {ActionsToRemove} from "../../../../../../../_domain/virtualcurrency/actionstoremove";
import {exists} from "../../../../../../../_utils/misc/misc";
import {Course} from "../../../../../../../_domain/courses/course";

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

  skillsVars = {
    skillCost: null,
    wildcardCost: null,
    attemptRating: null,
    costFormula: null,
    incrementCost: null,
  }

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

  isReadyToSubmit(): boolean {
    return exists(this.skillsVars.skillCost) && !this.skillsVars.skillCost.isEmpty() &&
      exists(this.skillsVars.wildcardCost) && !this.skillsVars.wildcardCost.isEmpty() &&
      exists(this.skillsVars.attemptRating) && !this.skillsVars.attemptRating.isEmpty() &&
      exists(this.skillsVars.costFormula) && !this.skillsVars.costFormula.isEmpty() &&
      exists(this.skillsVars.incrementCost) && !this.skillsVars.incrementCost.isEmpty();
  }

  isSkillEnabled(): Promise<boolean> {
    return this.api.isSkillsEnabled(this.courseID).toPromise();
  }

}

export interface CurrencySkillsVars {
  skillCost: number,
  wildcardCost: number,
  attemptRating: number,
  costFormula: number,
  incrementCost: string,
}

