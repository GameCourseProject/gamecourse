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
import {ThemingService} from "../../../../../../_services/theming/theming.service";

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
  @ViewChild('f', {static: false}) f: NgForm;       // (de)activation form (admin)

  gameElementToActOn: GameElement;
  availableGameElements: GameElement[];

  /** -- ADMIN VARIABLES -- **/
  gameElementToManage: GameElementManageData = this.initGameElementToManage();
  adminMode: 'questionnaire statistics' | 'activate' | 'deactivate';

  /** -- NON-ADMIN VARIABLES -- **/
  selectedGameElement: string;
  gameElementChildren: {[gameElement: string]: {version: string}}[];
  previousPreference: string;

  availableGameElementsSelect: { value: string, text: string }[] = [];

  activeButton: string = null;
  option: string;
  //message: string;

  isQuestionnaire: boolean = true;
  mode: 'questionnaire';
  questionnaires: QuestionnaireManageData[] = [];


  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private themeService: ThemingService
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
    if (!this.user.isAdmin){
      this.availableGameElements = await this.api.getGameElements(courseID).toPromise();
    }

    // NON-ADMIN
    else {
      // all available game elements
      this.availableGameElements = await this.api.getGameElements(courseID, true).toPromise();

      for (let i = 0; i < this.availableGameElements.length; i++){
        let questionnaireData: QuestionnaireManageData = {
          course: courseID,
          user: this.user.id,
          q1: null,
          q2: null,
          q3: null,
          element: this.availableGameElements[i].module,
          isAnswered: await this.api.isQuestionnaireAnswered(this.course.id, this.user.id, this.availableGameElements[i].id).toPromise()
        }
        this.questionnaires.push(questionnaireData);
        this.availableGameElementsSelect.push({value: this.availableGameElements[i].module, text: this.availableGameElements[i].module});
      }
      await this.doAction('prepare non-admin page');
    }
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

    if (!this.user.isAdmin){  //FIXME: DEBUG ONLY
      this.availableGameElements.forEach(gameElement => {

        let isActive = gameElement.isActive;
        if (isActive.toString() === '0' || isActive.toString() === '1'){
          isActive = (gameElement.isActive).toString() === '1';
        }
        table.push([
          {type: TableDataType.TEXT, content: {text: gameElement.module}},
          {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: isActive}},
          {type: TableDataType.ACTIONS, content: {actions: [
                {action: 'Questionnaire statistics', icon: 'tabler-chart-pie', color: 'primary'},
                {action: 'Export questionnaire answers', icon: 'jam-upload', color: 'primary'}]}
          }
        ]);
      });
    } else{
      for (const questionnaire of this.questionnaires) {
        table.push([
          {type: TableDataType.TEXT, content: {text: questionnaire.element}},
          {type: TableDataType.BUTTON, content: {
            buttonText: questionnaire.isAnswered ? 'Answered' : 'Answer',
            buttonColor: questionnaire.isAnswered ? '' : 'success',
            buttonStyle: 'outline',
            buttonDisable: questionnaire.isAnswered}},
        ]);
      }
    }

    this.data = table;
    this.loading.table = false;
  }

  async doActionOnTable(action: string, row: number, col: number, value?: any) {
    this.gameElementToActOn = this.availableGameElements[row];

    if (action === 'value changed game element' && col === 1){
      if ((this.gameElementToActOn.isActive).toString() !== '0' && this.gameElementToActOn.isActive !== false) {
        this.adminMode = 'deactivate';
      } else this.adminMode = 'activate';

      this.gameElementToManage = this.initGameElementToManage(this.gameElementToActOn);
      ModalService.openModal('manage-game-element');

    } else if (action === 'answer questionnaire') {
      ModalService.openModal('questionnaire');
      this.mode = 'questionnaire';
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/


  /** -- ADMIN ACTIONS -- **/
  async toggleActive(){
    this.loading.action = true;

    if ((this.gameElementToManage.isActive).toString() === 'true' || (this.gameElementToManage.isActive).toString() === 'false'){
      this.gameElementToManage.isActive = !this.gameElementToManage.isActive;
    }
    else if ((this.gameElementToManage.isActive).toString() === '0' || (this.gameElementToManage.isActive).toString() === '1'){
      this.gameElementToManage.isActive = (this.gameElementToManage.isActive).toString() !== '1';
    }

    const gameElement = await this.api.setGameElementActive(this.course.id, this.gameElementToManage.module,
      this.gameElementToManage.isActive, this.gameElementToManage.notify).toPromise();

    const index = this.availableGameElements.findIndex(element => (element.id).toString() === (gameElement.id).toString());
    this.availableGameElements.removeAtIndex(index);
    this.availableGameElements.push(gameElement);

    await this.buildTable();
    this.loading.action = false;
    this.resetGameElementManage();
    ModalService.closeModal('manage-game-element');
    AlertService.showAlert(AlertType.SUCCESS, 'Game element \'' + gameElement.module + '\'' + this.adminMode + 'd');

  }

  /** -- NON-ADMIN ACTIONS -- **/
  async doAction(action:string, gameElement?: string) {
    if (action === 'set option'){
      this.option = gameElement;

    } else if (action === 'prepare non-admin page') {
      if (this.questionnaires.length === 0){
        return;
      }

      this.mode = null;
      await this.buildTable();

      let filteredQuestionnaires = this.questionnaires.filter(function (item) { return !item.isAnswered });
      if (filteredQuestionnaires.length === 0) {
        // update user roles (Translate profiling roles to adaptation roles)
        // And prepares preferences for future use
        await this.api.profilingToAdaptationRole(this.course.id, this.user.id).toPromise();

        ModalService.openModal('all-questionnaires-submitted');
      }

    } else if (action === 'show game elements'){
      ModalService.closeModal('all-questionnaires-submitted');
      this.isQuestionnaire = false;
    }
  }

  async preparePreferences(gameElement: string){
    this.activeButton = null;

    if (gameElement !== "undefined"){
      await this.getChildren(gameElement);
      await this.getPreviousPreference(gameElement);
    }
    else{
      //this.message="undefined";
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
      this.option = Object.keys(this.gameElementChildren)[0];
      this.activeButton = gameElement;
      //this.message = preference;

    } else{
      //this.message = null;
      this.previousPreference = preference;
      this.option = preference;
      //this.activeButton = this.gameElementChildren.findIndex(el => el === this.previousPreference);
    }
  }

  async getChildren(gameElement: string) {
    this.gameElementChildren =  await this.api.getChildrenGameElement(this.course.id, gameElement).toPromise();
  }

  async updatePreference(): Promise<void> {
    const newPreference = this.activeButton;

    if (newPreference && newPreference !== this.previousPreference){
      let date = new Date();
      if (this.previousPreference === "none") { this.previousPreference = null; }

      await this.api.updateUserPreference(this.course.id, this.user.id,
        this.selectedGameElement, this.previousPreference, newPreference, date).toPromise();


      console.log(newPreference);
      console.log(this.gameElementChildren);
      this.previousPreference = newPreference;
      //this.message = null;
      AlertService.showAlert(AlertType.SUCCESS, 'New preference saved');

    } else AlertService.showAlert(AlertType.ERROR, 'Please select a new preference to save');
  }

  discardPreference(){
    if (this.activeButton !== null){
      this.activeButton= null;
      AlertService.showAlert(AlertType.INFO, 'Preferences discarded');
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getTheme(){
    return this.themeService.getTheme();
  }

  getButtonColor(gameElement: string){
    let color;
    if (this.activeButton === gameElement){
      color = "active";
    }
    else if (this.activeButton !== gameElement){
      color = "primary";

    } if (this.previousPreference === gameElement){
      color = color + " border-solid border-4 border-success"
    }

    return color;
  }

  setButtonActive(gameElement :string){
    this.activeButton = gameElement;
  }

  initGameElementToManage(gameElement? : GameElement): GameElementManageData{
    const gameElementData: GameElementManageData = {
      course: gameElement?.course ?? null,
      module: gameElement?.module ?? null,
      isActive: gameElement?.isActive ?? false,
      notify: false
    };
    if (gameElement){
      gameElementData.id = gameElement.id;
    }
    return gameElementData;
  }

  resetGameElementManage(){
    this.gameElementToManage = this.initGameElementToManage();
    this.gameElementToActOn = null;
    this.f.resetForm();
  }

  getVersions(): string[]{
    return Object.keys(this.gameElementChildren);
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
  element?: string,
  isAnswered?: boolean
}
