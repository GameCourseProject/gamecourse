import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {Component, OnInit, ViewChild} from "@angular/core";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../_domain/courses/course";
import {User} from "src/app/_domain/users/user";
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";
import {EditableGameElement} from "../../../../../../_domain/adaptation/EditableGameElement";
import {ModalService} from "../../../../../../_services/modal.service";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {Observable} from "rxjs";

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
  gameElementToManage: GameElementManageData = this.initEditableGameElement();
  mode: 'configure';
  availableGameElements: EditableGameElement[];

  @ViewChild('c', {static: false}) c: NgForm;       // configure form

  /** -- NON-ADMIN VARIABLES -- **/
  selectedGameElement: string;
  gameElementChildren: string[];
  previousPreference: string;

  availableGameElementsSelect: { value: string, text: string }[] = [];

  activeButton = null;
  option: string;
  message: string;

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
    // ADMIN
    this.availableGameElements = await this.api.getEditableGameElements(courseID).toPromise();

    // NON-ADMIN
    const gameElements = this.availableGameElements;
    for (let i = 0; i < gameElements.length; i++){
      this.availableGameElementsSelect.push({value: gameElements[i].module, text: gameElements[i].module});
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------ Table (ADMIN) ------------------ ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Game Element', align: 'middle'},
    {label: 'Editable', align: 'middle'},
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
        {type: TableDataType.TEXT, content: {text: gameElement.module}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isEditable', toggleValue: gameElement.isEditable}},
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

    if (action === 'value changed game element'){
      if (col === 1) this.toggleActive(gameElementToActOn);

    } else if (action === 'Configure') {
      this.mode = 'configure';
      this.gameElementToManage = this.initEditableGameElement(gameElementToActOn);
      ModalService.openModal('manage-game-element');
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/


  /** -- ADMIN ACTIONS -- **/
  async toggleActive(gameElement: EditableGameElement){
    this.loading.action = true;

    gameElement.isEditable = !gameElement.isEditable;
    await this.api.setEditableGameElementEditable(this.course.id, gameElement.module, gameElement.isEditable).toPromise();

    this.loading.action = false;
  }

  async updateGameElement(){
    if (this.c.valid){
      this.loading.action = true;

      const gameElementConfig = await this.api.updateEditableGameElement(clearEmptyValues(this.gameElementToManage)).toPromise();
      const index = this.availableGameElements.findIndex(gameElement => gameElement.id === gameElementConfig.id);
      this.availableGameElements.removeAtIndex(index);
      this.availableGameElements.push(gameElementConfig);

      this.buildTable();
      this.loading.action = false;
      ModalService.closeModal('manage-game-element');
      AlertService.showAlert(AlertType.SUCCESS, 'Game element \'' + gameElementConfig.module + '\' configured');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  /** -- NON-ADMIN ACTIONS -- **/
  doAction(gameElement: string) {
    this.option = gameElement;
  }

  async preparePreferences(gameElement: string){
    this.activeButton = null;

    if (gameElement !== "undefined"){
      await this.getChildren(gameElement);
      await this.getPreviousPreference(gameElement);
    }
    else{
      this.message="undefined";
      this.previousPreference = null;
      this.gameElementChildren = null;
    }
  }

  async getPreviousPreference(gameElement: string) {
    const preference = await this.api.getPreviousPreference(this.course.id, this.user.id, gameElement).toPromise();

    // Checks if preference has multiple words
    // If preference exists then value = "B001" | "B002" ... and so on (always one word!)
    // If preference doesn't exist then value = "No previous preference." (multiple words)
    if (preference.indexOf(' ') >= 0)
    {
      this.previousPreference = "none";
      this.message = preference;

    } else{
      this.message = null;
      this.previousPreference = preference;
    }
  }

  async getChildren(gameElement: string) {
    this.gameElementChildren =  await this.api.getChildrenGameElement(this.course.id, gameElement).toPromise();
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

  resetGameElementManage(){
    this.gameElementToManage = this.initEditableGameElement();
    this.c.resetForm();
  }

  initEditableGameElement(editableGameElement? : EditableGameElement): GameElementManageData{
    const gameElementData: GameElementManageData = {
      course: editableGameElement?.course ?? null,
      module: editableGameElement?.module ?? null,
      isEditable: editableGameElement?.isEditable ?? false,
      nDays: editableGameElement?.nDays ?? null,
      notify: editableGameElement?.notify ?? false
    };
    if (editableGameElement){
      gameElementData.id = editableGameElement.id;
    }
    return gameElementData;
  }
}

export interface GameElementManageData {
  id?: number,
  course?: number,
  module?: string,
  isEditable?: boolean,
  nDays?: number
  notify?: boolean
}
