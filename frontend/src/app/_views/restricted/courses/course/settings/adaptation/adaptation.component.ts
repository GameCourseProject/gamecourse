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
import {Action} from "../../../../../../_domain/modules/config/Action";
import {DownloadManager} from "../../../../../../_utils/download/download-manager";

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
  refreshing: boolean = true;

  course: Course;
  user: User;
  isAdminOrTeacher: boolean = false;
  @ViewChild('f', {static: false}) f: NgForm;       // (de)activation form (admin)

  gameElementToActOn: GameElement;
  availableGameElements: GameElement[];

  readonly tableType: string[] = ['overall', 'question2'];

  /** -- ADMIN VARIABLES -- **/
  nrAnswers: number = 0;
  nrStudents: number = 0;
  gameElementToManage: GameElementManageData = this.initGameElementToManage();
  adminMode: 'questionnaire statistics' | 'activate' | 'deactivate';

  statistics = {
    q1: { series: null,
          colors: ["#C53B55", "#65E59F"],
          labels: ["Yes", "No"],
          title: "Question 1:",
          subtitle: "Percentage of students that noticed differences in their interface."
    },
    q2: { tableData:null,
          tableHeaders: [{label: 'Description'}],
          tableOptions: {
            order: [0, 'asc'],
            columnDefs: [
              { type: 'natural', targets: [0] },
              { orderable: true, targets: [0] },
              { searchable: false, targets: [0] }
            ]
          },
          data: null,
          title: "Question 2:",
          subtitle: "Descriptions of what students saw different regarding this game element." },
    q3: { series: [{ name: "Nº of students", data: null}],
          categories: [ "10", "9", "8", "7", "6", "5", "4", "3", "2", "1"],
          highlights: [
            { color: "#61BE88", value: "10"},
            { color: "#8BC972", value: "9" },
            { color: "#AED257", value: "8" },
            { color: "#D6D83F", value: "7" },
            { color: "#F9DF1D", value: "6" },
            { color: "#FFC92A", value: "5" },
            { color: "#FBAD37", value: "4" },
            { color: "#F88A3B", value: "3" },
            { color: "#EE603B", value: "2" },
            { color: "#DF1D3D", value: "1"}
          ],
          title: "Question 3:",
          subtitle: "How many students thought about this game element per enjoyment level (1-lowest, 10-highest)."
    }
  }

  /** -- NON-ADMIN VARIABLES -- **/
  selectedGameElement: string;
  gameElementChildren: {[gameElement: string]: {version: string}}[];
  previousPreference: string;

  availableGameElementsSelect: { value: string, text: string }[] = [];

  activeButton: string = null;
  option: string;

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
      await this.getUser(courseID);
      await this.getCourse(courseID);

      await this.getGameElements(courseID);
      this.loading.page = false;

      await this.buildTable(this.tableType[0]);

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

    if (this.isAdminOrTeacher){
      const students = await this.api.getActiveStudents(this.course.id).toPromise();
      this.nrStudents = Object.keys(students).length;
    }
  }

  async getUser(courseID: number): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();

    this.isAdminOrTeacher = this.user.isAdmin || await this.api.isTeacher(courseID, this.user.id).toPromise();
  }

  async getGameElements(courseID: number): Promise<void> {
    // ADMIN
    if (this.isAdminOrTeacher){
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

  /*** -------------------------------------------- ***/
  /*** ----------------- Table -------------------- ***/
  /*** -------------------------------------------- ***/

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
      { type: 'string', targets: [0] },
      { orderable: false, targets: [1] }
    ]
  };

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

  async buildTable(tableType: string) {
    this.loading.table = true;

    const table: {type: TableDataType, content: any}[][] = [];


    if (tableType === this.tableType[0]){
      if (this.isAdminOrTeacher){
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
    } else if (tableType === this.tableType[1]){
      for (let i = 0; i < this.statistics.q2.data.length; i++){
        if (this.statistics.q2.data[i] !== null){
          table.push([{type: TableDataType.TEXT, content: {text: this.statistics.q2.data[i]}}]);
        }
      }
      this.statistics.q2.tableData = table;
    }

    this.loading.table = false;
  }

  async doActionOnTable(action: string, row: number, col: number, value?: any) {
    this.gameElementToActOn = this.availableGameElements[row];

    if (action === 'value changed game element' && col === 1 && this.isAdminOrTeacher){
      if ((this.gameElementToActOn.isActive).toString() !== '0' && this.gameElementToActOn.isActive !== false) {
        this.adminMode = 'deactivate';
      } else this.adminMode = 'activate';

      this.gameElementToManage = this.initGameElementToManage(this.gameElementToActOn);
      ModalService.openModal('manage-game-element');

    } else if (action === 'Questionnaire statistics' && col === 2 && this.isAdminOrTeacher){
      this.loading.action = true;
      this.gameElementToManage = this.initGameElementToManage(this.gameElementToActOn);

      // setting up data for statistics modal
      await this.setUpData(await this.api.getElementStatistics(this.course.id, this.gameElementToManage.id).toPromise());

      this.adminMode = 'questionnaire statistics';
      this.loading.action = false;
      ModalService.openModal('questionnaire-statistics');

    } else if (action === 'Export questionnaire answers' && col === 2 && this.isAdminOrTeacher){
      await this.exportAnswers(this.gameElementToActOn);

    } else if (action === 'answer questionnaire' && col === 1) {
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
    let newState;

    if ((this.gameElementToManage.isActive).toString() === 'true' || (this.gameElementToManage.isActive).toString() === 'false'){
      newState = !this.gameElementToManage.isActive;
    }
    else if ((this.gameElementToManage.isActive).toString() === '0' || (this.gameElementToManage.isActive).toString() === '1'){
      newState = (this.gameElementToManage.isActive).toString() !== '1';
    }

    const gameElement = await this.api.setGameElementActive(this.course.id, this.gameElementToManage.module,
      newState, this.gameElementToManage.notify).toPromise();

    const index = this.availableGameElements.findIndex(element => (element.id).toString() === (gameElement.id).toString());
    this.availableGameElements.removeAtIndex(index);
    this.availableGameElements.push(gameElement);

    await this.buildTable(this.tableType[0]);
    this.loading.action = false;
    this.resetGameElementManage();
    ModalService.closeModal('manage-game-element');
    AlertService.showAlert(AlertType.SUCCESS, 'Adaptation for game element \'' + gameElement.module + '\' ' + this.adminMode + 'd');

  }

  closeStatistics(){
    this.statistics.q1.series = null;
    this.statistics.q2.tableData = null;
    this.statistics.q2.data = null;
    this.statistics.q3.series[0].data = null;
    this.nrAnswers = 0;
    ModalService.closeModal('questionnaire-statistics');
    this.resetGameElementManage();
  }

  async exportAnswers(gameElement: GameElement): Promise<void>{
    this.nrAnswers = await this.api.getNrAnswersQuestionnaire(this.course.id, this.gameElementToActOn.id).toPromise();
    if (this.nrAnswers === 0) {
      AlertService.showAlert(AlertType.WARNING, 'There are no answers to export regarding this game element');

    } else {
      this.loading.action = true;

      const contents = await this.api.exportAnswersQuestionnaire(this.course.id, gameElement.id).toPromise();
      DownloadManager.downloadAsCSV((this.course.short ?? this.course.name) + '-' + gameElement.module + 'QuestionnaireAnswers', contents);

      this.loading.action = false;
    }
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
      await this.buildTable(this.tableType[0]);

      let filteredQuestionnaires = this.questionnaires.filter(function (item) { return !item.isAnswered });
      if (filteredQuestionnaires.length === 0) {
        ModalService.openModal('all-questionnaires-submitted');
      }

    } else if (action === 'show game elements'){
      ModalService.closeModal('all-questionnaires-submitted');
      this.isQuestionnaire = false;
    }
  }

  async preparePreferences(gameElement: string){
    this.activeButton = null;

    if (gameElement && gameElement !== ""){
      await this.getChildren(gameElement);
      await this.getPreviousPreference(gameElement);
    }
    else{
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
      this.option = null;
      this.activeButton = null;

    } else{
      this.previousPreference = preference;
      this.option = preference;
      this.activeButton = preference;
    }
  }

  async getChildren(gameElement: string) {
    this.gameElementChildren =  await this.api.getChildrenGameElement(this.course.id, gameElement).toPromise();
  }

  async updatePreference(): Promise<void> {
    const newPreference = this.activeButton;

    if (newPreference) {
      if (newPreference == this.previousPreference){
        AlertService.showAlert(AlertType.WARNING, 'Nothing new to update');

      } else {
        if (this.previousPreference === "none") { this.previousPreference = null; }

        await this.api.updateUserPreference(this.course.id, this.user.id,
          this.selectedGameElement, this.previousPreference, newPreference).toPromise();

        this.previousPreference = newPreference;
        AlertService.showAlert(AlertType.SUCCESS, 'New preference saved');
      }

    } else AlertService.showAlert(AlertType.ERROR, 'Please select a new preference to save');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  async setUpData(statistics: { questionNr: { parameter: string, value: number }[] | string[] }){
    // NOTE: forces graphs to update
    this.refreshing = true;
    setTimeout(() => this.refreshing = false, 0);

    this.nrAnswers = await this.api.getNrAnswersQuestionnaire(this.course.id, this.gameElementToManage.id).toPromise();

    // Question 1 data
    this.statistics.q1.labels = Object.keys(statistics["question1"]).map(element => { return element.capitalize() });
    this.statistics.q1.series = Object.values(statistics["question1"]).map(element => {return parseInt(String(element))});
    // Question 2 data
    this.statistics.q2.data = Object.values(statistics["question2"]);
    // Question 3 data
    this.statistics.q3.series[0].data = ((Object.values(statistics["question3"])).map(element => {return parseInt(<string>element);})).reverse();

    await this.buildTable(this.tableType[1]);
  }


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
    if (this.gameElementChildren) { return Object.keys(this.gameElementChildren); }
    else return [];
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
