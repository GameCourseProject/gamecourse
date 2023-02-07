import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";

import {Module} from "../../../../../../../../_domain/modules/module";

import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {TableDataType} from "../../../../../../../../_components/tables/table-data/table-data.component";
import {AlertService, AlertType} from "../../../../../../../../_services/alert.service";
import { Moment } from 'moment';

@Component({
  selector: 'app-data-source-status',
  templateUrl: './data-source-status.component.html'
})
export class DataSourceStatusComponent implements OnInit {

  loading = {
    table: true,
    action: false
  }

  courseID: number;
  module: Module;

  status: DataSourceStatus;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);

      this.route.params.subscribe(async childParams => {
        const moduleID = childParams.id;
        await this.getModule(this.courseID, moduleID);
        await this.getStatusInfo();
        this.buildTable();
      });
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getModule(courseID: number, moduleID: string): Promise<void> {
    this.module = await this.api.getCourseModuleById(courseID, moduleID).toPromise();
    this.module.icon.svg = this.module.icon.svg.replace('<svg', '<svg id="' + this.module.id + '-modal-icon"');
  }

  async getStatusInfo() {
    this.status = await this.api.getDataSourceStatus(this.courseID, this.module.id).toPromise();
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

  async changeStatus() {
    this.loading.action = true;

    const enable = !this.status.isEnabled;
    await this.api.changeDataSourceStatus(this.courseID, this.module.id, enable).toPromise();
    await this.getStatusInfo();

    AlertService.showAlert(AlertType.SUCCESS, 'Importing data from ' + this.module.name + ' is ' + (enable ? 'enabled' : 'disabled') + '.');
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
