import {Component, OnInit, ViewChild} from "@angular/core";
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";
import { JourneyPath } from "src/app/_domain/modules/config/personalized-config/journey/journey-path";
import {TableDataType} from "../../../../../../../../../_components/tables/table-data/table-data.component";
import {Skill} from "../../../../../../../../../_domain/modules/config/personalized-config/skills/skill";
import {Action} from "../../../../../../../../../_domain/modules/config/Action";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../../../../_utils/misc/misc";
import {ModalService} from "../../../../../../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../../../../../../_services/alert.service";
import {CdkDragDrop, moveItemInArray} from "@angular/cdk/drag-drop";
import * as _ from "lodash";

@Component({
  selector: 'app-journey',
  templateUrl: './journey.component.html',
  styleUrls: ['./journey.component.scss']

})
export class JourneyComponent implements OnInit {

  loading = {
    page: true,
    paths: true,
    action: false,
  }

  courseID: number;
  courseFolder: string;

  showAdd: boolean = false;

  skills: Skill[];
  skillToAdd: string;
  skillsAvailable: {value: string, text: string, html?: string}[];

  journeyPaths: JourneyPath[];
  data: {type: TableDataType, content: any}[][] = [];

  pathMode: 'create' | 'edit';
  pathToManage: PathManageData = this.initPathToManage();
  pathToDelete: JourneyPath;
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

      await this.initJourneyPaths(this.courseID);
      await this.initSkills(this.courseID);

      this.loading.page = false;
    });
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async initJourneyPaths(courseID: number) {
    this.journeyPaths = await this.api.getJourneyPaths(courseID).toPromise();
    this.buildPathsTable();
  }

  async initSkills(courseID: number) {
    this.skills = await this.api.getSkillsOfCourse(courseID).toPromise();
    this.skillsAvailable = this.skills.map((skill) => {
      return {
        value: skill.id.toString(),
        text: skill.name,
        html:
          '<div class="!text-left !text-start !justify-start">' +
            '<div class="flex items-center space-x-3">' +
              '<div class="avatar">' +
                '<div class="mask mask-circle w-9 h-9 !flex !items-center !justify-center bg-base-content bg-opacity-30" style="background-color: ' + skill.color + '">' +
                  '<span class="text-base-100 text-base">' + skill.name[0] + '</span>' +
                '</div>' +
              '</div>' +
              '<div class="flex flex-col">' +
                '<div class="prose text-sm">' +
                  '<h4>' + skill.name + '</h4>' +
                '</div>' +
                '<div class="prose text-sm opacity-60">' +
                  '<div>' + skill.reward + ' XP </div>' +
                '</div>' +
              '</div>' +
            '</div>' +
          '</div>'
      }
    })
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
        {type: TableDataType.CUSTOM, content: {html: '<div class="!text-left !text-start !justify-start">' +
              '<div class="flex items-center space-x-3">' +
              '<div class="avatar">' +
              '<div class="mask mask-circle w-9 h-9 !flex !items-center !justify-center bg-base-content bg-opacity-30" style="background-color: ' + path.color + '">' +
              '<span class="text-base-100">' + path.name[0] + '</span>' +
              '</div>' +
              '</div>' +
              '<div class="prose text-sm">' +
              '<h4>' + path.name + '</h4>' +
              '</div>' +
              '</div>' +
              '</div>', searchBy: path.name}},
        {type: TableDataType.TEXT, content: {text: this.stringifySkills(path.skills)}},
        {type: TableDataType.NUMBER, content: {value: this.getTotalSkillsXP(path.skills)}},
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
    const pathToActOn = this.journeyPaths[row];

    if (action === 'edit') {
      this.pathMode = 'edit';
      this.pathToManage = this.initPathToManage(pathToActOn);
      ModalService.openModal('path-manage');
    }
    else if (action === 'delete') {
      this.pathToDelete = pathToActOn;
      ModalService.openModal('path-delete-verification');
    }
    else if (action === 'value changed') {
      this.loading.paths = true;

      pathToActOn.isActive = value;
      await this.api.editJourneyPath(this.courseID, clearEmptyValues(pathToActOn)).toPromise();

      this.loading.paths = false;
    }
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
    if (this.fPath.valid) {
      this.loading.action = true;

      const pathToEdit = this.journeyPaths.find(el => el.id === this.pathToManage.id);
      pathToEdit.name = this.pathToManage.name;
      pathToEdit.color = this.pathToManage.color;
      pathToEdit.skills = this.pathToManage.skills;

      await this.api.editJourneyPath(this.courseID, clearEmptyValues(pathToEdit)).toPromise();
      this.journeyPaths = await this.api.getJourneyPaths(this.courseID).toPromise();
      this.buildPathsTable();

      ModalService.closeModal('path-manage');
      AlertService.showAlert(AlertType.SUCCESS, 'Journey Path edited: ' + this.pathToManage.name);
      this.resetPathToManage();

      this.loading.action = false;

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async deletePath(path: JourneyPath): Promise<void> {
    this.loading.action = true;

    await this.api.deleteJourneyPath(this.courseID, path.id).toPromise();
    this.journeyPaths = await this.api.getJourneyPaths(this.courseID).toPromise();
    this.buildPathsTable();

    this.loading.action = false;
    ModalService.closeModal('path-delete-verification');
    AlertService.showAlert(AlertType.SUCCESS, 'Path \'' + path.name + '\' deleted');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initPathToManage(path?: JourneyPath): PathManageData {
    const pathData: PathManageData = {
      name: path?.name ?? null,
      color: path?.color ?? null,
      skills: path?.skills ? _.cloneDeep(path.skills) : []
    };
    if (path) pathData.id = path.id;
    return pathData;
  }

  resetPathToManage() {
    this.pathMode = null;
    this.pathToManage = this.initPathToManage();
    this.fPath.resetForm();
  }

  stringifySkills(skills: Skill[]) {
    const string = skills.map(skill => skill.name).join(" -> ");
    return string.length > 80 ? string.substring(0, 80) + "(...)" : string
  }

  getTotalSkillsXP(skills: Skill[]) {
    return skills.reduce((prev, cur) => prev + cur.reward, 0);
  }

  addSkill() {
    this.pathToManage.skills.push(this.skills.find((skill) => skill.id.toString() === this.skillToAdd));
    this.skillToAdd = null;
  }

  editSkill(index: number, newSkillId: string) {
    this.pathToManage.skills.splice(index, 1, this.skills.find((skill) => skill.id.toString() === newSkillId));
  }

  deleteSkill(index: number) {
    this.pathToManage.skills.splice(index, 1);
  }

  drop(event: CdkDragDrop<string[]>){
    moveItemInArray(this.pathToManage.skills, event.previousIndex, event.currentIndex);
  }

}

export interface PathManageData {
  id?: number,
  name: string,
  color: string,
  skills: Skill[]
}
