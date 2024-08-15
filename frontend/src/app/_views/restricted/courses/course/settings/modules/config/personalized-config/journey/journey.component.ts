import {Component, OnInit, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import { JourneyPath } from "src/app/_domain/modules/config/personalized-config/journey/journey-path";
import {TableDataType} from "../../../../../../../../../_components/tables/table-data/table-data.component";
import {Skill} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/skill";
import {Action} from "../../../../../../../../../_domain/modules/config/Action";
import {Tier} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/tier";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../../../../_utils/misc/misc";
import {ModalService} from "../../../../../../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../../../../../../_services/alert.service";

@Component({
  selector: 'app-journey',
  templateUrl: './journey.component.html'
})
export class JourneyComponent implements OnInit {

  loading = {
    page: true,
    paths: true,
    action: false,
  }

  courseID: number;
  courseFolder: string;

  journeyPaths: JourneyPath[];
  journeyPathsInfo: {
    id: number,
    loading: boolean,
    skills: Skill[]
  }[];
  data: {type: TableDataType, content: any}[][] = [];

  pathMode: 'create' | 'edit';
  pathToManage: PathManageData = this.initPathToManage();
  pathToDelete: Tier;
  @ViewChild('fPath', { static: false }) fPath: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public router: Router
  ) {
  }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);

      await this.initJourneyPathsInfo(this.courseID);

      this.loading.page = false;
    });
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async initJourneyPathsInfo(courseID: number) {
    this.journeyPathsInfo = [];
    this.journeyPaths = await this.api.getJourneyPaths(courseID).toPromise();

    for (const path of this.journeyPaths) {
      // Get info
      //const skills = await this.api.getSkillsOfJourneyPath(skillTree.id, null, null, null).toPromise();
      const skills = [];
      this.journeyPathsInfo.push({id: path.id, loading: false, skills: skills});
    }
    // Build table
    this.buildPathsTable();
  }

  /*** --------------------------------------------- ***/
  /*** ------------------- Tables ------------------ ***/
  /*** --------------------------------------------- ***/

  tablesInfo: {
    paths: {
      headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
      tableOptions: any
    }
  } = {
    paths: {
      headers: [
        {label: 'Path', align: 'left'},
        {label: 'Skills', align: 'left'},
        {label: 'Reward (XP)', align: 'middle'},
        {label: 'Active', align: 'middle'},
        {label: 'View Rule'},
        {label: 'Actions'}
      ],
      tableOptions: {
        order: [[ 0, 'asc' ]], // default order
        columnDefs: [
          { type: 'natural', targets: [0] },
          {orderable: false, targets: [3, 4, 5]}
        ]
      }
    }
  }

  buildPathsTable(): void {
    this.loading.paths = true;

    const table: { type: TableDataType, content: any }[][] = [];
    this.journeyPaths.forEach(path => {
      const row: { type: TableDataType, content: any }[] = [
        {type: TableDataType.TEXT, content: {text: path.name}},
        {type: TableDataType.TEXT, content: {text: path.color, classList: 'font-semibold'}},
        {type: TableDataType.NUMBER, content: {value: 0}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: path.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.VIEW_RULE]}},
        {type: TableDataType.ACTIONS, content: {actions: [
              Action.EDIT,
              Action.DELETE
            ]}}
      ];

      table.push(row);
    });

    this.data = table;
    this.loading.paths = false;
  }

  async doActionOnTable(action: string, row: number, col: number, value?: any): Promise<void> {
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async doAction(action: string) {
    if (action === 'Create path') {
      this.pathMode = 'create';
      this.pathToManage = this.initPathToManage();
      ModalService.openModal('path-manage');
    }
  }

  async createPath(): Promise<void> {
    if (this.fPath.valid) {
      this.loading.action = true;

      await this.api.createJourneyPath(this.courseID, clearEmptyValues(this.pathToManage)).toPromise();
      this.journeyPaths = await this.api.getJourneyPaths(this.courseID).toPromise();
      this.buildPathsTable();

      ModalService.closeModal('path-manage');
      AlertService.showAlert(AlertType.SUCCESS, 'New Journey Path added: ' + this.pathToManage.name);
      this.resetPathToManage();

      this.loading.action = false;

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async editPath(): Promise<void> {
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initPathToManage(path?: JourneyPath): PathManageData {
    const pathData: PathManageData = {
      name: path?.name ?? null,
      color: path?.color ?? null,
    };
    if (path) pathData.id = path.id;
    return pathData;
  }

  resetPathToManage() {
    this.pathMode = null;
    this.pathToManage = this.initPathToManage();
    this.fPath.resetForm();
  }

}

export interface PathManageData {
  id?: number,
  name: string,
  color: string
}
