import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {exists} from "../../../../../../../../../_utils/misc/misc";
import * as moment from 'moment';
import {User} from "../../../../../../../../../_domain/users/user";

@Component({
  selector: 'app-progress-report',
  templateUrl: './progress-report.component.html',
  styleUrls: ['./progress-report.component.scss']
})
export class ProgressReportComponent implements OnInit {

  loading: boolean = true;
  hasUnsavedChanges: boolean;

  courseID: number;
  students: User[];

  progressReport = {
    endDate: null,
    periodicity: {
      time: null,
      hours: null,
      day: null
    },
    isEnabled: null
  }

  currentDate = moment().format('YYYY-MM-DDTHH:mm:ss');

  tables: {
    reports: {
      showing: boolean,
      loading: boolean,
      headers: string[],
      data: string[][],
      actions: TableAction[]
    },
    studentsReports: {
      showing: boolean,
      loading: boolean,
      headers: string[],
      data: string[][],
      actions: TableAction[]
    }
  } = {
    reports: {
      showing: true,
      loading: true,
      headers: null,
      data: null,
      actions: [TableAction.VIEW]
    },
    studentsReports: {
      showing: false,
      loading: true,
      headers: null,
      data: null,
      actions: [TableAction.VIEW]
    }
  }

  isStudentReportModalOpen: boolean;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.getProgressReportConfig();
      await this.buildReportsTable();
    });
  }

  async getProgressReportConfig() {
    this.loading = true;

    const config = await this.api.getProgressReportConfig(this.courseID).toPromise();
    this.progressReport.endDate = !config.endDate || config.endDate.isEmpty() ? null : moment(config.endDate).format('YYYY-MM-DDTHH:mm:ss');
    this.progressReport.periodicity.time = config.periodicityTime;
    this.progressReport.periodicity.hours = config.periodicityHours;
    this.progressReport.periodicity.day = config.periodicityDay;
    this.progressReport.isEnabled = config.isEnabled;

    this.loading = false;
  }

  async saveProgressReport() {
    this.loading = true;

    const progressReport = {
      endDate: moment(this.progressReport.endDate).format("YYYY-MM-DD HH:mm:ss"),
      periodicityTime: this.progressReport.periodicity.time,
      periodicityHours: this.progressReport.periodicity.hours,
      periodicityDay: this.progressReport.periodicity.day,
      isEnabled: this.progressReport.isEnabled
    }

    await this.api.saveProgressReportConfig(this.courseID, progressReport).toPromise();
    await this.getProgressReportConfig();

    this.loading = false;
  }

  isReadyToSubmit(): boolean {
    if (this.progressReport.isEnabled) {
      return exists(this.progressReport.endDate) &&
        exists(this.progressReport.periodicity.time) && !this.progressReport.periodicity.time.isEmpty() &&
        exists(this.progressReport.periodicity.hours) && this.progressReport.periodicity.hours >= 0 && this.progressReport.periodicity.hours <= 24 &&
        (this.progressReport.periodicity.time === 'Weekly' ? exists(this.progressReport.periodicity.day) : true);

    } else return true;
  }

  async buildReportsTable() {
    this.tables.reports.headers = [
      'report nr', 'reports sent', 'start date', 'end date', 'finished sending',
    ];

    const reports = await this.api.getProgressReports(this.courseID).toPromise();
    this.tables.reports.data = reports.map(entry => [
      entry.seqNr.toString(), entry.reportsSent.toString(), entry.periodStart.format('DD/MM/YYYY HH:mm:ss'), entry.periodEnd.format('DD/MM/YYYY HH:mm:ss'), entry.dateSent.format('DD/MM/YYYY HH:mm:ss')
    ]);
  }

  async buildStudentReportsTable(reportNr: number) {
    this.tables.studentsReports.headers = [
      'report nr', 'student', 'total XP', 'period XP', 'diff. previous period', 'prediction XP', 'e-mail', 'date',
    ]

    const students = await this.api.getStudentsWithProgressReports(this.courseID, reportNr).toPromise();
    this.tables.studentsReports.data = students.map(entry => [
      reportNr.toString(), entry.user.name, entry.totalXP.format(), entry.periodXP.format(), entry.diffXP.format('percent'),
      entry.prediction.format(), entry.emailSend, entry.dateSent.format('DD/MM/YYYY HH:mm:ss')
    ]);
    this.students = students.map(entry => entry.user);
  }

  async doBtnAction(table: 'reports' | 'studentReports', action: TableAction, row: number) {
    if (action === TableAction.VIEW) {
      if (table === 'reports') {
        this.tables.reports.showing = false;
        this.tables.studentsReports.showing = true;

        const reportNr = parseInt(this.tables.reports.data[row][0]);
        await this.buildStudentReportsTable(reportNr);

      } else if (table === 'studentReports') {
        const studentName = this.tables.studentsReports.data[row][1];
        const studentId = this.students.find(student => student.name === studentName).id;
        const seqNr = parseInt(this.tables.studentsReports.data[row][0]);

        const report = await this.api.getStudentProgressReport(this.courseID, studentId, seqNr).toPromise();
        this.isStudentReportModalOpen = true;
        setTimeout(() => {
          const element = (document.getElementById('student-report') as HTMLIFrameElement);
          const iframe = element.contentDocument || element.contentWindow;
          iframe.open();
          // @ts-ignore
          iframe.write(report);
          iframe.close();
        }, 0)
      }
    }
  }

}

export interface ProgressReportConfig {
  endDate: string,
  periodicityTime: "Weekly" | "Daily",
  periodicityHours: number,
  periodicityDay: 0 | 1 | 2 | 3 | 4 | 5 | 6,
  isEnabled: boolean
}

enum TableAction {
  VIEW,
  EDIT,
  DELETE
}
