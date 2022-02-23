import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {exists} from "../../../../../../../_utils/misc/misc";

@Component({
  selector: 'app-moodle',
  templateUrl: './moodle.component.html',
  styleUrls: ['./moodle.component.scss']
})
export class MoodleComponent implements OnInit {

  loading: boolean;
  hasUnsavedChanges: boolean;

  courseID: number;

  database = {
    server: null,
    user: null,
    password: null,
    name: null,
    port: null,
    prefix: null
  }

  moodle = {
    time: null,
    course: null,
    user: null
  }

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
      this.getMoodleVars();
    });
  }

  getMoodleVars() {
    this.loading = true;
    this.api.getMoodleVars(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        vars => {
          this.database.server = vars.dbServer;
          this.database.user = vars.dbUser;
          this.database.password = vars.dbPass;
          this.database.name = vars.dbName;
          this.database.port = vars.dbPort;
          this.database.prefix = vars.tablesPrefix;
          this.moodle.time = vars.moodleTime;
          this.moodle.course = vars.moodleCourse;
          this.moodle.user = vars.moodleUser;
          this.periodicity.nr = vars.periodicityNumber;
          this.periodicity.time = vars.periodicityTime;
          this.isEnabled = vars.isEnabled;
        },
        error => ErrorService.set(error)
      )
  }

  saveMoodle() {
    this.loading = true;

    const moodleVars = {
      dbServer: this.database.server,
      dbUser: this.database.user,
      dbPass: this.database.password,
      dbName: this.database.name,
      dbPort: this.database.port,
      tablesPrefix: this.database.prefix,
      moodleTime: this.moodle.time,
      moodleCourse: this.moodle.course,
      moodleUser: this.moodle.user,
      periodicityNumber: this.periodicity.nr,
      periodicityTime: this.periodicity.time,
      isEnabled: this.isEnabled
    }

    this.api.setMoodleVars(this.courseID, moodleVars)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => this.getMoodleVars(),
        error => ErrorService.set(error)
      )
  }

  isReadyToSubmit(): boolean {
    return exists(this.database.server) && !this.database.server.isEmpty() &&
      exists(this.database.name) && !this.database.name.isEmpty() &&
      exists(this.database.user) && !this.database.user.isEmpty() &&
      exists(this.database.port) && !this.database.port.isEmpty() &&
      exists(this.database.password) && !this.database.password.isEmpty() &&
      exists(this.database.prefix) && !this.database.prefix.isEmpty() &&
      exists(this.moodle.course) && !this.moodle.course.isEmpty() &&
      exists(this.moodle.time) && !this.moodle.time.isEmpty() &&
      exists(this.periodicity.nr) && exists(this.periodicity.time) && !this.periodicity.time.isEmpty();
  }

}

export interface MoodleVars {
  dbServer: string,
  dbUser: string,
  dbPass: string,
  dbName: string,
  dbPort: string,
  tablesPrefix: string,
  moodleTime: string,
  moodleCourse: string,
  moodleUser: string,
  periodicityNumber: number,
  periodicityTime: string,
  isEnabled: boolean
}
