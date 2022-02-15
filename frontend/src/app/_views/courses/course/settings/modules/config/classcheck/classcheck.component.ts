import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";

@Component({
  selector: 'app-classcheck',
  templateUrl: './classcheck.component.html',
  styleUrls: ['./classcheck.component.scss']
})
export class ClasscheckComponent implements OnInit {

  loading: boolean;
  hasUnsavedChanges: boolean;

  courseID: number;

  TSVCode: string;
  periodicity = {
    nr: 0,
    time: 'Minutes'
  };
  isEnabled: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getClassCheckVars();
    });
  }

  getClassCheckVars() {
    this.loading = true;
    this.api.getClassCheckVars(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        vars => {
          this.TSVCode = vars.tsvCode;
          this.periodicity.nr = vars.periodicityNumber;
          this.periodicity.time = vars.periodicityTime;
          this.isEnabled = vars.isEnabled;
        },
        error => ErrorService.set(error)
      )
  }

  saveClassCheck() {
    this.loading = true;
    this.api.setClassCheckVars(this.courseID, this.TSVCode, this.periodicity.nr, this.periodicity.time, this.isEnabled)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => this.getClassCheckVars(),
        error => ErrorService.set(error)
      )
  }

}
