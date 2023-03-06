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
  // styleUrls: ['./profiling.component.scss']
})
export class ProfilingComponent implements OnInit {

  loading: boolean = true;
  loadingAction: boolean;
  courseID: number;

  nrClusters: number = 4;
  minClusterSize: number = 4;
  endDate: string = moment().format('YYYY-MM-DDTHH:mm:ss');
  clusterNamesSelect: { value: string, text: string }[] = [];
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
    //const res = await this.api.getHistory(this.courseID).toPromise();
    const res = {
      "days": [
        "2022-03-15 22:31:32",
        "2022-03-20 12:12:27",
        "2022-04-08 09:44:09",
        "2022-04-11 18:18:27",
        "2022-04-19 13:00:42",
        "2022-04-26 19:31:44",
        "2022-05-05 17:26:32"
      ],
      "history": [
        {
          "id": "113",
          "name": "David Ribeiro",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "114",
          "name": "Paulo Cabeças",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "115",
          "name": "Pedro Teixeira",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "116",
          "name": "Guilherme Serpa",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Underachiever",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "117",
          "name": "Rafael Pires",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Regular_halfheartedlike",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "120",
          "name": "André Santos",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "121",
          "name": "António Santos",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Underachiever",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "123",
          "name": "João Pimenta",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "124",
          "name": "Pedro Santos",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "125",
          "name": "Alexandre Rodrigues",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "126",
          "name": "Miguel Coelho",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "127",
          "name": "Pedro Matono",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Regular_halfheartedlike"
        },
        {
          "id": "129",
          "name": "Rodrigo Nunes",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "130",
          "name": "Afonso Vasconcelos",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "132",
          "name": "Guilherme Carlota",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "133",
          "name": "Rodrigo Rosa",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Regular_halfheartedlike",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Regular"
        },
        {
          "id": "134",
          "name": "Tomás Costa",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "135",
          "name": "Tomás Costa",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "136",
          "name": "Carolina Pereira",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "137",
          "name": "Daniela Castanho",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "138",
          "name": "Francisco Marques",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "139",
          "name": "Guilherme Fernandes",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "140",
          "name": "Laura Baeta",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "141",
          "name": "Lúcia Silva",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "142",
          "name": "Paulina Wykowska",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Regular_halfheartedlike",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Regular_halfheartedlike"
        },
        {
          "id": "143",
          "name": "Pedro Nora",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "144",
          "name": "Tomás Saraiva",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Regular_halfheartedlike",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "145",
          "name": "Tomás Sequeira",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Regular_halfheartedlike",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "146",
          "name": "Vítor Vale",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Regular_halfheartedlike",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Regular_halfheartedlike"
        },
        {
          "id": "147",
          "name": "Leonor Morgado",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "148",
          "name": "Lucas Piper",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "149",
          "name": "Mariana Garcia",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "150",
          "name": "Patrícia Vilão",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "151",
          "name": "Tomás Coheur",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "152",
          "name": "Bernardo Quinteiro",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "153",
          "name": "Catarina Sousa",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "154",
          "name": "Diogo Lopes",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "155",
          "name": "Diogo Mendonça",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "156",
          "name": "Francisco Rodrigues",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "157",
          "name": "Guilherme Saraiva",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "158",
          "name": "Maria Ribeiro",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "159",
          "name": "Miguel Silva",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Regular_halfheartedlike",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Regular_halfheartedlike"
        },
        {
          "id": "160",
          "name": "Ricardo Subtil",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Regular_halfheartedlike",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Regular_halfheartedlike"
        },
        {
          "id": "161",
          "name": "Sara Ferreira",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "162",
          "name": "Miguel Gonçalves",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "164",
          "name": "María Legaza",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Underachiever",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "165",
          "name": "Thor-Herman Eggelen",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "166",
          "name": "Shima Bakhtiyari",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Underachiever",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "168",
          "name": "Solveig Grimstad",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "169",
          "name": "Louis Dutheil",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "170",
          "name": "María García",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Underachiever",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "171",
          "name": "Paulo Cardoso",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Underachiever",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "172",
          "name": "João Marto",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "173",
          "name": "Maria Gomes",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "174",
          "name": "Pedro Bento",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "175",
          "name": "Raquel Chin",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Achiever",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "176",
          "name": "Miguel Keim",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "177",
          "name": "Marina Martins",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Achiever",
          "2022-04-26 19:31:44": "Achiever",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "178",
          "name": "Julian Holzegger",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Regular_achieverlike"
        },
        {
          "id": "180",
          "name": "Jaakko Väkevä",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "181",
          "name": "Pierre Corbay",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "182",
          "name": "Annika Gerigoorian",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "183",
          "name": "Maha Kloub",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "184",
          "name": "Ingrid Nordlund",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Achiever",
          "2022-04-08 09:44:09": "Achiever",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "185",
          "name": "Valentin Mehnert",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "186",
          "name": "Maria Jacobson",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "209",
          "name": "Larissa Tomaz",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Regular_achieverlike",
          "2022-04-19 13:00:42": "Regular_achieverlike",
          "2022-04-26 19:31:44": "Regular_achieverlike",
          "2022-05-05 17:26:32": "Achiever"
        },
        {
          "id": "210",
          "name": "Raphaël Colcombet",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "289",
          "name": "Saif Abdoelrazak",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "290",
          "name": "Luís Ferreira",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "291",
          "name": "Marc Jelkic",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Underachiever",
          "2022-04-11 18:18:27": "Underachiever",
          "2022-04-19 13:00:42": "Underachiever",
          "2022-04-26 19:31:44": "Underachiever",
          "2022-05-05 17:26:32": "Underachiever"
        },
        {
          "id": "292",
          "name": "David Fontoura",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "293",
          "name": "Miguel Santos",
          "2022-03-15 22:31:32": "Regular",
          "2022-03-20 12:12:27": "Regular_halfheartedlike",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "294",
          "name": "Laura Acela",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Halfhearted",
          "2022-04-08 09:44:09": "Regular_halfheartedlike",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Regular_halfheartedlike"
        },
        {
          "id": "367",
          "name": "Ebba Rovig",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "368",
          "name": "Rodrigo Fernandes",
          "2022-03-15 22:31:32": "Underachiever",
          "2022-03-20 12:12:27": "Underachiever",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Halfhearted",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "369",
          "name": "Felix Schöllhammer",
          "2022-03-15 22:31:32": "Halfhearted",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Halfhearted",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Halfhearted",
          "2022-05-05 17:26:32": "Halfhearted"
        },
        {
          "id": "370",
          "name": "Francisca Paiva",
          "2022-03-15 22:31:32": "Achiever",
          "2022-03-20 12:12:27": "Regular_achieverlike",
          "2022-04-08 09:44:09": "Regular_achieverlike",
          "2022-04-11 18:18:27": "Halfhearted",
          "2022-04-19 13:00:42": "Regular_halfheartedlike",
          "2022-04-26 19:31:44": "Regular_halfheartedlike",
          "2022-05-05 17:26:32": "Achiever"
        }
      ],
      "nodes": [
        {
          "id": "Achiever0",
          "name": "Achiever",
          "color": "#7cb5ec"
        },
        {
          "id": "Achiever1",
          "name": "Achiever",
          "color": "#7cb5ec"
        },
        {
          "id": "Achiever2",
          "name": "Achiever",
          "color": "#7cb5ec"
        },
        {
          "id": "Achiever3",
          "name": "Achiever",
          "color": "#7cb5ec"
        },
        {
          "id": "Achiever4",
          "name": "Achiever",
          "color": "#7cb5ec"
        },
        {
          "id": "Achiever5",
          "name": "Achiever",
          "color": "#7cb5ec"
        },
        {
          "id": "Achiever6",
          "name": "Achiever",
          "color": "#7cb5ec"
        },
        {
          "id": "Regular0",
          "name": "Regular",
          "color": "#90ed7d"
        },
        {
          "id": "Regular1",
          "name": "Regular",
          "color": "#90ed7d"
        },
        {
          "id": "Regular2",
          "name": "Regular",
          "color": "#90ed7d"
        },
        {
          "id": "Regular3",
          "name": "Regular",
          "color": "#90ed7d"
        },
        {
          "id": "Regular4",
          "name": "Regular",
          "color": "#90ed7d"
        },
        {
          "id": "Regular5",
          "name": "Regular",
          "color": "#90ed7d"
        },
        {
          "id": "Regular6",
          "name": "Regular",
          "color": "#90ed7d"
        },
        {
          "id": "Regular_achieverlike0",
          "name": "Regular_achieverlike",
          "color": "#f7a35c"
        },
        {
          "id": "Regular_achieverlike1",
          "name": "Regular_achieverlike",
          "color": "#f7a35c"
        },
        {
          "id": "Regular_achieverlike2",
          "name": "Regular_achieverlike",
          "color": "#f7a35c"
        },
        {
          "id": "Regular_achieverlike3",
          "name": "Regular_achieverlike",
          "color": "#f7a35c"
        },
        {
          "id": "Regular_achieverlike4",
          "name": "Regular_achieverlike",
          "color": "#f7a35c"
        },
        {
          "id": "Regular_achieverlike5",
          "name": "Regular_achieverlike",
          "color": "#f7a35c"
        },
        {
          "id": "Regular_achieverlike6",
          "name": "Regular_achieverlike",
          "color": "#f7a35c"
        },
        {
          "id": "Regular_halfheartedlike0",
          "name": "Regular_halfheartedlike",
          "color": "#8085e9"
        },
        {
          "id": "Regular_halfheartedlike1",
          "name": "Regular_halfheartedlike",
          "color": "#8085e9"
        },
        {
          "id": "Regular_halfheartedlike2",
          "name": "Regular_halfheartedlike",
          "color": "#8085e9"
        },
        {
          "id": "Regular_halfheartedlike3",
          "name": "Regular_halfheartedlike",
          "color": "#8085e9"
        },
        {
          "id": "Regular_halfheartedlike4",
          "name": "Regular_halfheartedlike",
          "color": "#8085e9"
        },
        {
          "id": "Regular_halfheartedlike5",
          "name": "Regular_halfheartedlike",
          "color": "#8085e9"
        },
        {
          "id": "Regular_halfheartedlike6",
          "name": "Regular_halfheartedlike",
          "color": "#8085e9"
        },
        {
          "id": "Halfhearted0",
          "name": "Halfhearted",
          "color": "#f15c80"
        },
        {
          "id": "Halfhearted1",
          "name": "Halfhearted",
          "color": "#f15c80"
        },
        {
          "id": "Halfhearted2",
          "name": "Halfhearted",
          "color": "#f15c80"
        },
        {
          "id": "Halfhearted3",
          "name": "Halfhearted",
          "color": "#f15c80"
        },
        {
          "id": "Halfhearted4",
          "name": "Halfhearted",
          "color": "#f15c80"
        },
        {
          "id": "Halfhearted5",
          "name": "Halfhearted",
          "color": "#f15c80"
        },
        {
          "id": "Halfhearted6",
          "name": "Halfhearted",
          "color": "#f15c80"
        },
        {
          "id": "Underachiever0",
          "name": "Underachiever",
          "color": "#e4d354"
        },
        {
          "id": "Underachiever1",
          "name": "Underachiever",
          "color": "#e4d354"
        },
        {
          "id": "Underachiever2",
          "name": "Underachiever",
          "color": "#e4d354"
        },
        {
          "id": "Underachiever3",
          "name": "Underachiever",
          "color": "#e4d354"
        },
        {
          "id": "Underachiever4",
          "name": "Underachiever",
          "color": "#e4d354"
        },
        {
          "id": "Underachiever5",
          "name": "Underachiever",
          "color": "#e4d354"
        },
        {
          "id": "Underachiever6",
          "name": "Underachiever",
          "color": "#e4d354"
        },
        {
          "id": "None0",
          "name": "None",
          "color": "#949494"
        },
        {
          "id": "None1",
          "name": "None",
          "color": "#949494"
        },
        {
          "id": "None2",
          "name": "None",
          "color": "#949494"
        },
        {
          "id": "None3",
          "name": "None",
          "color": "#949494"
        },
        {
          "id": "None4",
          "name": "None",
          "color": "#949494"
        },
        {
          "id": "None5",
          "name": "None",
          "color": "#949494"
        },
        {
          "id": "None6",
          "name": "None",
          "color": "#949494"
        }
      ],
      "data": [
        [
          "Achiever0",
          "Achiever1",
          9
        ],
        [
          "Achiever0",
          "Regular1",
          0
        ],
        [
          "Achiever0",
          "Regular_achieverlike1",
          5
        ],
        [
          "Achiever0",
          "Regular_halfheartedlike1",
          5
        ],
        [
          "Achiever0",
          "Halfhearted1",
          0
        ],
        [
          "Achiever0",
          "Underachiever1",
          0
        ],
        [
          "Achiever0",
          "None1",
          0
        ],
        [
          "Achiever1",
          "Achiever2",
          10
        ],
        [
          "Achiever1",
          "Regular2",
          0
        ],
        [
          "Achiever1",
          "Regular_achieverlike2",
          4
        ],
        [
          "Achiever1",
          "Regular_halfheartedlike2",
          0
        ],
        [
          "Achiever1",
          "Halfhearted2",
          0
        ],
        [
          "Achiever1",
          "Underachiever2",
          0
        ],
        [
          "Achiever1",
          "None2",
          0
        ],
        [
          "Achiever2",
          "Achiever3",
          11
        ],
        [
          "Achiever2",
          "Regular3",
          0
        ],
        [
          "Achiever2",
          "Regular_achieverlike3",
          5
        ],
        [
          "Achiever2",
          "Regular_halfheartedlike3",
          0
        ],
        [
          "Achiever2",
          "Halfhearted3",
          0
        ],
        [
          "Achiever2",
          "Underachiever3",
          0
        ],
        [
          "Achiever2",
          "None3",
          0
        ],
        [
          "Achiever3",
          "Achiever4",
          13
        ],
        [
          "Achiever3",
          "Regular4",
          0
        ],
        [
          "Achiever3",
          "Regular_achieverlike4",
          0
        ],
        [
          "Achiever3",
          "Regular_halfheartedlike4",
          0
        ],
        [
          "Achiever3",
          "Halfhearted4",
          0
        ],
        [
          "Achiever3",
          "Underachiever4",
          0
        ],
        [
          "Achiever3",
          "None4",
          0
        ],
        [
          "Achiever4",
          "Achiever5",
          16
        ],
        [
          "Achiever4",
          "Regular5",
          0
        ],
        [
          "Achiever4",
          "Regular_achieverlike5",
          1
        ],
        [
          "Achiever4",
          "Regular_halfheartedlike5",
          0
        ],
        [
          "Achiever4",
          "Halfhearted5",
          0
        ],
        [
          "Achiever4",
          "Underachiever5",
          0
        ],
        [
          "Achiever4",
          "None5",
          0
        ],
        [
          "Achiever5",
          "Achiever6",
          13
        ],
        [
          "Achiever5",
          "Regular6",
          0
        ],
        [
          "Achiever5",
          "Regular_achieverlike6",
          4
        ],
        [
          "Achiever5",
          "Regular_halfheartedlike6",
          0
        ],
        [
          "Achiever5",
          "Halfhearted6",
          0
        ],
        [
          "Achiever5",
          "Underachiever6",
          0
        ],
        [
          "Achiever5",
          "None6",
          0
        ],
        [
          "Regular0",
          "Achiever1",
          5
        ],
        [
          "Regular0",
          "Regular1",
          0
        ],
        [
          "Regular0",
          "Regular_achieverlike1",
          11
        ],
        [
          "Regular0",
          "Regular_halfheartedlike1",
          12
        ],
        [
          "Regular0",
          "Halfhearted1",
          2
        ],
        [
          "Regular0",
          "Underachiever1",
          0
        ],
        [
          "Regular0",
          "None1",
          0
        ],
        [
          "Regular1",
          "Achiever2",
          0
        ],
        [
          "Regular1",
          "Regular2",
          0
        ],
        [
          "Regular1",
          "Regular_achieverlike2",
          0
        ],
        [
          "Regular1",
          "Regular_halfheartedlike2",
          0
        ],
        [
          "Regular1",
          "Halfhearted2",
          0
        ],
        [
          "Regular1",
          "Underachiever2",
          0
        ],
        [
          "Regular1",
          "None2",
          0
        ],
        [
          "Regular2",
          "Achiever3",
          0
        ],
        [
          "Regular2",
          "Regular3",
          0
        ],
        [
          "Regular2",
          "Regular_achieverlike3",
          0
        ],
        [
          "Regular2",
          "Regular_halfheartedlike3",
          0
        ],
        [
          "Regular2",
          "Halfhearted3",
          0
        ],
        [
          "Regular2",
          "Underachiever3",
          0
        ],
        [
          "Regular2",
          "None3",
          0
        ],
        [
          "Regular3",
          "Achiever4",
          0
        ],
        [
          "Regular3",
          "Regular4",
          0
        ],
        [
          "Regular3",
          "Regular_achieverlike4",
          0
        ],
        [
          "Regular3",
          "Regular_halfheartedlike4",
          0
        ],
        [
          "Regular3",
          "Halfhearted4",
          0
        ],
        [
          "Regular3",
          "Underachiever4",
          0
        ],
        [
          "Regular3",
          "None4",
          0
        ],
        [
          "Regular4",
          "Achiever5",
          0
        ],
        [
          "Regular4",
          "Regular5",
          0
        ],
        [
          "Regular4",
          "Regular_achieverlike5",
          0
        ],
        [
          "Regular4",
          "Regular_halfheartedlike5",
          0
        ],
        [
          "Regular4",
          "Halfhearted5",
          0
        ],
        [
          "Regular4",
          "Underachiever5",
          0
        ],
        [
          "Regular4",
          "None5",
          0
        ],
        [
          "Regular5",
          "Achiever6",
          0
        ],
        [
          "Regular5",
          "Regular6",
          0
        ],
        [
          "Regular5",
          "Regular_achieverlike6",
          0
        ],
        [
          "Regular5",
          "Regular_halfheartedlike6",
          0
        ],
        [
          "Regular5",
          "Halfhearted6",
          0
        ],
        [
          "Regular5",
          "Underachiever6",
          0
        ],
        [
          "Regular5",
          "None6",
          0
        ],
        [
          "Regular_achieverlike0",
          "Achiever1",
          0
        ],
        [
          "Regular_achieverlike0",
          "Regular1",
          0
        ],
        [
          "Regular_achieverlike0",
          "Regular_achieverlike1",
          0
        ],
        [
          "Regular_achieverlike0",
          "Regular_halfheartedlike1",
          0
        ],
        [
          "Regular_achieverlike0",
          "Halfhearted1",
          0
        ],
        [
          "Regular_achieverlike0",
          "Underachiever1",
          0
        ],
        [
          "Regular_achieverlike0",
          "None1",
          0
        ],
        [
          "Regular_achieverlike1",
          "Achiever2",
          2
        ],
        [
          "Regular_achieverlike1",
          "Regular2",
          0
        ],
        [
          "Regular_achieverlike1",
          "Regular_achieverlike2",
          12
        ],
        [
          "Regular_achieverlike1",
          "Regular_halfheartedlike2",
          0
        ],
        [
          "Regular_achieverlike1",
          "Halfhearted2",
          7
        ],
        [
          "Regular_achieverlike1",
          "Underachiever2",
          0
        ],
        [
          "Regular_achieverlike1",
          "None2",
          0
        ],
        [
          "Regular_achieverlike2",
          "Achiever3",
          2
        ],
        [
          "Regular_achieverlike2",
          "Regular3",
          0
        ],
        [
          "Regular_achieverlike2",
          "Regular_achieverlike3",
          12
        ],
        [
          "Regular_achieverlike2",
          "Regular_halfheartedlike3",
          0
        ],
        [
          "Regular_achieverlike2",
          "Halfhearted3",
          2
        ],
        [
          "Regular_achieverlike2",
          "Underachiever3",
          0
        ],
        [
          "Regular_achieverlike2",
          "None3",
          0
        ],
        [
          "Regular_achieverlike3",
          "Achiever4",
          3
        ],
        [
          "Regular_achieverlike3",
          "Regular4",
          0
        ],
        [
          "Regular_achieverlike3",
          "Regular_achieverlike4",
          13
        ],
        [
          "Regular_achieverlike3",
          "Regular_halfheartedlike4",
          0
        ],
        [
          "Regular_achieverlike3",
          "Halfhearted4",
          1
        ],
        [
          "Regular_achieverlike3",
          "Underachiever4",
          0
        ],
        [
          "Regular_achieverlike3",
          "None4",
          0
        ],
        [
          "Regular_achieverlike4",
          "Achiever5",
          1
        ],
        [
          "Regular_achieverlike4",
          "Regular5",
          0
        ],
        [
          "Regular_achieverlike4",
          "Regular_achieverlike5",
          11
        ],
        [
          "Regular_achieverlike4",
          "Regular_halfheartedlike5",
          1
        ],
        [
          "Regular_achieverlike4",
          "Halfhearted5",
          0
        ],
        [
          "Regular_achieverlike4",
          "Underachiever5",
          0
        ],
        [
          "Regular_achieverlike4",
          "None5",
          0
        ],
        [
          "Regular_achieverlike5",
          "Achiever6",
          1
        ],
        [
          "Regular_achieverlike5",
          "Regular6",
          1
        ],
        [
          "Regular_achieverlike5",
          "Regular_achieverlike6",
          6
        ],
        [
          "Regular_achieverlike5",
          "Regular_halfheartedlike6",
          0
        ],
        [
          "Regular_achieverlike5",
          "Halfhearted6",
          4
        ],
        [
          "Regular_achieverlike5",
          "Underachiever6",
          0
        ],
        [
          "Regular_achieverlike5",
          "None6",
          0
        ],
        [
          "Regular_halfheartedlike0",
          "Achiever1",
          0
        ],
        [
          "Regular_halfheartedlike0",
          "Regular1",
          0
        ],
        [
          "Regular_halfheartedlike0",
          "Regular_achieverlike1",
          0
        ],
        [
          "Regular_halfheartedlike0",
          "Regular_halfheartedlike1",
          0
        ],
        [
          "Regular_halfheartedlike0",
          "Halfhearted1",
          0
        ],
        [
          "Regular_halfheartedlike0",
          "Underachiever1",
          0
        ],
        [
          "Regular_halfheartedlike0",
          "None1",
          0
        ],
        [
          "Regular_halfheartedlike1",
          "Achiever2",
          4
        ],
        [
          "Regular_halfheartedlike1",
          "Regular2",
          0
        ],
        [
          "Regular_halfheartedlike1",
          "Regular_achieverlike2",
          0
        ],
        [
          "Regular_halfheartedlike1",
          "Regular_halfheartedlike2",
          8
        ],
        [
          "Regular_halfheartedlike1",
          "Halfhearted2",
          9
        ],
        [
          "Regular_halfheartedlike1",
          "Underachiever2",
          0
        ],
        [
          "Regular_halfheartedlike1",
          "None2",
          0
        ],
        [
          "Regular_halfheartedlike2",
          "Achiever3",
          0
        ],
        [
          "Regular_halfheartedlike2",
          "Regular3",
          0
        ],
        [
          "Regular_halfheartedlike2",
          "Regular_achieverlike3",
          0
        ],
        [
          "Regular_halfheartedlike2",
          "Regular_halfheartedlike3",
          7
        ],
        [
          "Regular_halfheartedlike2",
          "Halfhearted3",
          2
        ],
        [
          "Regular_halfheartedlike2",
          "Underachiever3",
          0
        ],
        [
          "Regular_halfheartedlike2",
          "None3",
          0
        ],
        [
          "Regular_halfheartedlike3",
          "Achiever4",
          1
        ],
        [
          "Regular_halfheartedlike3",
          "Regular4",
          0
        ],
        [
          "Regular_halfheartedlike3",
          "Regular_achieverlike4",
          0
        ],
        [
          "Regular_halfheartedlike3",
          "Regular_halfheartedlike4",
          5
        ],
        [
          "Regular_halfheartedlike3",
          "Halfhearted4",
          2
        ],
        [
          "Regular_halfheartedlike3",
          "Underachiever4",
          0
        ],
        [
          "Regular_halfheartedlike3",
          "None4",
          0
        ],
        [
          "Regular_halfheartedlike4",
          "Achiever5",
          0
        ],
        [
          "Regular_halfheartedlike4",
          "Regular5",
          0
        ],
        [
          "Regular_halfheartedlike4",
          "Regular_achieverlike5",
          0
        ],
        [
          "Regular_halfheartedlike4",
          "Regular_halfheartedlike5",
          8
        ],
        [
          "Regular_halfheartedlike4",
          "Halfhearted5",
          1
        ],
        [
          "Regular_halfheartedlike4",
          "Underachiever5",
          0
        ],
        [
          "Regular_halfheartedlike4",
          "None5",
          0
        ],
        [
          "Regular_halfheartedlike5",
          "Achiever6",
          1
        ],
        [
          "Regular_halfheartedlike5",
          "Regular6",
          0
        ],
        [
          "Regular_halfheartedlike5",
          "Regular_achieverlike6",
          0
        ],
        [
          "Regular_halfheartedlike5",
          "Regular_halfheartedlike6",
          6
        ],
        [
          "Regular_halfheartedlike5",
          "Halfhearted6",
          5
        ],
        [
          "Regular_halfheartedlike5",
          "Underachiever6",
          0
        ],
        [
          "Regular_halfheartedlike5",
          "None6",
          0
        ],
        [
          "Halfhearted0",
          "Achiever1",
          0
        ],
        [
          "Halfhearted0",
          "Regular1",
          0
        ],
        [
          "Halfhearted0",
          "Regular_achieverlike1",
          5
        ],
        [
          "Halfhearted0",
          "Regular_halfheartedlike1",
          4
        ],
        [
          "Halfhearted0",
          "Halfhearted1",
          9
        ],
        [
          "Halfhearted0",
          "Underachiever1",
          0
        ],
        [
          "Halfhearted0",
          "None1",
          0
        ],
        [
          "Halfhearted1",
          "Achiever2",
          0
        ],
        [
          "Halfhearted1",
          "Regular2",
          0
        ],
        [
          "Halfhearted1",
          "Regular_achieverlike2",
          0
        ],
        [
          "Halfhearted1",
          "Regular_halfheartedlike2",
          1
        ],
        [
          "Halfhearted1",
          "Halfhearted2",
          10
        ],
        [
          "Halfhearted1",
          "Underachiever2",
          4
        ],
        [
          "Halfhearted1",
          "None2",
          0
        ],
        [
          "Halfhearted2",
          "Achiever3",
          0
        ],
        [
          "Halfhearted2",
          "Regular3",
          0
        ],
        [
          "Halfhearted2",
          "Regular_achieverlike3",
          0
        ],
        [
          "Halfhearted2",
          "Regular_halfheartedlike3",
          1
        ],
        [
          "Halfhearted2",
          "Halfhearted3",
          26
        ],
        [
          "Halfhearted2",
          "Underachiever3",
          0
        ],
        [
          "Halfhearted2",
          "None3",
          0
        ],
        [
          "Halfhearted3",
          "Achiever4",
          0
        ],
        [
          "Halfhearted3",
          "Regular4",
          0
        ],
        [
          "Halfhearted3",
          "Regular_achieverlike4",
          0
        ],
        [
          "Halfhearted3",
          "Regular_halfheartedlike4",
          4
        ],
        [
          "Halfhearted3",
          "Halfhearted4",
          25
        ],
        [
          "Halfhearted3",
          "Underachiever4",
          2
        ],
        [
          "Halfhearted3",
          "None4",
          0
        ],
        [
          "Halfhearted4",
          "Achiever5",
          0
        ],
        [
          "Halfhearted4",
          "Regular5",
          0
        ],
        [
          "Halfhearted4",
          "Regular_achieverlike5",
          0
        ],
        [
          "Halfhearted4",
          "Regular_halfheartedlike5",
          3
        ],
        [
          "Halfhearted4",
          "Halfhearted5",
          25
        ],
        [
          "Halfhearted4",
          "Underachiever5",
          1
        ],
        [
          "Halfhearted4",
          "None5",
          0
        ],
        [
          "Halfhearted5",
          "Achiever6",
          0
        ],
        [
          "Halfhearted5",
          "Regular6",
          0
        ],
        [
          "Halfhearted5",
          "Regular_achieverlike6",
          0
        ],
        [
          "Halfhearted5",
          "Regular_halfheartedlike6",
          0
        ],
        [
          "Halfhearted5",
          "Halfhearted6",
          26
        ],
        [
          "Halfhearted5",
          "Underachiever6",
          0
        ],
        [
          "Halfhearted5",
          "None6",
          0
        ],
        [
          "Underachiever0",
          "Achiever1",
          0
        ],
        [
          "Underachiever0",
          "Regular1",
          0
        ],
        [
          "Underachiever0",
          "Regular_achieverlike1",
          0
        ],
        [
          "Underachiever0",
          "Regular_halfheartedlike1",
          0
        ],
        [
          "Underachiever0",
          "Halfhearted1",
          4
        ],
        [
          "Underachiever0",
          "Underachiever1",
          7
        ],
        [
          "Underachiever0",
          "None1",
          0
        ],
        [
          "Underachiever1",
          "Achiever2",
          0
        ],
        [
          "Underachiever1",
          "Regular2",
          0
        ],
        [
          "Underachiever1",
          "Regular_achieverlike2",
          0
        ],
        [
          "Underachiever1",
          "Regular_halfheartedlike2",
          0
        ],
        [
          "Underachiever1",
          "Halfhearted2",
          1
        ],
        [
          "Underachiever1",
          "Underachiever2",
          6
        ],
        [
          "Underachiever1",
          "None2",
          0
        ],
        [
          "Underachiever2",
          "Achiever3",
          0
        ],
        [
          "Underachiever2",
          "Regular3",
          0
        ],
        [
          "Underachiever2",
          "Regular_achieverlike3",
          0
        ],
        [
          "Underachiever2",
          "Regular_halfheartedlike3",
          0
        ],
        [
          "Underachiever2",
          "Halfhearted3",
          1
        ],
        [
          "Underachiever2",
          "Underachiever3",
          9
        ],
        [
          "Underachiever2",
          "None3",
          0
        ],
        [
          "Underachiever3",
          "Achiever4",
          0
        ],
        [
          "Underachiever3",
          "Regular4",
          0
        ],
        [
          "Underachiever3",
          "Regular_achieverlike4",
          0
        ],
        [
          "Underachiever3",
          "Regular_halfheartedlike4",
          0
        ],
        [
          "Underachiever3",
          "Halfhearted4",
          1
        ],
        [
          "Underachiever3",
          "Underachiever4",
          8
        ],
        [
          "Underachiever3",
          "None4",
          0
        ],
        [
          "Underachiever4",
          "Achiever5",
          0
        ],
        [
          "Underachiever4",
          "Regular5",
          0
        ],
        [
          "Underachiever4",
          "Regular_achieverlike5",
          0
        ],
        [
          "Underachiever4",
          "Regular_halfheartedlike5",
          0
        ],
        [
          "Underachiever4",
          "Halfhearted5",
          0
        ],
        [
          "Underachiever4",
          "Underachiever5",
          10
        ],
        [
          "Underachiever4",
          "None5",
          0
        ],
        [
          "Underachiever5",
          "Achiever6",
          0
        ],
        [
          "Underachiever5",
          "Regular6",
          0
        ],
        [
          "Underachiever5",
          "Regular_achieverlike6",
          0
        ],
        [
          "Underachiever5",
          "Regular_halfheartedlike6",
          0
        ],
        [
          "Underachiever5",
          "Halfhearted6",
          2
        ],
        [
          "Underachiever5",
          "Underachiever6",
          9
        ],
        [
          "Underachiever5",
          "None6",
          0
        ],
        [
          "None0",
          "Achiever1",
          0
        ],
        [
          "None0",
          "Regular1",
          0
        ],
        [
          "None0",
          "Regular_achieverlike1",
          0
        ],
        [
          "None0",
          "Regular_halfheartedlike1",
          0
        ],
        [
          "None0",
          "Halfhearted1",
          0
        ],
        [
          "None0",
          "Underachiever1",
          0
        ],
        [
          "None0",
          "None1",
          0
        ],
        [
          "None1",
          "Achiever2",
          0
        ],
        [
          "None1",
          "Regular2",
          0
        ],
        [
          "None1",
          "Regular_achieverlike2",
          0
        ],
        [
          "None1",
          "Regular_halfheartedlike2",
          0
        ],
        [
          "None1",
          "Halfhearted2",
          0
        ],
        [
          "None1",
          "Underachiever2",
          0
        ],
        [
          "None1",
          "None2",
          0
        ],
        [
          "None2",
          "Achiever3",
          0
        ],
        [
          "None2",
          "Regular3",
          0
        ],
        [
          "None2",
          "Regular_achieverlike3",
          0
        ],
        [
          "None2",
          "Regular_halfheartedlike3",
          0
        ],
        [
          "None2",
          "Halfhearted3",
          0
        ],
        [
          "None2",
          "Underachiever3",
          0
        ],
        [
          "None2",
          "None3",
          0
        ],
        [
          "None3",
          "Achiever4",
          0
        ],
        [
          "None3",
          "Regular4",
          0
        ],
        [
          "None3",
          "Regular_achieverlike4",
          0
        ],
        [
          "None3",
          "Regular_halfheartedlike4",
          0
        ],
        [
          "None3",
          "Halfhearted4",
          0
        ],
        [
          "None3",
          "Underachiever4",
          0
        ],
        [
          "None3",
          "None4",
          0
        ],
        [
          "None4",
          "Achiever5",
          0
        ],
        [
          "None4",
          "Regular5",
          0
        ],
        [
          "None4",
          "Regular_achieverlike5",
          0
        ],
        [
          "None4",
          "Regular_halfheartedlike5",
          0
        ],
        [
          "None4",
          "Halfhearted5",
          0
        ],
        [
          "None4",
          "Underachiever5",
          0
        ],
        [
          "None4",
          "None5",
          0
        ],
        [
          "None5",
          "Achiever6",
          0
        ],
        [
          "None5",
          "Regular6",
          0
        ],
        [
          "None5",
          "Regular_achieverlike6",
          0
        ],
        [
          "None5",
          "Regular_halfheartedlike6",
          0
        ],
        [
          "None5",
          "Halfhearted6",
          0
        ],
        [
          "None5",
          "Underachiever6",
          0
        ],
        [
          "None5",
          "None6",
          0
        ]
      ]
    }
    this.history = res.history;
    this.nodes = res.nodes;
    this.data = res.data;
    this.days = res.days.length > 0 ? res.days : ["Current"];
    if (this.data.length > 0) this.buildChart();
  }

  async getStudents() {
    //let students = await this.api.getCourseUsersWithRole(this.courseID, "Student").toPromise();
    let students = [
      {
        "id": "4",
        "name": "Joaquim Jorge",
        "nickname": null,
        "studentNumber": "3909",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-T",
        "email": "jaj@inesc-id.pt",
        "lastLogin": "2022-06-06 11:38:01",
        "username": "ist13909",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "8",
        "name": "Daniel Gonçalves",
        "nickname": null,
        "studentNumber": "3898",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-A",
        "email": "daniel.goncalves@inesc-id.pt",
        "lastLogin": "2023-03-01 12:51:36",
        "username": "ist13898",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "10",
        "name": "Sandra Gama",
        "nickname": null,
        "studentNumber": "52404",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-T",
        "email": "sandra.gama@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-05 16:30:11",
        "username": "ist152404",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "11",
        "name": "Tomás Alves",
        "nickname": null,
        "studentNumber": "75541",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-T",
        "email": "tomas.alves@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-06 11:04:16",
        "username": "ist175541",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "12",
        "name": "Rita Marques",
        "nickname": null,
        "studentNumber": "69369",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-A",
        "email": "rita.c.marques@gmail.com",
        "lastLogin": "2022-03-28 16:34:53",
        "username": "ist169369",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "13",
        "name": "João Moreira",
        "nickname": null,
        "studentNumber": "79003",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-A",
        "email": "joaopedro.moreira@protonmail.com",
        "lastLogin": "2022-05-06 11:05:01",
        "username": "ist179003",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "43",
        "name": "Daniel Luís Lopes Serafim",
        "nickname": "",
        "studentNumber": "89428",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-A",
        "email": "daniel.serafim@tecnico.ulisboa.pt",
        "lastLogin": "2022-06-01 11:47:58",
        "username": "ist189428",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "44",
        "name": "Vasco Carvalho Ferreira Pires",
        "nickname": null,
        "studentNumber": "87708",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-T",
        "email": "vascocfpires@tecnico.ulisboa.pt",
        "lastLogin": "2022-04-29 10:24:22",
        "username": "ist187708",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "45",
        "name": "Soraia Meneses Alarcão",
        "nickname": null,
        "studentNumber": "57690",
        "roles": [
          "Teacher"
        ],
        "major": "MEIC-T",
        "email": "soraiamenesesalarcao@tecnico.ulisboa.pt",
        "lastLogin": "2022-04-08 20:43:20",
        "username": "ist157690",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "113",
        "name": "David Alexandre Vaz Ribeiro",
        "nickname": "",
        "studentNumber": "75526",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "LEMec",
        "email": "david.vaz@tecnico.ulisboa.pt",
        "lastLogin": "2022-07-23 01:00:33",
        "username": "ist175526",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "114",
        "name": "Paulo Jorge Tavares Cabeças",
        "nickname": "Paulo",
        "studentNumber": "76358",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "LEIC-T",
        "email": "cabecas.paulo@gmail.com",
        "lastLogin": "2022-08-08 14:34:42",
        "username": "ist176358",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "115",
        "name": "Pedro Miguel Da Silva Teixeira",
        "nickname": "",
        "studentNumber": "81416",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "pedro.teixeira.96@hotmail.com",
        "lastLogin": "2022-09-09 16:21:12",
        "username": "ist181416",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "116",
        "name": "Guilherme Martins Silva Caramona Serpa",
        "nickname": "",
        "studentNumber": "82078",
        "roles": [
          "Student",
          "Underachiever"
        ],
        "major": "MEIC-T",
        "email": "gmscs@icloud.com",
        "lastLogin": "2022-05-05 17:34:22",
        "username": "ist182078",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "117",
        "name": "Rafael Sousa Aguilar Margalho Pires",
        "nickname": "",
        "studentNumber": "83555",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "LEIC-A",
        "email": "rafael.m.pires@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-06 01:07:14",
        "username": "ist424858",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "118",
        "name": "Maria Inês de Magalhães Torres Queiroz de Morais",
        "nickname": "",
        "studentNumber": "83609",
        "roles": [
          "Student"
        ],
        "major": "MEIC-T",
        "email": "ines.q.morais@tecnico.ulisboa.pt",
        "lastLogin": "2022-04-06 22:28:27",
        "username": "ist424912",
        "authenticationService": "fenix",
        "isActive": "0",
        "hasImage": true
      },
      {
        "id": "119",
        "name": "Nuno De Barbosa Colen e Azevedo Damas",
        "nickname": "",
        "studentNumber": "84155",
        "roles": [
          "Student"
        ],
        "major": "LEIC-A",
        "email": "nuno.damas@tecnico.ulisboa.pt",
        "lastLogin": null,
        "username": "ist425455",
        "authenticationService": "fenix",
        "isActive": "0",
        "hasImage": true
      },
      {
        "id": "120",
        "name": "André Gonçalo Silvestre dos Santos",
        "nickname": "",
        "studentNumber": "84699",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-T",
        "email": "andre.s.dos.santos@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-17 01:42:04",
        "username": "ist425999",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "121",
        "name": "António Maria Costa Bernardo Machado Santos",
        "nickname": "",
        "studentNumber": "87632",
        "roles": [
          "Student",
          "Underachiever"
        ],
        "major": "MEIC-T",
        "email": "antonio.maria@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-09 15:43:02",
        "username": "ist187632",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "122",
        "name": "João Carlos Jerónimo Antunes",
        "nickname": "",
        "studentNumber": "87668",
        "roles": [
          "Student"
        ],
        "major": "MEIC-T",
        "email": "joao.c.jeronimo.antunes@tecnico.ulisboa.pt",
        "lastLogin": null,
        "username": "ist187668",
        "authenticationService": "fenix",
        "isActive": "0",
        "hasImage": false
      },
      {
        "id": "123",
        "name": "João Miguel Seabra Pedroso Pimenta",
        "nickname": "",
        "studentNumber": "87674",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-T",
        "email": "joao.m.pimenta@tecnico.ulisboa.pt",
        "lastLogin": "2022-04-28 23:29:59",
        "username": "ist187674",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "124",
        "name": "Pedro Rafael Lopes Santos",
        "nickname": "",
        "studentNumber": "87734",
        "roles": [
          "Student",
          "Underachiever"
        ],
        "major": "LEE",
        "email": "pedro.lopes.santos@tecnico.ulisboa.pt",
        "lastLogin": "2022-04-26 18:11:04",
        "username": "ist187734",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "125",
        "name": "Alexandre Antunes Rodrigues",
        "nickname": "",
        "studentNumber": "89404",
        "roles": [
          "Student",
          "Regular_achieverlike"
        ],
        "major": "MEIC-A",
        "email": "alexandre.rodrigues@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-19 16:59:00",
        "username": "ist189404",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "126",
        "name": "Miguel Gomes Coelho",
        "nickname": "",
        "studentNumber": "89509",
        "roles": [
          "Student",
          "Underachiever"
        ],
        "major": "MEIC-A",
        "email": "miguel.g.coelho@tecnico.ulisboa.pt",
        "lastLogin": "2022-06-11 01:25:07",
        "username": "ist189509",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "127",
        "name": "Pedro Miguel De Andrade Rovisco Matono",
        "nickname": "",
        "studentNumber": "89523",
        "roles": [
          "Student",
          "Regular_halfheartedlike"
        ],
        "major": "MEIC-A",
        "email": "pedro.rovisco.matono@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-05 19:39:36",
        "username": "ist189523",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "128",
        "name": "Rafael Nunes Henriques",
        "nickname": "",
        "studentNumber": "89530",
        "roles": [
          "Student"
        ],
        "major": "LEIC-A",
        "email": "rafael.henriques@tecnico.ulisboa.pt",
        "lastLogin": null,
        "username": "ist189530",
        "authenticationService": "fenix",
        "isActive": "0",
        "hasImage": false
      },
      {
        "id": "129",
        "name": "Rodrigo Carvalho de Alvarenga Nunes",
        "nickname": "",
        "studentNumber": "90353",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "LEIC-A",
        "email": "rodrigo.alvarenga.nunes@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-09 13:49:42",
        "username": "ist190353",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "130",
        "name": "Afonso Faria de Vasconcelos",
        "nickname": "",
        "studentNumber": "90698",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-T",
        "email": "afonso.vasconcelos@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-06 13:12:58",
        "username": "ist190698",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "131",
        "name": "Guilherme Filipe De Almeida Monteiro",
        "nickname": "",
        "studentNumber": "90724",
        "roles": [
          "Student"
        ],
        "major": "MEIC-T",
        "email": "guilhermefam99@gmail.com",
        "lastLogin": null,
        "username": "ist190724",
        "authenticationService": "fenix",
        "isActive": "0",
        "hasImage": false
      },
      {
        "id": "132",
        "name": "Guilherme Pereira Carlota",
        "nickname": "",
        "studentNumber": "90725",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "LEIC-T",
        "email": "guilherme.carlota@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-27 22:09:02",
        "username": "ist190725",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "133",
        "name": "Rodrigo Palma Pelado Pereira Rosa",
        "nickname": "",
        "studentNumber": "90777",
        "roles": [
          "Student",
          "Regular"
        ],
        "major": "LEIC-T",
        "email": "rodrigo.rosa@tecnico.ulisboa.pt",
        "lastLogin": "2022-07-08 17:02:09",
        "username": "ist190777",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "134",
        "name": "Tomás Folgado Louro Dias Costa",
        "nickname": "",
        "studentNumber": "90783",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-T",
        "email": "tomas.folgado@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-13 12:07:19",
        "username": "ist190783",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "135",
        "name": "Tomás De Menezes Costa",
        "nickname": "",
        "studentNumber": "90918",
        "roles": [
          "Student",
          "Profiling",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "tomascosta2212@gmail.com",
        "lastLogin": "2023-01-10 02:08:00",
        "username": "ist190918",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "136",
        "name": "Carolina Micaela Pinto Duarte Pereira",
        "nickname": "",
        "studentNumber": "92433",
        "roles": [
          "Student",
          "Profiling",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "carolina.m.pereira@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-05 20:33:18",
        "username": "ist192433",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "137",
        "name": "Daniela Narciso Castanho",
        "nickname": "Daniela Castanho",
        "studentNumber": "92442",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "daniela.castanho@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-22 16:19:00",
        "username": "ist192442",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "138",
        "name": "Francisco Lisboa Ricardo Marques",
        "nickname": "",
        "studentNumber": "92464",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "francisco.lisboa.ricardo.marques@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-08 15:31:57",
        "username": "ist192464",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "139",
        "name": "Guilherme Mimoso Montez Fernandes",
        "nickname": "",
        "studentNumber": "92473",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "g.mimoso.fernandes@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-05 22:11:04",
        "username": "ist192473",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "140",
        "name": "Laura Constança Ferreira Baeta",
        "nickname": "",
        "studentNumber": "92507",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "LEIC-A",
        "email": "laura.c.f.baeta@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-26 19:19:24",
        "username": "ist192507",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "141",
        "name": "Lúcia Filipa Lopes da Silva",
        "nickname": "",
        "studentNumber": "92510",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "lucia.silva@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-09 03:27:39",
        "username": "ist192510",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "142",
        "name": "Paulina Izabela Wykowska",
        "nickname": "",
        "studentNumber": "92534",
        "roles": [
          "Student",
          "Regular_halfheartedlike"
        ],
        "major": "LEIC-A",
        "email": "paulina.izabela@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-08 20:55:26",
        "username": "ist192534",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "143",
        "name": "Pedro Afonso Da Boa Morte Cruz Nora",
        "nickname": "",
        "studentNumber": "92536",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "LEIC-A",
        "email": "pedro.nora@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-05 22:07:57",
        "username": "ist192536",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "144",
        "name": "Tomás Andrade Saraiva",
        "nickname": "",
        "studentNumber": "92564",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "tomas.a.saraiva@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-05 20:53:56",
        "username": "ist192564",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "145",
        "name": "Tomás Lobo Sequeira",
        "nickname": "",
        "studentNumber": "92565",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "tomas.sequeira@tecnico.ulisboa.pt",
        "lastLogin": "2022-06-09 19:24:41",
        "username": "ist192565",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "146",
        "name": "Vítor Manuel Ferreira do Vale",
        "nickname": "",
        "studentNumber": "92570",
        "roles": [
          "Student",
          "Regular_halfheartedlike"
        ],
        "major": "MEIC-A",
        "email": "vitorvale007@gmail.com",
        "lastLogin": "2022-05-07 12:38:17",
        "username": "ist192570",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "147",
        "name": "Leonor Valadas Preto Pereira Morgado",
        "nickname": "",
        "studentNumber": "92906",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "leonor.morgado@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-13 14:28:30",
        "username": "ist192906",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "148",
        "name": "Lucas Alegy Raichande Piper",
        "nickname": "",
        "studentNumber": "93290",
        "roles": [
          "Student",
          "Profiling",
          "Achiever"
        ],
        "major": "MEIC-T",
        "email": "lucaspiper99@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-08 20:39:07",
        "username": "ist193290",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "149",
        "name": "Mariana Colaço Garcia",
        "nickname": "",
        "studentNumber": "93597",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "mariana.colaco.garcia@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-07 15:23:35",
        "username": "ist193597",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "150",
        "name": "Patrícia Borges Vilão",
        "nickname": "",
        "studentNumber": "93604",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "patriciavilao@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-09 16:32:58",
        "username": "ist193604",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "151",
        "name": "Tomás Marques da Silva Coheur",
        "nickname": "",
        "studentNumber": "93621",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "LETI",
        "email": "tomas.coheur@tecnico.ulisboa.pt",
        "lastLogin": "2022-04-29 21:31:21",
        "username": "ist193621",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "152",
        "name": "Bernardo De Jesus e Quinteiro",
        "nickname": "",
        "studentNumber": "93692",
        "roles": [
          "Student",
          "Profiling",
          "Regular_achieverlike"
        ],
        "major": "LEIC-T",
        "email": "bernardo.quinteiro@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-09 15:27:46",
        "username": "ist193692",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "153",
        "name": "Catarina Sofia Dos Santos Sousa",
        "nickname": "",
        "studentNumber": "93695",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "catarinasousa2000@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-08 10:09:20",
        "username": "ist193695",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "154",
        "name": "Diogo André Fulgêncio Lopes",
        "nickname": "",
        "studentNumber": "93700",
        "roles": [
          "Student",
          "Profiling",
          "Regular_achieverlike"
        ],
        "major": "LEIC-T",
        "email": "diogo.andre.fulgencio.lopes@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-09 21:32:15",
        "username": "ist193700",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "155",
        "name": "Diogo Cunha Mendonça",
        "nickname": "",
        "studentNumber": "93701",
        "roles": [
          "Student",
          "Regular_achieverlike"
        ],
        "major": "MEIC-T",
        "email": "diogo.c.mendonca@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-20 12:26:04",
        "username": "ist193701",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "156",
        "name": "Francisco Miguel Saiote Rodrigues",
        "nickname": "",
        "studentNumber": "93711",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "LEIC-T",
        "email": "francisco.saiote@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-07 03:51:31",
        "username": "ist193711",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "157",
        "name": "Guilherme Almeida Saraiva",
        "nickname": "",
        "studentNumber": "93717",
        "roles": [
          "Student",
          "Regular_achieverlike"
        ],
        "major": "MEIC-T",
        "email": "guilherme.a.saraiva@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-09 18:35:14",
        "username": "ist193717",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "158",
        "name": "Maria Francisco Ribeiro",
        "nickname": "",
        "studentNumber": "93735",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "maria.f.ribeiro@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-08 21:25:25",
        "username": "ist193735",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "159",
        "name": "Miguel Amaral da Silva",
        "nickname": "",
        "studentNumber": "93739",
        "roles": [
          "Student",
          "Regular_halfheartedlike"
        ],
        "major": "LEIC-T",
        "email": "miguel.amaral.silva@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-06 14:41:52",
        "username": "ist193739",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "160",
        "name": "Ricardo Jorge Santos Subtil",
        "nickname": "",
        "studentNumber": "93752",
        "roles": [
          "Student",
          "Regular_halfheartedlike"
        ],
        "major": "MEIC-T",
        "email": "ricardo.subtil@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-06 15:35:58",
        "username": "ist193752",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "161",
        "name": "Sara Coelho Ferreira",
        "nickname": "",
        "studentNumber": "93756",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-T",
        "email": "sara.c.ferreira@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-07 00:07:28",
        "username": "ist193756",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "162",
        "name": "Miguel Nunes Gonçalves",
        "nickname": "",
        "studentNumber": "94238",
        "roles": [
          "Student",
          "Regular_achieverlike"
        ],
        "major": "MEIC-T",
        "email": "miguel.nunes.goncalves@tecnico.ulisboa.pt",
        "lastLogin": "2022-04-28 23:53:23",
        "username": "ist194238",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "163",
        "name": "João Vasco Almeida Sobral Siborro Reis",
        "nickname": "",
        "studentNumber": "95611",
        "roles": [
          "Student"
        ],
        "major": "LEIC-A",
        "email": "joao.vasco.sobral@tecnico.ulisboa.pt",
        "lastLogin": null,
        "username": "ist195611",
        "authenticationService": "fenix",
        "isActive": "0",
        "hasImage": false
      },
      {
        "id": "164",
        "name": "María Ureña Legaza",
        "nickname": "",
        "studentNumber": "101376",
        "roles": [
          "Student",
          "Profiling",
          "Underachiever"
        ],
        "major": "MEIC-A",
        "email": "maria.urena.25c@gmail.com",
        "lastLogin": "2022-05-05 18:00:06",
        "username": "ist1101376",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "165",
        "name": "Thor-Herman van Eggelen",
        "nickname": "",
        "studentNumber": "101528",
        "roles": [
          "Student",
          "Profiling",
          "Regular_achieverlike"
        ],
        "major": "MEIC-A",
        "email": "thorherman.eggelen@gmail.com",
        "lastLogin": "2022-07-23 16:03:29",
        "username": "ist1101528",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "166",
        "name": "Shima Bakhtiyari",
        "nickname": "",
        "studentNumber": "101596",
        "roles": [
          "Student",
          "Profiling",
          "Underachiever"
        ],
        "major": "MEMec",
        "email": "shima@bakhtiyari.eu",
        "lastLogin": "2022-04-08 13:08:53",
        "username": "ist1101596",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "167",
        "name": "Benedicte Kaltoft Hansen",
        "nickname": "",
        "studentNumber": "101823",
        "roles": [
          "Student"
        ],
        "major": "MEIC-A",
        "email": "benedkh@stud.ntnu.no",
        "lastLogin": null,
        "username": "ist1101823",
        "authenticationService": "fenix",
        "isActive": "0",
        "hasImage": false
      },
      {
        "id": "168",
        "name": "Solveig Bergan Grimstad",
        "nickname": "",
        "studentNumber": "101838",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEMec",
        "email": "solveig.b.grimstad@gmail.com",
        "lastLogin": "2022-05-08 19:39:18",
        "username": "ist1101838",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "169",
        "name": "Louis Kim Dutheil",
        "nickname": "",
        "studentNumber": "101860",
        "roles": [
          "Student",
          "Regular_achieverlike"
        ],
        "major": "MEIC-A",
        "email": "dutheil.louis@polymtl.ca",
        "lastLogin": "2022-04-29 23:33:38",
        "username": "ist1101860",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "170",
        "name": "María Soler García",
        "nickname": "",
        "studentNumber": "101923",
        "roles": [
          "Student",
          "Profiling",
          "Underachiever"
        ],
        "major": "MEEC",
        "email": "msolergarcia25@gmail.com",
        "lastLogin": "2022-05-05 17:58:45",
        "username": "ist1101923",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "171",
        "name": "Paulo Tomás Falcão de Almeida Cardoso",
        "nickname": "",
        "studentNumber": "102113",
        "roles": [
          "Student",
          "Profiling",
          "Underachiever"
        ],
        "major": "MEIC-A",
        "email": "paulotomas14@gmail.com",
        "lastLogin": "2022-04-17 22:03:06",
        "username": "ist1102113",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "172",
        "name": "João Pedro Oliveira Marto",
        "nickname": "",
        "studentNumber": "102174",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "joaopomarto@tecnico.ulisboa.pt",
        "lastLogin": "2022-07-26 21:19:15",
        "username": "ist1102174",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "173",
        "name": "Maria Froufe Vilar de Lima Gomes",
        "nickname": "",
        "studentNumber": "102203",
        "roles": [
          "Student",
          "Regular_achieverlike"
        ],
        "major": "MEIC-T",
        "email": "summermaria13@gmail.com",
        "lastLogin": "2022-05-06 17:17:06",
        "username": "ist1102203",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "174",
        "name": "Pedro José Moreira Bento",
        "nickname": "",
        "studentNumber": "102225",
        "roles": [
          "Student",
          "Profiling",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "pbento2000abc@gmail.com",
        "lastLogin": "2022-05-06 00:20:46",
        "username": "ist1102225",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "175",
        "name": "Raquel Sofia Diogo de Oliveira Chin",
        "nickname": "",
        "studentNumber": "102240",
        "roles": [
          "Student",
          "Profiling",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "raquel.o.chin@gmail.com",
        "lastLogin": "2022-05-16 14:35:40",
        "username": "ist1102240",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "176",
        "name": "Miguel Maria do Nascimento Simões Mortágua Keim",
        "nickname": "",
        "studentNumber": "102305",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-T",
        "email": "miguel.nsm.keim@gmail.com",
        "lastLogin": "2022-05-05 21:08:22",
        "username": "ist1102305",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "177",
        "name": "Marina Pereira Martins",
        "nickname": "",
        "studentNumber": "104080",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-T",
        "email": "marina_pereira_martins@hotmail.com",
        "lastLogin": "2022-06-21 01:37:33",
        "username": "ist1104080",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "178",
        "name": "Julian Holzegger",
        "nickname": "",
        "studentNumber": "104224",
        "roles": [
          "Student",
          "Profiling",
          "Regular_achieverlike"
        ],
        "major": "MEIC-A",
        "email": "julian.holzegger@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-07 22:02:18",
        "username": "ist1104224",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "179",
        "name": "Daniel Nils Braun",
        "nickname": "",
        "studentNumber": "104238",
        "roles": [
          "Student"
        ],
        "major": "MEIC-A",
        "email": "daniel.n.braun@tecnico.ulisboa.pt",
        "lastLogin": null,
        "username": "ist1104238",
        "authenticationService": "fenix",
        "isActive": "0",
        "hasImage": false
      },
      {
        "id": "180",
        "name": "Jaakko Rikhard Väkevä",
        "nickname": "",
        "studentNumber": "104268",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "jaakko.vakeva@tecnico.ulisboa.pt",
        "lastLogin": "2022-06-01 15:58:53",
        "username": "ist1104268",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "181",
        "name": "Pierre Alexander Corbay",
        "nickname": "",
        "studentNumber": "104319",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "pierre.corbay@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-25 17:47:24",
        "username": "ist1104319",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "182",
        "name": "Annika Gerigoorian",
        "nickname": "",
        "studentNumber": "104380",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "annika.gerigoorian@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-05 19:25:30",
        "username": "ist1104380",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "183",
        "name": "Maha Kloub",
        "nickname": "",
        "studentNumber": "104381",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "maha.kloub@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-05 19:22:57",
        "username": "ist1104381",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "184",
        "name": "Ingrid Nicole Nordlund",
        "nickname": "",
        "studentNumber": "104497",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "nicolenordlund@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-01 15:53:12",
        "username": "ist1104497",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "185",
        "name": "Valentin Mehnert",
        "nickname": "",
        "studentNumber": "104510",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "valentin.mehnert@tecnico.ulisboa.pt",
        "lastLogin": "2022-06-30 19:54:37",
        "username": "ist1104510",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "186",
        "name": "Maria Davidsdotter Jacobson",
        "nickname": "",
        "studentNumber": "104615",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "maria.jacobson@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-02 14:01:50",
        "username": "ist1104615",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "209",
        "name": "Larissa Mendes da Silva Tomaz",
        "nickname": "",
        "studentNumber": "92506",
        "roles": [
          "Teacher",
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "tomaz_lary@hotmail.com",
        "lastLogin": "2022-05-15 02:18:43",
        "username": "ist192506",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "210",
        "name": "Raphaël de Araújo Colcombet",
        "nickname": "",
        "studentNumber": "81957",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-T",
        "email": "raphael.clc@hotmail.fr",
        "lastLogin": "2022-09-05 21:41:24",
        "username": "ist181957",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "289",
        "name": "Saif Abdoelrazak",
        "nickname": "",
        "studentNumber": "104230",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "saif.abdoelrazak@gmail.com",
        "lastLogin": "2022-05-17 17:20:31",
        "username": "ist1104230",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "290",
        "name": "Luís Pedro Oliveira Ferreira",
        "nickname": "",
        "studentNumber": "83500",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "LEIC-A",
        "email": "luis.pedro.f@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-07 15:59:48",
        "username": "ist424803",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "291",
        "name": "Marc Alexandre Jelkic",
        "nickname": "",
        "studentNumber": "84741",
        "roles": [
          "Student",
          "Profiling",
          "Underachiever"
        ],
        "major": "MEIC-T",
        "email": "marc.jelkic@tecnico.ulisboa.pt",
        "lastLogin": "2022-03-24 12:49:03",
        "username": "ist426041",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "292",
        "name": "David Teixeira Fontoura",
        "nickname": "",
        "studentNumber": "89792",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "LEBiom",
        "email": "david.fontoura@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-29 03:30:47",
        "username": "ist189792",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "293",
        "name": "Miguel Conrado dos Santos",
        "nickname": "",
        "studentNumber": "93601",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "LETI",
        "email": "miguel.conrado.santos@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-16 19:18:32",
        "username": "ist193601",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "294",
        "name": "Laura Helena Cabra Acela",
        "nickname": "",
        "studentNumber": "104412",
        "roles": [
          "Student",
          "Profiling",
          "Regular_achieverlike"
        ],
        "major": "MEIC-A",
        "email": "laura.helena.cabra.acela@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-02 13:52:52",
        "username": "ist1104412",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "367",
        "name": "Ebba Rovig",
        "nickname": null,
        "studentNumber": "104383",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "ebbarovig@gmail.com",
        "lastLogin": "2022-05-02 14:00:14",
        "username": "ist1104383",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "368",
        "name": "Rodrigo Fernandes",
        "nickname": null,
        "studentNumber": "86509",
        "roles": [
          "Student",
          "Profiling",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "rodrigo.d.fernandes@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-22 23:59:49",
        "username": "ist186509",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "369",
        "name": "Felix Schöllhammer",
        "nickname": null,
        "studentNumber": "104442",
        "roles": [
          "Student",
          "Halfhearted"
        ],
        "major": "MEIC-A",
        "email": "felix.schollhammer@tecnico.pt",
        "lastLogin": "2022-05-17 16:40:14",
        "username": "ist1104442",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "370",
        "name": "Francisca Maria dos Santos de Paiva",
        "nickname": null,
        "studentNumber": "96525",
        "roles": [
          "Student",
          "Achiever"
        ],
        "major": "MEIC-A",
        "email": "francisca.paiva@tecnico.ulisboa.pt",
        "lastLogin": "2022-05-16 02:25:30",
        "username": "ist196525",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      },
      {
        "id": "487",
        "name": "Mónica Sánchez Lemos Alves",
        "nickname": "Mónica Alves",
        "studentNumber": "90759",
        "roles": [
          "Teacher"
        ],
        "major": "LEIC-T",
        "email": "monica.alves@tecnico.ulisboa.pt",
        "lastLogin": "2023-03-03 13:13:23",
        "username": "ist190759",
        "authenticationService": "fenix",
        "isActive": "1",
        "hasImage": true
      }
    ];

    for (const student of students) {
      this.students[student.id] = student;
    }
  }

  async getLastRun() {
    this.lastRun = await this.api.getLastRun(this.courseID).toPromise();
  }

  async getSavedClusters() {
    const res = await this.api.getSavedClusters(this.courseID).toPromise(); // returns "saved" (uncommitted changes) and "names" (cluster names)

    this.clusterNamesSelect = res.names.map(name => {return {value: name, text: name}});
    this.select = res.saved;

    await this.checkPredictorStatus();
    await this.checkProfilerStatus();
    this.buildResultsTable();

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
    this.clusters = null;
    this.select = null;
    this.loadingAction = false;
  }

  async checkProfilerStatus() {
    this.loadingAction = true;

    //const status = await this.api.checkProfilerStatus(this.courseID).toPromise();
    const status =  {  //  response to running profiler
      "clusters": {
        "113": {
          "name": "David Ribeiro",
          "cluster": "Halfhearted"
        },
        "114": {
          "name": "Paulo Cabeças",
          "cluster": "Halfhearted"
        },
        "115": {
          "name": "Pedro Teixeira",
          "cluster": "Halfhearted"
        },
        "116": {
          "name": "Guilherme Serpa",
          "cluster": "Underachiever"
        },
        "117": {
          "name": "Rafael Pires",
          "cluster": "Regular"
        },
        "120": {
          "name": "André Santos",
          "cluster": "Regular"
        },
        "121": {
          "name": "António Santos",
          "cluster": "Underachiever"
        },
        "123": {
          "name": "João Pimenta",
          "cluster": "Halfhearted"
        },
        "124": {
          "name": "Pedro Santos",
          "cluster": "Underachiever"
        },
        "125": {
          "name": "Alexandre Rodrigues",
          "cluster": "Achiever"
        },
        "126": {
          "name": "Miguel Coelho",
          "cluster": "Underachiever"
        },
        "127": {
          "name": "Pedro Matono",
          "cluster": "Regular"
        },
        "129": {
          "name": "Rodrigo Nunes",
          "cluster": "Halfhearted"
        },
        "130": {
          "name": "Afonso Vasconcelos",
          "cluster": "Halfhearted"
        },
        "132": {
          "name": "Guilherme Carlota",
          "cluster": "Regular"
        },
        "133": {
          "name": "Rodrigo Rosa",
          "cluster": "Regular"
        },
        "134": {
          "name": "Tomás Costa",
          "cluster": "Halfhearted"
        },
        "135": {
          "name": "Tomás Costa",
          "cluster": "Achiever"
        },
        "136": {
          "name": "Carolina Pereira",
          "cluster": "Achiever"
        },
        "137": {
          "name": "Daniela Castanho",
          "cluster": "Achiever"
        },
        "138": {
          "name": "Francisco Marques",
          "cluster": "Achiever"
        },
        "139": {
          "name": "Guilherme Fernandes",
          "cluster": "Regular"
        },
        "140": {
          "name": "Laura Baeta",
          "cluster": "Halfhearted"
        },
        "141": {
          "name": "Lúcia Silva",
          "cluster": "Halfhearted"
        },
        "142": {
          "name": "Paulina Wykowska",
          "cluster": "Achiever"
        },
        "143": {
          "name": "Pedro Nora",
          "cluster": "Halfhearted"
        },
        "144": {
          "name": "Tomás Saraiva",
          "cluster": "Regular"
        },
        "145": {
          "name": "Tomás Sequeira",
          "cluster": "Regular"
        },
        "146": {
          "name": "Vítor Vale",
          "cluster": "Achiever"
        },
        "147": {
          "name": "Leonor Morgado",
          "cluster": "Achiever"
        },
        "148": {
          "name": "Lucas Piper",
          "cluster": "Achiever"
        },
        "149": {
          "name": "Mariana Garcia",
          "cluster": "Achiever"
        },
        "150": {
          "name": "Patrícia Vilão",
          "cluster": "Achiever"
        },
        "151": {
          "name": "Tomás Coheur",
          "cluster": "Halfhearted"
        },
        "152": {
          "name": "Bernardo Quinteiro",
          "cluster": "Regular"
        },
        "153": {
          "name": "Catarina Sousa",
          "cluster": "Achiever"
        },
        "154": {
          "name": "Diogo Lopes",
          "cluster": "Regular"
        },
        "155": {
          "name": "Diogo Mendonça",
          "cluster": "Regular"
        },
        "156": {
          "name": "Francisco Rodrigues",
          "cluster": "Regular"
        },
        "157": {
          "name": "Guilherme Saraiva",
          "cluster": "Regular"
        },
        "158": {
          "name": "Maria Ribeiro",
          "cluster": "Achiever"
        },
        "159": {
          "name": "Miguel Silva",
          "cluster": "Regular"
        },
        "160": {
          "name": "Ricardo Subtil",
          "cluster": "Regular"
        },
        "161": {
          "name": "Sara Ferreira",
          "cluster": "Regular"
        },
        "162": {
          "name": "Miguel Gonçalves",
          "cluster": "Regular"
        },
        "164": {
          "name": "María Legaza",
          "cluster": "Underachiever"
        },
        "165": {
          "name": "Thor-Herman Eggelen",
          "cluster": "Regular"
        },
        "166": {
          "name": "Shima Bakhtiyari",
          "cluster": "Underachiever"
        },
        "168": {
          "name": "Solveig Grimstad",
          "cluster": "Halfhearted"
        },
        "169": {
          "name": "Louis Dutheil",
          "cluster": "Regular"
        },
        "170": {
          "name": "María García",
          "cluster": "Underachiever"
        },
        "171": {
          "name": "Paulo Cardoso",
          "cluster": "Underachiever"
        },
        "172": {
          "name": "João Marto",
          "cluster": "Halfhearted"
        },
        "173": {
          "name": "Maria Gomes",
          "cluster": "Regular"
        },
        "174": {
          "name": "Pedro Bento",
          "cluster": "Achiever"
        },
        "175": {
          "name": "Raquel Chin",
          "cluster": "Achiever"
        },
        "176": {
          "name": "Miguel Keim",
          "cluster": "Halfhearted"
        },
        "177": {
          "name": "Marina Martins",
          "cluster": "Achiever"
        },
        "178": {
          "name": "Julian Holzegger",
          "cluster": "Regular"
        },
        "180": {
          "name": "Jaakko Väkevä",
          "cluster": "Halfhearted"
        },
        "181": {
          "name": "Pierre Corbay",
          "cluster": "Halfhearted"
        },
        "182": {
          "name": "Annika Gerigoorian",
          "cluster": "Halfhearted"
        },
        "183": {
          "name": "Maha Kloub",
          "cluster": "Halfhearted"
        },
        "184": {
          "name": "Ingrid Nordlund",
          "cluster": "Halfhearted"
        },
        "185": {
          "name": "Valentin Mehnert",
          "cluster": "Halfhearted"
        },
        "186": {
          "name": "Maria Jacobson",
          "cluster": "Halfhearted"
        },
        "209": {
          "name": "Larissa Tomaz",
          "cluster": "Achiever"
        },
        "210": {
          "name": "Raphaël Colcombet",
          "cluster": "Halfhearted"
        },
        "289": {
          "name": "Saif Abdoelrazak",
          "cluster": "Halfhearted"
        },
        "290": {
          "name": "Luís Ferreira",
          "cluster": "Regular"
        },
        "291": {
          "name": "Marc Jelkic",
          "cluster": "Underachiever"
        },
        "292": {
          "name": "David Fontoura",
          "cluster": "Halfhearted"
        },
        "293": {
          "name": "Miguel Santos",
          "cluster": "Halfhearted"
        },
        "294": {
          "name": "Laura Acela",
          "cluster": "Regular"
        },
        "367": {
          "name": "Ebba Rovig",
          "cluster": "Halfhearted"
        },
        "368": {
          "name": "Rodrigo Fernandes",
          "cluster": "Regular"
        },
        "369": {
          "name": "Felix Schöllhammer",
          "cluster": "Halfhearted"
        },
        "370": {
          "name": "Francisca Paiva",
          "cluster": "Achiever"
        }
      },
      "names": [
        {
          "name": "Achiever"
        },
        {
          "name": "Regular"
        },
        {
          "name": "Regular_achieverlike"
        },
        {
          "name": "Regular_halfheartedlike"
        },
        {
          "name": "Halfhearted"
        },
        {
          "name": "Underachiever"
        }
      ]
    };
    //if (typeof status == 'boolean') {
    //  this.profilerIsRunning = status;

    //} else { // got clusters as result
      this.clusters = status.clusters;
      this.clusterNamesSelect = status.names.map(clusterName => {return {value: clusterName.name, text: clusterName.name}}); // FIXME: (DEBUG ONLY) change clusterName.name to name
      this.profilerIsRunning = false;
    //}

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
        {type: TableDataType.TEXT, content: {text: (student.nickname !== null && student.nickname !== "") ? student.nickname : student.name}},
        {type: TableDataType.AVATAR, content: {
          avatarSrc: student.photoUrl,
          avatarTitle: (student.nickname !== null && student.nickname !== "") ? student.nickname : student.name,
          avatarSubtitle: student.major}},
        {type: TableDataType.NUMBER, content: {value: parseInt(String(student.studentNumber)), valueFormat: 'none'}}
      ];
      for (const day of this.days) {
        data.push({type: TableDataType.TEXT, content: {text: studentHistory[day]}});
      }

     if (this.select.length > 0) { // See if there's uncommitted changes
        data.push({type: TableDataType.TEXT, content: {text: this.select[student.id]}});
      } else if (this.clusters){ // else shows profiler results from running
        let aux = (student.id).toString();
        aux = "cluster-" + aux;
          data.push({type: TableDataType.SELECT, content: {
              selectId: aux,
              selectValue: this.clusters[student.id].cluster,
              selectOptions: this.clusterNamesSelect,
              selectMultiple: false,
              selectRequire: true,
              selectPlaceholder: "Select cluster",
              selectSearch: false
            }});
      }

      // for table legibility
      if (this.days.length > 3){
        data.push({type: TableDataType.NUMBER, content: {value: parseInt(String(student.studentNumber)), valueFormat: 'none'}});
        data.push({type: TableDataType.AVATAR, content: {
            avatarSrc: student.photoUrl,
            avatarTitle: (student.nickname !== null && student.nickname !== "") ? student.nickname : student.name,
            avatarSubtitle: student.major}});
      }

      this.table.data.push(data);
    }

    if (this.select.length > 0 || this.clusters) {
      this.table.headers.push({label: 'Current', align: 'middle'});
      this.table.options.columnDefs[0].targets.push(this.table.headers.length - 1);
    }

    // for table legibility
    if (this.days.length > 3){
      this.table.headers.push(this.table.headers[2]);
      this.table.options.columnDefs[0].targets.push(this.table.headers.length - 1);

      this.table.headers.push(this.table.headers[1]);
      this.table.options.columnDefs[0].targets.push(this.table.headers.length - 1);
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

  selectCluster(event: any, row: number){
    let studentHistory = this.history[row];
    const student: User = this.students[studentHistory.id];

    this.clusters[student.id].cluster = event;
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
    const reader = new FileReader();
    reader.onload = (e) => {
      // FIXME
      // this.import = JSON.parse(reader.result as string);
    }
    reader.readAsText(this.importedFile);
  }

  /*
  getEditableResults(): {name: string, cluster: string}[] {
    return Object.values(this.clusters).sort((a, b) => a.name.localeCompare(b.name));
  }*/



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
