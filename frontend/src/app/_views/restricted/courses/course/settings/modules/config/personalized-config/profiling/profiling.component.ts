import {Component, OnInit, ViewChild} from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";

import {Action} from 'src/app/_domain/modules/config/Action';
import * as Highcharts from 'highcharts';
import * as moment from "moment";
import {TableDataType} from "../../../../../../../../../_components/tables/table-data/table-data.component";
import {dateFromDatabase} from "../../../../../../../../../_utils/misc/misc";
import {User} from "../../../../../../../../../_domain/users/user";
import {ModalService} from "../../../../../../../../../_services/modal.service";
import {NgForm} from "@angular/forms";
import {AlertService, AlertType} from "../../../../../../../../../_services/alert.service";
import {CourseUser} from "../../../../../../../../../_domain/users/course-user";
import {Course} from "../../../../../../../../../_domain/courses/course";
import {ResourceManager} from "../../../../../../../../../_utils/resources/resource-manager";

import * as _ from 'lodash';
import {Moment} from "moment";

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
  templateUrl: './profiling.component.html'
})
export class ProfilingComponent implements OnInit {

  loading = {
    action: false,
    save: false,
    commit: false,
    table: {
      status: true,
      results: true
    }
  }
  running = {
    predictor: false,
    profiler: false
  }
  refreshing: boolean = true;

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Name (sorting)', align: 'left'},
    {label: 'Student', align: 'left'},
    {label: 'Student Nr', align: 'middle'}
  ];
  targets: number[] = [0,1,2];
  table: {
    headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
    data: {type: TableDataType, content: any}[][],
    options: any
  } = {
    headers: _.cloneDeep(this.headers),
    data: null,
    options: {
      order: [[ 0, 'asc' ]], // default order,
      columnDefs: [
        { type: 'natural', targets: _.cloneDeep(this.targets) },
        { orderData: 0,   targets: 1 }
      ]
    }
  }

  course: Course;
  students: CourseUser[];

  mode: "predict" | "discard" | "import";
  lastRun: Moment;

  // PREDICTOR
  methods: {name: string, char: string}[] = [
    {name: "Elbow method", char: "e"},
    {name: "Silhouette method", char: "s"}
  ];
  methodSelected: string = null;

  // TODO - REFACTOR (add to new exported interface?)
  status: {type: TableDataType, content: any}[][];
  nrClusters: number = 4;
  minClusterSize: number = 4;
  endDate: string = moment().format('YYYY-MM-DDTHH:mm:ss');

  // PROFILER
  origin: "profiler" | "drafts";
  newClusters: {[studentId: number]: string};
  results: {[studentId: number]: string};
  clusterNamesSelect: { value: string, text: string }[];

  // CHART
  history: ProfilingHistory[];
  nodes: ProfilingNode[];
  data: (string|number)[][];
  days: string[];

  @ViewChild('fPrediction', { static: false }) fPrediction: NgForm;
  @ViewChild('fImport', { static: false }) fImport: NgForm;

  importedFile: { file: File, replace: boolean } = {file: null, replace: true};

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      // Status
      await this.getLastRun();
      await this.buildStatusTable();

      // Overview
      await this.getStudents();
      await this.getHistory();
      await this.getClusterNames();
      await this.checkPredictorStatus();
      await this.getClusters();
    });
  }

  get TableDataType(): typeof TableDataType {
    return TableDataType;
  }

  get Action(): typeof Action {
    return Action;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getStudents() {
    this.students = await this.api.getCourseUsersWithRole(this.course.id, "Student").toPromise();
  }

  async getHistory() {
    const res = await this.api.getHistory(this.course.id).toPromise();

    this.history = res.history;
    this.nodes = res.nodes;
    this.data = res.data;
    this.days = res.days.length > 0 ? res.days : ["Current"];
    if (this.data.length > 0) this.buildChart();
  }

  async getClusterNames(){
    const names = await this.api.getClusterNames(this.course.id).toPromise();
    this.clusterNamesSelect = names.map(element => {return {value: element, text: element}});
  }

  async getLastRun() {
    this.lastRun = await this.api.getLastRun(this.course.id).toPromise();
  }

  /*** --------------------------------------------------- ***/
  /*** ---------------------- Status --------------------- ***/
  /*** --------------------------------------------------- ***/

  async buildStatusTable() {
    this.loading.table.status = true;

    this.status = [[
      {type: TableDataType.DATETIME, content: {datetime: await this.getLastRun()}},
      {type: TableDataType.COLOR,
        content: {
          color: this.running.profiler ? '#36D399' : '#EF6060',
          colorLabel: this.running.profiler ? 'Running' : 'Not running'
        }
      },
      {type: TableDataType.ACTIONS, content: {actions: [{action: 'Refresh', icon: 'feather-refresh-ccw'}]}}
    ]];

    this.loading.table.status = false;
  }

  async doActionOnTable(action: string): Promise<void>{
    if (action === 'Refresh') await this.refreshResults();
  }


  /*** --------------------------------------------------- ***/
  /*** --------------------- Profiler -------------------- ***/
  /*** --------------------------------------------------- ***/

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

  async getClusters(){
    await this.getSavedClusters();        // { "saved" (uncommitted changes), "names" (cluster names) }
    await this.checkProfilerStatus();     // { "clusters": { studentId: {name, cluster} }, "names" (cluster names -> not sure?) }

    this.buildResultsTable();
  }

  async getSavedClusters() {
    const drafts = await this.api.getSavedClusters(this.course.id).toPromise();

    if (drafts){
      this.results = drafts.saved;
      this.origin = "drafts";
      this.newClusters = JSON.parse(JSON.stringify(this.results));       // prepare in case of discard action
    }
  }

  async checkProfilerStatus() {
    this.loading.action = true;

    const profiler = await this.api.checkProfilerStatus(this.course.id).toPromise()

    if (typeof profiler == 'boolean') { this.running.profiler = profiler; }
    else { // got clusters as result
      let results = {};

      // parse results to match this.results type
      for (const element of Object.keys(profiler.clusters)){
        results[element] = profiler.clusters[element].cluster;
      }

      this.results = results;
      this.origin = "profiler";
      this.newClusters = JSON.parse(JSON.stringify(this.results));       // prepare in case of discard action
      this.running.profiler = false;
    }

    this.loading.action = false;
  }

  async runProfiler() {
    this.loading.action = true;
    this.running.profiler = true;

    const endDate = moment(this.endDate).format("YYYY-MM-DD HH:mm:ss");
    await this.api.runProfiler(this.course.id, this.nrClusters, this.minClusterSize, endDate).toPromise();
    this.loading.action = false;
  }

  async saveClusters() {
    this.loading.save = true;

    try {
      const cls = {}

      for (const user of Object.keys(this.newClusters)) {
        cls[user] = this.newClusters[user];
      }

        await this.api.saveClusters(this.course.id, cls).toPromise();
        this.loading.save = false;
        AlertService.showAlert(AlertType.SUCCESS, "Draft saved successfully");

    } catch (error) {
      AlertService.showAlert(AlertType.ERROR, "Unable to save");
    }
  }

  async commitClusters() {
    this.loading.commit = true;

    try {
      const cls = {}
      for (const user of Object.keys(this.newClusters)) {
        cls[user] = this.newClusters[user];
      }

      await this.api.commitClusters(this.course.id, cls).toPromise();

      // reset variables
      this.resetData();

      // get info for table
      await this.getHistory();
      await this.getClusters();
      AlertService.showAlert(AlertType.SUCCESS, "Changes successfully committed to Database");

    } catch (error) {
      AlertService.showAlert(AlertType.ERROR, "Unable to commit changes");
    }

    this.loading.commit = false;
  }

  async deleteClusters() {
    this.loading.action = true;

    // see if entries on table come from drafts and deletes them
    if (this.origin === "drafts"){
      await this.api.deleteSavedClusters(this.course.id).toPromise();
    }

    this.resetData();
    this.buildResultsTable();
    this.resetDiscardModal();
    this.loading.action = false;
  }


  /*** --------------------------------------------------- ***/
  /*** -------------------- Predictor  ------------------- ***/
  /*** --------------------------------------------------- ***/

  async checkPredictorStatus() {
    this.loading.action = true;

    const status = await this.api.checkPredictorStatus(this.course.id).toPromise();
    if (typeof status == 'boolean') {
      this.running.predictor = status;

    } else { // got nrClusters as result
      this.nrClusters = status;
      this.minClusterSize = Math.min(this.minClusterSize, this.nrClusters);
      this.running.predictor = false;
    }

    this.loading.action = false;
  }

  async runPredictor() {
    this.loading.action = true;
    this.running.predictor = true;

    const endDate = moment(this.endDate).format("YYYY-MM-DD HH:mm:ss");
    await this.api.runPredictor(this.course.id, this.methodSelected, endDate).toPromise();
    this.loading.action = false;
  }


  /*** --------------------------------------------------- ***/
  /*** --------------------- Results --------------------- ***/
  /*** --------------------------------------------------- ***/

  buildResultsTable() {
    // NOTE: forces table to update
    this.refreshing = true;
    setTimeout(() => this.refreshing = false, 0);

    this.loading.table.results = true;

    for (const day of this.days) {
      if (day === 'Current' && Object.keys(this.newClusters).length > 0){
        continue;
      }
      this.table.headers.push({label: day === 'Current' ? day : dateFromDatabase(day).format('DD/MM/YYYY HH:mm:ss'), align: 'middle'});
      (this.table.options.columnDefs[0].targets).push((this.table.options.columnDefs[0].targets).length);
    }

    if (Object.keys(this.newClusters).length > 0) {
      this.table.headers.push({label: 'Current', align: 'middle'});
      (this.table.options.columnDefs[0].targets).push((this.table.options.columnDefs[0].targets).length);
    }

    this.addLegibility("headers");

    let data: { type: TableDataType, content: any }[][] = [];
    for (const studentHistory of this.history) {
      const student = this.students.find(el => el.id === parseInt(studentHistory.id));
      data.push([{type: TableDataType.TEXT, content: {text: (student.nickname !== null && student.nickname !== "") ? student.nickname : student.name}},
        {type: TableDataType.AVATAR, content: {
          avatarSrc: student.photoUrl,
          avatarTitle: (student.nickname !== null && student.nickname !== "") ? student.nickname : student.name,
          avatarSubtitle: student.major}},
        {type: TableDataType.NUMBER, content: {value: parseInt(String(student.studentNumber)), valueFormat: 'none'}}]);

      for (const day of this.days) {
        if (studentHistory[day] === 'None' && Object.keys(this.newClusters).length > 0){
          continue;
        }
        data[data.length - 1].push({type: TableDataType.TEXT, content: {text: studentHistory[day]}});
      }

      // See if there's uncommitted changes or profiles shows results from running
      if (Object.keys(this.newClusters).length > 0) {
        let aux = (student.id).toString();
        aux = "cluster-" + aux;
        data[data.length - 1].push({type: TableDataType.SELECT, content: {
              selectId: aux,
              selectValue: this.newClusters[student.id],
              selectOptions: this.clusterNamesSelect,
              selectMultiple: false,
              selectRequire: true,
              selectPlaceholder: "Select cluster",
              selectSearch: false
          }}
        );
     }

      // for table legibility
      this.addLegibility("data", student, data);
    }
    this.table.data = data;
    this.loading.table.results = false;
  }

  addLegibility(mode: string, student?: User, data?: { type: TableDataType, content: any }[][]){
    if (this.days.length > 3){
      if (mode === 'headers'){
        this.table.headers.push(this.table.headers[2]);
        (this.table.options.columnDefs[0].targets).push((this.table.options.columnDefs[0].targets).length);

        this.table.headers.push(this.table.headers[1]);
        (this.table.options.columnDefs[0].targets).push((this.table.options.columnDefs[0].targets).length);

      } else if (mode === 'data'){
        data[data.length - 1].push({type: TableDataType.NUMBER, content: {value: parseInt(String(student.studentNumber)), valueFormat: 'none'}});
        data[data.length - 1].push({type: TableDataType.AVATAR, content: {
            avatarSrc: student.photoUrl,
            avatarTitle: (student.nickname !== null && student.nickname !== "") ? student.nickname : student.name,
            avatarSubtitle: student.major}});
      }
    }
  }

  async refreshResults() {
    this.loading.action = true;

    await this.buildStatusTable();
    this.resetData();
    await this.getClusters();

    this.loading.action = false;
  }


  /*** --------------------------------------------------- ***/
  /*** ----------------- Import/ Export ------------------ ***/
  /*** --------------------------------------------------- ***/

  exportItem() { // FIXME
    if (Object.keys(this.newClusters).length === 0){
      AlertService.showAlert(AlertType.WARNING, 'There are no profiler results to export');

    } else {
      this.loading.action = true;

      // FIXME -- SHOULD RETURN STRING? (see users example?)
      const contents = this.api.exportModuleItems(this.course.id, ApiHttpService.PROFILING, null)
        .pipe( finalize(() => this.loading.action = false) ).subscribe(res => {});
      // DownloadManager.downloadAsCSV((this.course.short ?? this.course.name) + '-profiler results', contents);

      this.loading.action = false;
    }

  }

  //importItems(replace: boolean): void { // FIXME
    // this.loadingAction = true;
    //
    // const reader = new FileReader();
    // reader.onload = (e) => {
    //   const importedItems = reader.result;
    //   this.api.importModuleItems(this.course.id, ApiHttpService.PROFILING, importedItems, replace)
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
  //}

  async importItems(): Promise<void>{
    if (this.fImport.valid){
      this.loading.action = true;

      const file = await ResourceManager.getText(this.importedFile.file);
      //const nrResultsImported = await this.api.importModuleItems(this.course.id, ApiHttpService.PROFILING, file, this.importedFile.replace).toPromise();

    }  else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async doAction(action: string): Promise<void> {
    if (action === 'choose prediction method'){
      this.mode = "predict";
      ModalService.openModal('prediction-method');

    } else if (action === 'run predictor'){
      if (this.methodSelected !== null){
        await this.runPredictor();
        this.resetPredictionMethod();
      } else AlertService.showAlert(AlertType.ERROR, "Invalid method");

    } else if (action === Action.IMPORT){
      this.mode = "import";
      ModalService.openModal('import-modal');

    } else if (action === 'submit import') {

      // FIXME : something else missing ?
      // FIXME -- NAO CHEGA AQUI
      this.resetImportModal();

    } else if (action === 'discard changes'){
      if (this.origin === 'profiler' || this.origin === 'drafts'){
          this.mode = "discard";
          ModalService.openModal('discard-changes');
        } else {
          AlertService.showAlert(AlertType.WARNING, "Nothing to discard");
        }
    } else if (action === 'reset fields'){
      if (this.newClusters !== this.results){
        this.loading.action = true;

        this.table.headers = _.cloneDeep(this.headers);
        this.table.options.columnDefs[0].targets = _.cloneDeep(this.targets);

        this.newClusters = JSON.parse(JSON.stringify(this.results));
        this.buildResultsTable();
        this.loading.action = false;
      }
    }
  }


  /*** ----------------------------------------------- ***/
  /*** ------------------ Resetters ------------------ ***/
  /*** ----------------------------------------------- ***/

  resetPredictionMethod(){
    this.mode = null;
    this.methodSelected = null;
    ModalService.closeModal('prediction-method');
  }

  resetImportModal(){
    this.mode = null;
    this.importedFile = null;
    ModalService.closeModal('import-modal');
  }

  resetDiscardModal(){
    this.mode = null;
    ModalService.closeModal('discard-changes');
  }

  resetData(resetAll: boolean = true){
    if (resetAll) {
      // reset table properties
      this.table.headers = _.cloneDeep(this.headers);
      this.table.options.columnDefs[0].targets = _.cloneDeep(this.targets);
    }

    this.results = {};
    this.newClusters = {};
    this.origin = null;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  selectCluster(event: any, row: number){
    let studentHistory = this.history[row];
    const student = this.students.find(el => el.id === parseInt(studentHistory.id));
    this.newClusters[student.id] = event.value;
  }

  onFileSelected(files: FileList): void {
    const resultsFile = files.item(0);
    const reader = new FileReader();
    reader.onload = (e) => {
      this.importedFile = JSON.parse(reader.result as string);
    }
    reader.readAsText(resultsFile);
  }

  keys(obj): string[] {
    return Object.keys(obj ?? {});
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

