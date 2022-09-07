import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";

import * as Highcharts from 'highcharts';
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

  loading: boolean;
  loadingAction: boolean;
  courseID: number;

  nrClusters: number = 4;
  minClusterSize: number = 4;
  clusterNames: {name: string}[];
  clusters: {[studentNr: string]: {name: string, cluster: string}};

  lastRun: string;
  profilerIsRunning: boolean;
  predictorIsRunning: boolean;
  select: any[];

  history: ProfilingHistory[];
  nodes: ProfilingNode[];
  data: any[][];
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
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getHistory();
    });
  }

  getHistory() {
    this.loading = true;
    this.api.getHistory(this.courseID)
      .subscribe(
        res => {
          this.history = res.history;
          this.nodes = res.nodes;
          this.data = res.data;
          this.days = res.days.length > 0 ? res.days : ["Current"];
          if (this.data.length > 0) this.buildChart();
          this.getTime();
        })
  }

  getTime() {
    this.loading = true;
    this.api.getTime(this.courseID)
      .subscribe(
        time => {
          this.lastRun = time != null ? time : 'Never';
          this.getSaved();
        })
  }

  getSaved() {
    this.loading = true;
    this.api.getSaved(this.courseID)
      .pipe(finalize(() => this.loading = false))
      .subscribe(
        res => {
          this.clusterNames = res.names;
          this.select = res.saved;

          if (this.select.length == 0) {
            this.buildResultsTable();
            this.checkRunningStatus();

          } else {
            console.log('select not empty'); // FIXME
          }

          this.checkPredictorStatus();
        })
  }

  runProfiler() {
    this.loadingAction = true;
    this.profilerIsRunning = true;
    this.api.runProfiler(this.courseID, this.nrClusters, this.minClusterSize)
      .pipe(finalize(() => this.loadingAction = false))
      .subscribe(res => {})
  }

  runPredictor() {
    this.loadingAction = true;
    this.predictorIsRunning = true;
    this.api.runPredictor(this.courseID, this.methodSelected)
      .pipe(finalize(() => this.loadingAction = false))
      .subscribe(res => {})
  }

  saveClusters() {
    this.loadingAction = true;

    const cls = {}
    for (const user of Object.keys(this.clusters)) {
      cls[user] = this.clusters[user].cluster;
    }

    this.api.saveClusters(this.courseID, cls)
      .pipe(finalize(() => this.loadingAction = false))
      .subscribe(res => {})
  }

  commitClusters() {
    this.loadingAction = true;

    const cls = {}
    for (const user of Object.keys(this.clusters)) {
      cls[user] = this.clusters[user].cluster;
    }

    this.api.commitClusters(this.courseID, cls)
      .pipe(finalize(() => {
        this.clusters = null
        this.loadingAction = false;
      }))
      .subscribe(res => this.getHistory());
  }

  deleteSaved() {
    this.loadingAction = true;
    this.api.deleteSaved(this.courseID)
      .pipe(finalize(() => {
        this.clusters = null
        this.loadingAction = false;
      }))
      .subscribe(res => {});
  }

  checkRunningStatus() {
    this.loadingAction = true;
    this.api.checkRunningStatus(this.courseID)
      .pipe(finalize(() => this.loadingAction = false))
      .subscribe(
        res => {
          if (typeof res == 'boolean') {
            this.profilerIsRunning = res;

          } else { // got clusters as result
            this.clusters = res.clusters;
            this.clusterNames = res.names;
            this.profilerIsRunning = false;
          }
        })
  }

  checkPredictorStatus() {
    this.loadingAction = true;
    this.api.checkPredictorStatus(this.courseID)
      .pipe(finalize(() => this.loadingAction = false))
      .subscribe(
        res => {
          if (typeof res == 'boolean') {
            this.predictorIsRunning = res;

          } else { // got nrClusters as result
            this.nrClusters = res;
            this.predictorIsRunning = false;
          }
        })
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

  exportItem() {
    this.loadingAction = true;
    this.api.exportModuleItems(this.courseID, ApiHttpService.PROFILING, null)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(res => {})
  }

  importItems(replace: boolean): void {
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
  [key: string]: string
}

export interface ProfilingNode {
  id: string,
  name: string,
  color: string
}
