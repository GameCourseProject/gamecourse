import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";

import {Moment} from 'moment';
import {TableDataType} from 'src/app/_components/tables/table-data/table-data.component';
import {ApiHttpService} from 'src/app/_services/api/api-http.service';
import {AlertService, AlertType} from "../../../../../../_services/alert.service";

@Component({
  selector: 'app-autogame',
  templateUrl: './autogame.component.html'
})
export class AutogameComponent implements OnInit {

  loading = {
    table: true,
    action: false
  }

  courseID: number;

  status: DataSourceStatus;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
        this.courseID = parseInt(params.id);
        await this.getStatusInfo();
        this.buildTable();
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getStatusInfo() {
    this.status = await this.api.getAutoGameStatus(this.courseID).toPromise();
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Started importing data', align: 'middle'},
    {label: 'Finished importing data', align: 'middle'},
    {label: 'Now', align: 'middle'},
    {label: 'Actions'},
  ];
  data: {type: TableDataType, content: any}[][];
  tableOptions = {
    searching: false,
    lengthChange: false,
    paging: false,
    info: false,
    columnDefs: [
      { orderable: false, targets: [0, 1, 2, 3] }
    ]
  }

  buildTable(): void {
    this.loading.table = true;

    this.data = [[
      {type: TableDataType.DATETIME, content: {datetime: this.status.startedRunning}},
      {type: TableDataType.DATETIME, content: {datetime: this.status.finishedRunning}},
      {type: TableDataType.COLOR, content: {color: this.status.isRunning ? '#36D399' : '#EF6060', colorLabel: this.status.isRunning ? 'Importing' : 'Not importing'}},
      {type: TableDataType.ACTIONS, content: {actions: [{action: 'Refresh', icon: 'feather-refresh-ccw'}]}},
    ]];

    this.loading.table = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async doActionOnTable(action: string): Promise<void> {
    if (action === 'Refresh') {
      this.loading.action = true;

      await this.getStatusInfo();
      this.buildTable();

      this.loading.action = false;
    }
  }

  async runAutoGameNow() {
    this.loading.action = true;
    await this.api.runAutoGameNow(this.courseID).toPromise();
    AlertService.showAlert(AlertType.SUCCESS, "AutoGame started running");
    await this.getStatusInfo();
    this.buildTable();
    this.loading.action = false;
  }

  async runAutoGameNowForAllTargets() {
    this.loading.action = true;
    await this.api.runAutoGameNowForAllTargets(this.courseID).toPromise();
    AlertService.showAlert(AlertType.SUCCESS, "AutoGame started running");
    await this.getStatusInfo();
    this.buildTable();
    this.loading.action = false;
  }

}

export type DataSourceStatus = {
  isEnabled: boolean,
  startedRunning: Moment,
  finishedRunning: Moment,
  isRunning: boolean,
  logs: string
}
