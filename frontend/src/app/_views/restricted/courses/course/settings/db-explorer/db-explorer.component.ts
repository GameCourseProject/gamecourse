import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {Component, OnInit, ViewChild} from "@angular/core";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../_domain/courses/course";
import {Action} from "src/app/_domain/modules/config/Action";
import {TableDataType} from "src/app/_components/tables/table-data/table-data.component";
import {dateFromDatabase} from "src/app/_utils/misc/misc";
import {ModalService} from "../../../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {NgForm} from "@angular/forms";
import * as moment from "moment/moment";

@Component({
  selector: 'app-db-explorer',
  templateUrl: './db-explorer.component.html'
})
export class DBExplorerComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    table: true
  }

  course: Course;
  awardsEnabled: boolean;

  participations : TablesData = {
    selected: true,
    dbData: null,
    headers: [
      {label: 'Id', align: 'middle'},
      {label: 'User', align: 'middle'},
      {label: 'Source', align: 'middle'},
      {label: 'Description', align: 'middle'},
      {label: 'Type', align: 'middle'},
      {label: 'Post', align: 'middle'},
      {label: 'Date', align: 'middle'},
      {label: 'Rating', align: 'middle'},
      {label: 'Evaluator', align: 'middle'},
      {label: 'Actions', align: 'middle'},
    ],
    table: []
  }
  awards : TablesData = {
    selected: false,
    dbData: null,
    headers: [
      {label: 'Id', align: 'middle'},
      {label: 'User', align: 'middle'},
      {label: 'Description', align: 'middle'},
      {label: 'Type', align: 'middle'},
      {label: 'Module Instance', align: 'middle'},
      {label: 'Reward', align: 'middle'},
      {label: 'Date', align: 'middle'},
      {label: 'Actions', align: 'middle'},
    ],
    table: []
  }

  participationToManage: EditableParticipationData;
  awardToManage: EditableAwardData;

  @ViewChild('fParticipation', { static: false }) fParticipation: NgForm;
  @ViewChild('fAward', { static: false }) fAward: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getParticipations(courseID);
      await this.getAwards(courseID);
      await this.isAwardsEnabled(courseID);

      await this.buildTables();
      this.loading.page = false;
    });
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

  async getParticipations(courseID: number): Promise<void> {
    this.participations.dbData = await this.api.getParticipations(courseID).toPromise();
  }

  async getAwards(courseID: number): Promise<void> {
    this.awards.dbData = await this.api.getAwards(courseID).toPromise();
  }

  async isAwardsEnabled(courseID: number) {
    this.awardsEnabled = (await this.api.getCourseModuleById(courseID, ApiHttpService.AWARDS).toPromise()).enabled;
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Top Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async doTopAction(event: string) {
    if (event === 'Participations') {
      this.awards.selected = false;
      this.participations.selected = true;
    }
    else if (event === 'Awards') {
      this.awards.selected = true;
      this.participations.selected = false;
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------- Tables ------------------ ***/
  /*** --------------------------------------------- ***/

  tableOptions = {
    order: [[0, 'desc']],
  }

  async buildTables() {
    this.loading.table = true;

    this.participations.table = [];
    this.participations.dbData.forEach(entry => {
      this.participations.table.push([
        {type: TableDataType.TEXT, content: {text: entry.id?.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.user}},
        {type: TableDataType.TEXT, content: {text: entry.source}},
        {type: TableDataType.TEXT, content: {text: entry.description}},
        {type: TableDataType.TEXT, content: {text: entry.type}},
        {type: TableDataType.TEXT, content: {text: entry.post}},
        {type: TableDataType.DATETIME, content: {datetime: dateFromDatabase(entry.date), datetimeFormat: 'DD/MM/YYYY HH:mm'}},
        {type: TableDataType.TEXT, content: {text: entry.rating?.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.evaluatorName}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.EDIT, Action.DELETE]}},
      ]);
      entry.date = dateFromDatabase(entry.date)?.format("YYYY-MM-DDTHH:mm"); // prepare for working in datetime picker
    });

    this.awards.table = [];
    this.awards.dbData.forEach(entry => {
      this.awards.table.push([
        {type: TableDataType.TEXT, content: {text: entry.id?.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.user}},
        {type: TableDataType.TEXT, content: {text: entry.description}},
        {type: TableDataType.TEXT, content: {text: entry.type}},
        {type: TableDataType.TEXT, content: {text: entry.moduleInstance?.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.reward?.toString()}},
        {type: TableDataType.DATETIME, content: {datetime: dateFromDatabase(entry.date), datetimeFormat: 'DD/MM/YYYY HH:mm'}},
        {type: TableDataType.ACTIONS, content: {actions: [Action.EDIT, Action.DELETE]}},
      ]);
      entry.date = dateFromDatabase(entry.date)?.format("YYYY-MM-DDTHH:mm"); // prepare for working in datetime picker
    });

    this.loading.table = false;
  }

  doActionOnTable(table: 'participations' | 'awards', action: string, row: number, col: number, value?: any){
    if (table === 'participations' && action === Action.EDIT) {
      this.participationToManage = this.participations.dbData[row];
      ModalService.openModal('edit-participation');
    }
    else if (table === 'participations' && action === Action.DELETE) {
      this.participationToManage = this.participations.dbData[row];
      ModalService.openModal('confirm-participation-modal');
    }
    else if (table === 'awards' && action === Action.EDIT) {
      this.awardToManage = this.awards.dbData[row];
      ModalService.openModal('edit-award');
    }
    else if (table === 'awards' && action === Action.DELETE) {
      this.awardToManage = this.awards.dbData[row];
      ModalService.openModal('confirm-award-modal');
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async deleteParticipation() {
    this.loading.action = true;
    await this.api.deleteParticipation(this.course.id, this.participationToManage.id).toPromise();

    // Refresh
    await this.getParticipations(this.course.id);
    await this.buildTables();

    this.participationToManage = null;
    this.loading.action = false;
    ModalService.closeModal('confirm-participation-modal');
    AlertService.showAlert(AlertType.SUCCESS, "Participation deleted successfully")
  }

  async deleteAward() {
    this.loading.action = true;
    await this.api.deleteAward(this.course.id, this.awardToManage.id).toPromise();

    // Refresh
    await this.getAwards(this.course.id);
    await this.buildTables();

    this.awardToManage = null;
    this.loading.action = false;
    ModalService.closeModal('confirm-award-modal');
    AlertService.showAlert(AlertType.SUCCESS, "Award deleted successfully")
  }

  async editParticipation() {
    if (!this.fParticipation.valid) {
      AlertService.showAlert(AlertType.ERROR, 'Invalid form');
    }
    else {
      this.loading.action = true;
      await this.api.editParticipation(this.course.id, this.participationToManage).toPromise();

      // Refresh
      await this.getParticipations(this.course.id);
      await this.buildTables();

      this.participationToManage = null;
      this.loading.action = false;
      ModalService.closeModal('edit-participation');
      AlertService.showAlert(AlertType.SUCCESS, "Participation edited successfully")
    }
  }

  async editAward() {
    if (!this.fAward.valid) {
      AlertService.showAlert(AlertType.ERROR, 'Invalid form');
    }
    else {
      this.loading.action = true;
      await this.api.editAward(this.course.id, this.awardToManage).toPromise();

      // Refresh
      await this.getAwards(this.course.id);
      await this.buildTables();

      this.awardToManage = null;
      this.loading.action = false;
      ModalService.closeModal('edit-award');
      AlertService.showAlert(AlertType.SUCCESS, "Award updated successfully")
    }
  }

}

interface TablesData {
  selected: boolean,
  dbData: any,
  headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
  table: { type: TableDataType, content: any }[][],
}

export interface EditableParticipationData {
  id: number,
  user: string,
  source: string,
  description: string,
  type: string,
  post: string,
  date: string,
  rating: number,
  evaluator: number,
  evaluatorName: string,
}

export interface EditableAwardData {
  id: number,
  user: string,
  description: string,
  type: string,
  moduleInstance: number,
  reward: number,
  date: string,
}
