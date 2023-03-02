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
