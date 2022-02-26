import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {exists} from "../../../../../../../_utils/misc/misc";

@Component({
  selector: 'app-notifications',
  templateUrl: './notifications.component.html',
  styleUrls: ['./notifications.component.scss']
})
export class NotificationsComponent implements OnInit {

  loading: boolean;
  hasUnsavedChanges: boolean;

  courseID: number;

  progressReport = {
    endDate: null,
    periodicity: {
      time: null,
      hours: null,
      day: null
    },
    isEnabled: null
  }

  currentDate = new Date().toISOString().split('T')[0];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getProgressReportVars();
    });
  }

  getProgressReportVars() {
    this.loading = true;
    this.api.getProgressReportVars(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        vars => {
          this.progressReport.endDate = vars.endDate;
          this.progressReport.periodicity.time = vars.periodicityTime;
          this.progressReport.periodicity.hours = vars.periodicityHours;
          this.progressReport.periodicity.day = vars.periodicityDay;
          this.progressReport.isEnabled = vars.isEnabled;
        },
        error => ErrorService.set(error)
      )
  }

  saveProgressReport() {
    this.loading = true;

    const progressReport = {
      endDate: this.progressReport.endDate,
      periodicityTime: this.progressReport.periodicity.time,
      periodicityHours: this.progressReport.periodicity.hours,
      periodicityDay: this.progressReport.periodicity.day,
      isEnabled: this.progressReport.isEnabled
    }

    this.api.setProgressReportVars(this.courseID, progressReport)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => this.getProgressReportVars(),
        error => ErrorService.set(error)
      )
  }

  isReadyToSubmit(): boolean {
    return exists(this.progressReport.endDate) &&
      exists(this.progressReport.periodicity.time) && !this.progressReport.periodicity.time.isEmpty() &&
      exists(this.progressReport.periodicity.hours) && this.progressReport.periodicity.hours >= 0 && this.progressReport.periodicity.hours <= 24 &&
      (this.progressReport.periodicity.time === 'Weekly' ? exists(this.progressReport.periodicity.day) : true);
  }

}

export interface ProgressReportVars {
  endDate: string,
  periodicityTime: "Weekly" | "Daily",
  periodicityHours: number,
  periodicityDay: 0 | 1 | 2 | 3 | 4 | 5 | 6,
  isEnabled: boolean
}
