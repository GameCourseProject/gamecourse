import {Component, OnInit, ViewChild} from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";

import {Action} from 'src/app/_domain/modules/config/Action';
import * as Highcharts from 'highcharts';
import * as moment from "moment";
import {Moment} from "moment";
import {TableDataType} from "../../../../../../../../../_components/tables/table-data/table-data.component";
import {dateFromDatabase} from "../../../../../../../../../_utils/misc/misc";
import {User} from "../../../../../../../../../_domain/users/user";
import {ModalService} from "../../../../../../../../../_services/modal.service";
import {NgForm} from "@angular/forms";
import {AlertService, AlertType} from "../../../../../../../../../_services/alert.service";

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
  data: (string|number)[][];
  days: string[];

  table: {
    loading: boolean,
    headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
    data: {type: TableDataType, content: any}[][],
    options: any
  } = {
    loading: true,
    headers: null,
    data: null,
    options: null
  }

  isPredictModalOpen: boolean;
  methods: {name: string, char: string}[] = [
    {name: "Elbow method", char: "e"},
    {name: "Silhouette method", char: "s"}
  ];
  methodSelected: string = null;
  @ViewChild('fPrediction', { static: false }) fPrediction: NgForm;
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  isImportModalOpen: boolean;
  importedFile: File;

  students: {[userID: number]: User} = {};

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.getStudents();
      await this.getHistory();
      await this.getLastRun();
      await this.getSavedClusters();
      this.loading = false;
    });
  }

  get TableDataType(): typeof TableDataType {
    return TableDataType;
  }

  get Action(): typeof Action {
    return Action;
  }

  async getHistory() {
    const res = await this.api.getHistory(this.courseID).toPromise();
    this.history = res.history;
    this.nodes = res.nodes;
    this.data = res.data;
    this.days = res.days.length > 0 ? res.days : ["Current"];
    if (this.data.length > 0) this.buildChart();
  }

  async getStudents() {
    let students = await this.api.getCourseUsersWithRole(this.courseID, "Student").toPromise();
    for (const student of students) {
      this.students[student.id] = student;
    }
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
    setTimeout(() => {
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
    }, 0);
  }

  buildResultsTable() {
    this.table.loading = true;

    this.table.headers = [
      {label: 'Name (sorting)', align: 'left'},
      {label: 'Student', align: 'left'},
      {label: 'Student Nr', align: 'middle'}
    ];
    this.table.options = {
      order: [[ 0, 'asc' ]], // default order,
      columnDefs: [
        { type: 'natural', targets: [0, 1, 2] },
        { orderData: 0,   targets: 1 }
      ]
    };

    let i = 0;
    for (const day of this.days) {
      this.table.headers.push({label: day === 'Current' ? day : dateFromDatabase(day).format('DD/MM/YYYY HH:mm:ss'), align: 'middle'});
      this.table.options.columnDefs[0].targets.push(i+3);
      i++;
    }

    if (!this.table.data) this.table.data = [];
    for (const studentHistory of this.history) {
      const student: User = this.students[studentHistory.id];
      const data: { type: TableDataType, content: any }[] = [
        {type: TableDataType.TEXT, content: {text: student.nickname ?? student.name}},
        {type: TableDataType.AVATAR, content: {avatarSrc: student.photoUrl, avatarTitle: student.nickname ?? student.name, avatarSubtitle: student.major}},
        {type: TableDataType.NUMBER, content: {value: student.studentNumber, valueFormat: 'none'}},
      ];
      for (const day of this.days) {
        data.push({type: TableDataType.TEXT, content: {text: studentHistory[day]}});
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

  async doAction(action: string): Promise<void> {
    if (action === 'choose prediction method'){
      this.isPredictModalOpen = true;
      ModalService.openModal('prediction-method');

    } else if (action === 'run predictor'){
      if (this.methodSelected !== null){
        await this.runPredictor();
        this.isPredictModalOpen = false;
        this.resetPredictionMethod();
      } else AlertService.showAlert(AlertType.ERROR, "Invalid method");

    } else if (action === Action.IMPORT){
      this.isImportModalOpen = true;
      ModalService.openModal('import-modal');

    } else if (action === 'submit import') {
      // FIXME : something else missing ?
      // FIXME -- NAO CHEGA AQUI
      this.isImportModalOpen = false;
      ModalService.closeModal('import-modal');

    } else if (action === 'close import modal'){
      this.importedFile = null;
      this.isImportModalOpen = false;
      ModalService.closeModal('import-modal');
    }
  }

  resetPredictionMethod(){
    this.methodSelected = null;
    ModalService.closeModal('prediction-method');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
    const reader = new FileReader();
    reader.onload = (e) => {
      // FIXME
      // this.import = JSON.parse(reader.result as string);
    }
    reader.readAsText(this.importedFile);
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
