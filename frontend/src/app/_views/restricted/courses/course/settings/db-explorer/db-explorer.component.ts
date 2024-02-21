import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {Component, OnInit} from "@angular/core";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../_domain/courses/course";
import { Action } from "src/app/_domain/modules/config/Action";
import { TableDataType } from "src/app/_components/tables/table-data/table-data.component";
import { dateFromDatabase } from "src/app/_utils/misc/misc";

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
  refreshing: boolean = true;

  course: Course;
  awardsEnabled: boolean;

  participations : TablesData = {
    selected: true,
    dbData: null,
    headers: [
      {label: 'Id', align: 'middle'},
      {label: 'User', align: 'middle'},
      {label: 'Course', align: 'middle'},
      {label: 'Source', align: 'middle'},
      {label: 'Description', align: 'middle'},
      {label: 'Type', align: 'middle'},
      {label: 'Post', align: 'middle'},
      {label: 'Date', align: 'middle'},
      {label: 'Rating', align: 'middle'},
      {label: 'Evaluator', align: 'middle'},
    ],
    table: []
  }
  awards : TablesData = {
    selected: false,
    dbData: null,
    headers: [
      {label: 'Id', align: 'middle'},
      {label: 'User', align: 'middle'},
      {label: 'Course', align: 'middle'},
      {label: 'Description', align: 'middle'},
      {label: 'Type', align: 'middle'},
      {label: 'Reward', align: 'middle'},
      {label: 'Date', align: 'middle'},
    ],
    table: []
  }

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
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  buildTables(): void {
    this.loading.table = true;

    this.participations.dbData.forEach(entry => {
      this.participations.table.push([
        {type: TableDataType.TEXT, content: {text: entry.id.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.user.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.course.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.source}},
        {type: TableDataType.TEXT, content: {text: entry.description}},
        {type: TableDataType.TEXT, content: {text: entry.type}},
        {type: TableDataType.TEXT, content: {text: entry.post}},
        {type: TableDataType.DATETIME, content: {datetime: dateFromDatabase(entry.date), datetimeFormat: 'DD/MM/YYYY HH:mm'}},
        {type: TableDataType.TEXT, content: {text: entry.rating?.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.evaluator?.toString()}}
      ]);
    });

    this.awards.dbData.forEach(entry => {
      this.awards.table.push([
        {type: TableDataType.TEXT, content: {text: entry.id.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.user.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.course.toString()}},
        {type: TableDataType.TEXT, content: {text: entry.description}},
        {type: TableDataType.TEXT, content: {text: entry.type}},
        {type: TableDataType.TEXT, content: {text: entry.reward.toString()}},
        {type: TableDataType.DATETIME, content: {datetime: dateFromDatabase(entry.date), datetimeFormat: 'DD/MM/YYYY HH:mm'}},
      ]);
    });

    this.loading.table = false;
  }
}

interface TablesData {
  selected: boolean,
  dbData: any,
  headers: {label: string, align?: 'left' | 'middle' | 'right'}[],
  table: { type: TableDataType, content: any }[][],
}