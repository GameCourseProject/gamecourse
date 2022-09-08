import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";

import * as Highcharts from 'highcharts';
import {Moment} from "moment";
import * as moment from "moment";
declare var require: any;
let Sankey = require('highcharts/modules/sankey');
let Export = require('highcharts/modules/exporting');
let ExportData = require('highcharts/modules/export-data');
let Accessibility = require('highcharts/modules/accessibility');

Sankey(Highcharts)
Export(Highcharts)
ExportData(Highcharts)
Accessibility(Highcharts)

@Component({
  selector: 'app-profiling',
  templateUrl: './profiling.component.html',
  styleUrls: ['./profiling.component.scss']
})
export class ProfilingComponent implements OnInit {

  loading: boolean = true;
  loadingAction: boolean;
  courseID: number;

  nrClusters: number = 4;
  minClusterSize: number = 4;
  endDate: string = moment().format('YYYY-MM-DDTHH:mm:ss');
  clusterNames: string[];
  clusters: {[studentId: string]: {name: string, cluster: string}};

  lastRun: Moment;
  predictorIsRunning: boolean;
  profilerIsRunning: boolean;
  select: {[studentId: number]: string}[];

  history: ProfilingHistory[];
  nodes: ProfilingNode[];
  data: string|number[][];
  days: string[];

  table: {
    loading: boolean,
    headers: string[],
    data: string[][]
  } = {
    loading: true,
    headers: null,
    data: null
  }

  isPredictModalOpen: boolean;
  methods: {name: string, char: string}[] = [
    {name: "Elbow method", char: "e"},
    {name: "Silhouette method", char: "s"}
  ];
  methodSelected: string = this.methods[0].char;

  isImportModalOpen: boolean;
  importedFile: File;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.getHistory();
      await this.getLastRun();
      await this.getSavedClusters();
      this.loading = false;
    });
  }

  async getHistory() {
    const res = await this.api.getHistory(this.courseID).toPromise();
    this.history = res.history;
    this.nodes = res.nodes;
    this.data = res.data;
    this.days = res.days.length > 0 ? res.days : ["Current"];
    if (this.data.length > 0) this.buildChart();
  }

  async getLastRun() {
    this.lastRun = await this.api.getLastRun(this.courseID).toPromise();
  }

  async getSavedClusters() {
    const res = await this.api.getSavedClusters(this.courseID).toPromise();
    this.clusterNames = res.names;
    this.select = res.saved;

    if (this.select.length == 0) { // no saved clusters
      this.buildResultsTable();
      await this.checkProfilerStatus();
    }

    await this.checkPredictorStatus();
  }

  async runPredictor() {
    this.loadingAction = true;
    this.predictorIsRunning = true;

    const endDate = moment(this.endDate).format("YYYY-MM-DD HH:mm:ss");
    await this.api.runPredictor(this.courseID, this.methodSelected, endDate).toPromise();
    this.loadingAction = false;
  }

  async runProfiler() {
    this.loadingAction = true;
    this.profilerIsRunning = true;

    const endDate = moment(this.endDate).format("YYYY-MM-DD HH:mm:ss");
    await this.api.runProfiler(this.courseID, this.nrClusters, this.minClusterSize, endDate).toPromise();
    this.loadingAction = false;
  }

  async saveClusters() {
    this.loadingAction = true;

    const cls = {}
    for (const user of Object.keys(this.clusters)) {
      cls[user] = this.clusters[user].cluster;
    }

    await this.api.saveClusters(this.courseID, cls).toPromise();
    this.loadingAction = false;
  }

  async commitClusters() {
    this.loadingAction = true;

    const cls = {}
    for (const user of Object.keys(this.clusters)) {
      cls[user] = this.clusters[user].cluster;
    }

    await this.api.commitClusters(this.courseID, cls).toPromise();
    await this.getHistory();

    this.clusters = null
    this.loadingAction = false;
  }

  async deleteSavedClusters() {
    this.loadingAction = true;
    await this.api.deleteSavedClusters(this.courseID).toPromise();
    this.clusters = null
    this.loadingAction = false;
  }

  async checkProfilerStatus() {
    this.loadingAction = true;

    const status = await this.api.checkProfilerStatus(this.courseID).toPromise();
    if (typeof status == 'boolean') {
      this.profilerIsRunning = status;

    } else { // got clusters as result
      this.clusters = status.clusters;
      this.clusterNames = status.names;
      this.profilerIsRunning = false;
    }

    this.loadingAction = false;
  }

  async checkPredictorStatus() {
    this.loadingAction = true;

    const status = await this.api.checkPredictorStatus(this.courseID).toPromise();
    if (typeof status == 'boolean') {
      this.predictorIsRunning = status;

    } else { // got nrClusters as result
      this.nrClusters = status;
      this.minClusterSize = Math.min(this.minClusterSize, this.nrClusters);
      this.predictorIsRunning = false;
    }

    this.loadingAction = false
  }

  buildChart() {
    // @ts-ignore
    Highcharts.chart('overview', {
      chart: {
        marginRight: 40
      },
      title: {
        text: ""
      },
      series: [{
        keys: ["from", "to", "weight"],
        nodes: this.nodes,
        data: this.data,
        type: "sankey",
        name: "Cluster History",
        dataLabels: {
          style: {
            color: "#1a1a1a",
            textOutline: false
          }
        }
      }]
    });
  }

  buildResultsTable() {
    this.table.loading = true;

    this.table.headers = ['Student'];
    for (const day of this.days) {
      this.table.headers.push(day);
    }

    if (!this.table.data) this.table.data = [];
    for (const studentHistory of this.history) {
      const data = [studentHistory.name];
      for (const day of this.days) {
        data.push(studentHistory[day]);
      }
      this.table.data.push(data);
    }

    this.table.loading = false;
  }

  exportItem() { // FIXME
    this.loadingAction = true;
    this.api.exportModuleItems(this.courseID, ApiHttpService.PROFILING, null)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(res => {})
  }

  importItems(replace: boolean): void { // FIXME
    // this.loadingAction = true;
    //
    // const reader = new FileReader();
    // reader.onload = (e) => {
    //   const importedItems = reader.result;
    //   this.api.importModuleItems(this.courseID, ApiHttpService.PROFILING, importedItems, replace)
    //     .pipe( finalize(() => {
    //       this.isImportModalOpen = false;
    //       this.loadingAction = false;
    //     }) )
    //     .subscribe(
    //       nrItems => {
    //         const successBox = $('#action_completed');
    //         successBox.empty();
    //         successBox.append("Items imported");
    //         successBox.show().delay(3000).fadeOut();
    //       })
    // }
    // reader.readAsDataURL(this.importedFile);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
  }

  getEditableResults(): {name: string, cluster: string}[] {
    return Object.values(this.clusters).sort((a, b) => a.name.localeCompare(b.name));
  }
}

export interface ProfilingHistory {
  id: string,
  name: string,
  [day: string]: string
}

export interface ProfilingNode {
  id: string,
  name: string,
  color: string
}
