import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {exists} from "../../../../../../../_utils/misc/misc";
import * as moment from 'moment';
import {TableAction} from "../../../../../../../_components/tables/datatable/datatable.component";
import {User} from "../../../../../../../_domain/users/user";

@Component({
  selector: 'app-notifications',
  templateUrl: './notifications.component.html',
  styleUrls: ['./notifications.component.scss']
})
export class NotificationsComponent implements OnInit {

  loading: boolean;
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
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getProgressReportVars();
      this.buildReportsTable();
    });
  }

  getProgressReportVars() {
    this.loading = true;
    this.api.getProgressReportVars(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        vars => {
          this.progressReport.endDate = vars.endDate.isEmpty() ? null : moment(vars.endDate).format('YYYY-MM-DDTHH:mm:ss');
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
      endDate: moment(this.progressReport.endDate).format("YYYY-MM-DD HH:mm:ss"),
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
    if (this.progressReport.isEnabled) {
      return exists(this.progressReport.endDate) &&
        exists(this.progressReport.periodicity.time) && !this.progressReport.periodicity.time.isEmpty() &&
        exists(this.progressReport.periodicity.hours) && this.progressReport.periodicity.hours >= 0 && this.progressReport.periodicity.hours <= 24 &&
        (this.progressReport.periodicity.time === 'Weekly' ? exists(this.progressReport.periodicity.day) : true);

    } else return true;
  }

  buildReportsTable() {
    this.tables.reports.headers = [
      'report nr', 'reports sent', 'start date', 'end date', 'finished sending',
    ];

    this.api.getTableData(this.courseID, 'notifications_progress_report')
      .pipe(finalize(() => this.tables.reports.loading = false))
      .subscribe(
        data => {
          this.tables.reports.data = data.entries.map(entry => [
            entry.seqNr, entry.reportsSent, entry.periodStart, entry.periodEnd, entry.dateSent
          ]);
        },
        error => ErrorService.set(error)
      )
  }

  buildStudentReportsTable(reportNr: number) {
    this.tables.studentsReports.headers = [
      'report nr', 'student', 'total XP', 'period XP', 'diff. previous period', 'prediction XP', 'e-mail', 'date',
    ]

    this.api.getTableData(this.courseID, 'notifications_progress_report_history')
      .subscribe(
        data => {
          data.entries = data.entries.filter(entry => parseInt(entry.course) === this.courseID && parseInt(entry.seqNr) === reportNr);

          this.api.getCourseUsers(this.courseID, "Student")
            .pipe(finalize(() => this.tables.studentsReports.loading = false))
            .subscribe(
              students => {
                this.students = students;
                this.tables.studentsReports.data = data.entries.map(entry => {
                  const student = students.find(student => student.id === parseInt(entry.user));
                  return [
                    entry.seqNr, student.name, parseInt(entry.totalXP).format(), parseInt(entry.periodXP).format(),
                    parseInt(entry.diffXP).format('percent'), entry.prediction !== null ? parseInt(entry.prediction).format() : '-',
                    entry.emailSend, entry.dateSent
                  ];
                });
              },
              error => ErrorService.set(error)
            )
        },
        error => ErrorService.set(error)
      )
  }

  doBtnAction(table: 'reports' | 'studentReports', action: TableAction, row: number) {
    if (action === TableAction.VIEW) {
      if (table === 'reports') {
        this.tables.reports.showing = false;
        this.tables.studentsReports.showing = true;

        const reportNr = parseInt(this.tables.reports.data[row][0]);
        this.buildStudentReportsTable(reportNr);

      } else if (table === 'studentReports') {
        const studentName = this.tables.studentsReports.data[row][1];
        const studentId = this.students.find(student => student.name === studentName).id;
        const seqNr = parseInt(this.tables.studentsReports.data[row][0]);
        this.api.getStudentProgressReport(this.courseID, studentId, seqNr)
          .subscribe(
            report => {
              this.isStudentReportModalOpen = true;
              setTimeout(() => {
                const element = (document.getElementById('student-report') as HTMLIFrameElement);
                const iframe = element.contentDocument || element.contentWindow;
                iframe.open();
                // @ts-ignore
                iframe.write(report);
                iframe.close();
              }, 0)
            },
            error => ErrorService.set(error)
          )
      }
    }
  }

}

export interface ProgressReportVars {
  endDate: string,
  periodicityTime: "Weekly" | "Daily",
  periodicityHours: number,
  periodicityDay: 0 | 1 | 2 | 3 | 4 | 5 | 6,
  isEnabled: boolean
}
