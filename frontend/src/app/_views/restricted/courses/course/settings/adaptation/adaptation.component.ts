import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {Component, OnInit, ViewChild} from "@angular/core";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../_domain/courses/course";
import {User} from "src/app/_domain/users/user";
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";
import {GameElement} from "../../../../../../_domain/adaptation/GameElement";
import {ModalService} from "../../../../../../_services/modal.service";
import {NgForm} from "@angular/forms";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";

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
  gameElementToManage: GameElementManageData = this.initGameElementToManage();
  availableGameElements: GameElement[];
  adminMode: 'questionnaire statistics' | 'activate' | 'deactivate';

  @ViewChild('f', {static: false}) f: NgForm;       // (de)activation form

  /** -- NON-ADMIN VARIABLES -- **/
  selectedGameElement: string;
  gameElementChildren: string[];
  previousPreference: string;

  availableGameElementsSelect: { value: string, text: string }[] = [];

  activeButton = null;
  option: string;
  message: string;
  questionnaire: boolean;
  mode: 'questionnaire';

  questionnaireToManage: QuestionnaireManageData;

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

      await this.buildTable();

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
    if (this.user.isAdmin){
      this.availableGameElements = await this.api.getGameElements(courseID).toPromise();
    }

    // NON-ADMIN
    //else { //FIXME:DEBUG ONLY
      // all available game elements
      this.availableGameElements = await this.api.getGameElements(courseID, true).toPromise();

      for (let i = 0; i < this.availableGameElements.length; i++){
        this.availableGameElementsSelect.push({value: this.availableGameElements[i].module, text: this.availableGameElements[i].module});
      }
    //}
  }

  /*** --------------------------------------------- ***/
  /*** ----------------- Table  -------------------- ***/
  /*** --------------------------------------------- ***/

  data: {type: TableDataType, content: any}[][];

  /* ADMIN */
  headersAdmin: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Game Element', align: 'middle'},
    {label: 'Active', align: 'middle'},
    {label: 'Actions'}
  ];
  tableOptionsAdmin = {
    order: [0, 'asc'],
    columnDefs: [
      { type: 'natural', targets: [0] },
      { orderable: false, targets: [1] }
    ]
  }

  /* NON-ADMIN */
  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [
    {label: 'Game Element', align: 'middle'},
    {label: 'Questionnaire', align: 'middle'}
  ];
  tableOptions = {
    order: [0, 'asc'],
    columnDefs: [
      { type: 'natural', targets: [0] },
    ]
  }

  async buildTable() {
    this.loading.table = true;

    const table: {type: TableDataType, content: any}[][] = [];

    if (!this.user.isAdmin){  //FIXME: this.user.isAdmin
      this.availableGameElements.forEach(gameElement => {
        table.push([
          {type: TableDataType.TEXT, content: {text: gameElement.module}},
          {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: gameElement.isActive}},
          {type: TableDataType.ACTIONS, content: {actions: [
                {action: 'Questionnaire statistics', icon: 'tabler-chart-pie', color: 'primary'},
                {action: 'Export questionnaire answers', icon: 'jam-upload', color: 'primary'}]}
          }
        ]);
      });
    } else{
      for (const gameElement of this.availableGameElements) {
        const response = await this.api.isQuestionnaireAnswered(this.course.id, this.user.id, gameElement.id).toPromise();
        table.push([
          {type: TableDataType.TEXT, content: {text: gameElement.module}},
          {type: TableDataType.BUTTON, content: {
            buttonText: response ? 'Answered' : 'Answer',
            buttonColor: response ? '' : 'success',
            buttonStyle: 'outline',
            buttonDisable: response}},
        ]);
      }
    }

    this.data = table;
    this.loading.table = false;
  }

  async doActionOnTable(action: string, row: number, col: number, value?: any) {
    const gameElementToActOn = this.availableGameElements[row];

    if (action === 'value changed game element' && col === 1){
      if (gameElementToActOn.isActive) {
        this.adminMode = 'deactivate';
      } else this.adminMode = 'activate';

      this.gameElementToManage = this.initGameElementToManage(gameElementToActOn);
      ModalService.openModal('manage-game-element');

    } else if (action === 'answer questionnaire') {
      this.mode = 'questionnaire';
      this.questionnaireToManage = this.initQuestionnaireToManage();
      this.questionnaireToManage.element = gameElementToActOn.module;
      console.log(this.questionnaireToManage);
      ModalService.openModal('questionnaire');
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/


  /** -- ADMIN ACTIONS -- **/
  async toggleActive(){
    this.loading.action = true;

    this.gameElementToManage.isActive = !this.gameElementToManage.isActive;
    const gameElement = await this.api.setGameElementActive(this.course.id, this.gameElementToManage.module,
      this.gameElementToManage.isActive, this.gameElementToManage.notify).toPromise();

    const index = this.availableGameElements.findIndex(gameElement => gameElement.id === gameElement.id);
    this.availableGameElements.removeAtIndex(index);
    this.availableGameElements.push(gameElement);

    await this.buildTable();
    this.loading.action = false;
    ModalService.closeModal('manage-game-element');
    AlertService.showAlert(AlertType.SUCCESS, 'Game element \'' + gameElement.module + '\'' + this.adminMode + 'd');

  }

  /** -- NON-ADMIN ACTIONS -- **/
  async doAction(action:string, gameElement?: string) {
    if (action === 'set option'){
      this.option = gameElement;
    } else if (action === 'submit questionnaire'){
      await this.api.submitGameElementQuestionnaire(clearEmptyValues(this.questionnaireToManage)).toPromise();
    }

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
      this.option = preference;
      //this.activeButton = this.gameElementChildren.findIndex(el => el === this.previousPreference);
    }
  }

  async getChildren(gameElement: string) {
    this.gameElementChildren =  await this.api.getChildrenGameElement(this.course.id, gameElement).toPromise();
  }

  async updatePreference(): Promise<void> {
    const newPreference = this.gameElementChildren[this.activeButton];

    if (newPreference && newPreference !== this.previousPreference){
      let date = new Date();
      if (this.previousPreference === "none") { this.previousPreference = null; }

      await this.api.updateUserPreference(this.course.id, this.user.id,
        this.selectedGameElement, this.previousPreference, newPreference, date).toPromise();

      this.previousPreference = newPreference;
      this.message = null;
      AlertService.showAlert(AlertType.SUCCESS, 'New preference saved');

    } else AlertService.showAlert(AlertType.ERROR, 'Please select a new preference to save');
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

  initGameElementToManage(gameElement? : GameElement): GameElementManageData{
    const gameElementData: GameElementManageData = {
      course: gameElement?.course ?? null,
      module: gameElement?.module ?? null,
      isActive: gameElement?.isActive ?? false,
      notify: gameElement?.notify ?? false
    };
    if (gameElement){
      gameElementData.id = gameElement.id;
    }
    return gameElementData;
  }

  resetGameElementManage(){
    this.gameElementToManage = this.initGameElementToManage();
    this.f.resetForm();
  }

  initQuestionnaireToManage(): QuestionnaireManageData{
    const questionnaireData: QuestionnaireManageData = {
      course: this.course.id,
      user: this.user.id,
      q1: null,
      q2: null,
      q3: null,
      element: ""
    };
    return questionnaireData;
  }

  resetQuestionnaireManage(){
    this.questionnaireToManage = this.initQuestionnaireToManage();
    this.f.resetForm();
  }
}

export interface GameElementManageData {
  id?: number,
  course?: number,
  module?: string,
  isActive?: boolean,
  notify?: boolean
}

export interface QuestionnaireManageData{
  course?: number,
  user?: number,
  q1?: boolean,
  q2?: string,
  q3?: number,
  element?: string
}
