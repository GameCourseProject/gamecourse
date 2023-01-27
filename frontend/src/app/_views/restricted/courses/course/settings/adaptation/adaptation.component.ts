import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {Component, OnInit} from "@angular/core";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../_domain/courses/course";
import {User} from "src/app/_domain/users/user";
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";

@Component({
  selector: 'app-adaptation',
  templateUrl: './adaptation.component.html'
})
export class AdaptationComponent implements OnInit {

  /** -- COMMON VARIABLES -- **/
  loading = {
    page: true,
    action: false,
    table: false
  }

  course: Course;
  user: User;

  /** -- ADMIN VARIABLES -- **/
  mode: 'configure';

  /** -- NON-ADMIN VARIABLES -- **/
  selectedGameElement: string;
  gameElementChildren: string[];
  previousPreference: string;

  availableGameElements: { value: string, text: string }[] = [];

  activeButton;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getUser();

      await this.getGameElements(courseID);

      this.loading.page = false;

      if (this.user.isAdmin){
        this.buildTable();
      }

    });
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
  }

  async getGameElements(courseID: number): Promise<void> {
    const gameNames = await this.api.getAdaptationParentRoles(courseID).toPromise();
    console.log(gameNames);

    let elements = Object.values(gameNames);
    for (let i = 0; i < elements.length; i++){
      this.availableGameElements.push({value: elements[i], text: elements[i]});
    }
    console.log(this.availableGameElements);
  }

  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Game Element', align: 'middle'},
    {label: 'Active', align: 'middle'},
    {label: 'Actions'}
  ];
  data: {type: TableDataType, content: any}[][];
  tableOptions = {
    order: [0, 'asc'],
    columnDefs: [ // not sure
      { type: 'natural', targets: [0] },
      { orderable: false, targets: [1] }
    ]
  }

  buildTable(): void {
    this.loading.table = true;

    const table: {type: TableDataType, content: any}[][] = [];
    this.availableGameElements.forEach(gameElement => {
      table.push([
        {type: TableDataType.TEXT, content: {text: gameElement.text}},
        {type: TableDataType.PILL, content: true},
        {type: TableDataType.ACTIONS, content: {actions: [
          {action: 'Configure', icon: 'tabler-settings', color: 'primary'}]}
        }
      ]);
    });

    this.data = table;
    this.loading.table = false;
  }

  doActionOnTable(action: string, row: number, col: number, value?: any): void{
    const gameElementToActOn = this.availableGameElements[row];

    if (action === 'value changed'){
      //if (col === 1) this.toggleActive(gameElementToActOn);

    } else if (action === 'Configure') {
      this.mode = 'configure';
      //this.
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/
/*
  async toggleActive(gameElement: AdaptationGameElement){
    this.loading.action = true;

    gameElement.isActive = !gameElement.isActive;
    await this.api.setAdaptationGameElementActive()
  }
  */
  doAction(gameElement: string) {
    // TODO
  }

  async getPreviousPreference(): Promise<void> {
    // TODO
    let date = new Date(); // NOT SURE
    this.previousPreference = await this.api.getPreviousPreference(this.course.id, this.user.studentNumber, date).toPromise();
  }

  getChildren(gameElement: string) {
    // TODO
    this.gameElementChildren = ["a", "b", "c"]; // await this.api....
    return this.gameElementChildren;
  }

  discard() {
    // TODO
  }

  async save(): Promise<void> {
    // TODO
    const newPreference = this.gameElementChildren[this.activeButton];

    await this.api.updatePreference(this.course.id, this.user.studentNumber, this.previousPreference, newPreference).toPromise();
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getButtonColor(index: number){
    if (this.activeButton === index){ return "active";}
    else return "primary";
  }

  setButtonActive(index : number){
    this.activeButton = index;
  }

}
