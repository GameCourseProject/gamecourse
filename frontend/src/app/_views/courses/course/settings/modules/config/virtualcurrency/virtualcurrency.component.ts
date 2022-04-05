import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";

@Component({
  selector: 'app-virtualcurrency',
  templateUrl: './virtualcurrency.component.html',
  styleUrls: ['./virtualcurrency.component.scss']
})
export class VirtualcurrencyComponent implements OnInit {

  loading: boolean;
  hasUnsavedChanges: boolean;


  courseID: number;

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
      this.getCurrencySkillVars();
    });
  }


  getCurrencySkillVars() {
    this.loading = true;
    /*this.api.getCurrencySkillVars(this.courseID)
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
      )*/
  }

  /*

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
  }*/


}

export interface CurrencySkillsVars {
  skillCost: number,
  wildcardCost: number,
  attemptRating: number,
  costFormula: number,
  incrementCost: string,
}

