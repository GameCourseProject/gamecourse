import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {Component, OnInit, ViewChild} from "@angular/core";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../_domain/courses/course";
import {User} from "src/app/_domain/users/user";
import {TableDataType} from "../../../../../../_components/tables/table-data/table-data.component";
import {GameElement} from "../../../../../../_domain/adaptation/GameElement";
import {ModalService} from "../../../../../../_services/modal.service";
import {NgForm} from "@angular/forms";
import {clearEmptyValues} from "../../../../../../_utils/misc/misc";
import {AlertService, AlertType} from "../../../../../../_services/alert.service";
import {Observable} from "rxjs";
import {CourseUser} from "../../../../../../_domain/users/course-user";
import {Action} from "../../../../../../_domain/modules/config/Action";

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
  mode: 'questionnaire statistics' | 'activate' | 'deactivate';
  availableGameElements: GameElement[];

  periodicity: {number: number, time: string};
  courseUsers: User[];
  courseUsersSelect: {value: string, text: string}[] = [];
  usersMode: "all-users" | "all-except-users" | "only-some-users";

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
        await this.getCourseUsers(courseID);
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
    if (this.user.isAdmin){
      this.availableGameElements = await this.api.getGameElements(courseID).toPromise();
    }

    // NON-ADMIN
    else {
      // all available game elements
      this.availableGameElements = await this.api.getGameElements(courseID, true).toPromise();
      let ids = this.availableGameElements.map(value => {return value.id});
      // game elements the logged-in user is allowed to edit
      const gameElements = await this.api.getGameElementsUserCanEdit(courseID, this.user.id).toPromise();

      let elements = [];
      // see if game elements logged-in user is allowed to edit is active
      for (let i = 0; i < gameElements.length; i++){
        if (ids.includes(gameElements[i].id)){
          elements.push(gameElements[i]);
        }
      }

      for (let i = 0; i < elements.length; i++){
        this.availableGameElementsSelect.push({value: elements[i].module, text: elements[i].module});
      }

    }
  }

  async getCourseUsers(courseID: number): Promise<void>{
    this.courseUsers = (await this.api.getCourseUsers(courseID, true).toPromise()).sort((a, b) => a.name.localeCompare(b.name));
    this.courseUsersSelect = this.courseUsers.map(user => {
      return {value: 'id-' + user.id, text: user.name};
    });
  }

  /*** --------------------------------------------- ***/
  /*** ------------ Table (ADMIN) ------------------ ***/
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
        {type: TableDataType.TEXT, content: {text: gameElement.module}},
        {type: TableDataType.TOGGLE, content: {toggleId: 'isActive', toggleValue: gameElement.isActive}},
        {type: TableDataType.ACTIONS, content: {actions: [
          {action: 'Questionnaire statistics', icon: 'tabler-chart-pie', color: 'primary'},
          {action: 'Export questionnaire answers', icon: 'jam-upload', color: 'primary'}]}
        }
      ]);
    });
    this.data = table;
    this.loading.table = false;
  }

  async doActionOnTable(action: string, row: number, col: number, value?: any) {
    const gameElementToActOn = this.availableGameElements[row];

    if (action === 'value changed game element' && col === 1){
      console.log(gameElementToActOn);
      if (gameElementToActOn.isActive) {
        this.mode = 'deactivate';
        //this.toggleActive(gameElementToActOn);
      } else this.mode = 'activate';

      this.gameElementToManage = this.initGameElementToManage(gameElementToActOn);
      ModalService.openModal('manage-game-element');

    } /*else if (action === 'Configure') {
      this.mode = 'configure';

      this.gameElementToManage = this.initGameElementToManage(gameElementToActOn);

      ModalService.openModal('manage-game-element');
    }*/
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/


  /** -- ADMIN ACTIONS -- **/
  async toggleActive(){
    this.loading.action = true;

    this.gameElementToManage.isActive = !this.gameElementToManage.isActive;
    await this.api.setGameElementActive(this.course.id, this.gameElementToManage.module,
      this.gameElementToManage.isActive, this.gameElementToManage.notify).toPromise();

    this.loading.action = false;
  }

  // FIXME
  /*async updateGameElement(){
    if (this.c.valid && this.usersMode){
      this.loading.action = true;

      this.gameElementToManage.nDays = this.periodicity.number;
      this.gameElementToManage.usersMode = this.usersMode;

      if (this.usersMode === "all-users"){
        this.gameElementToManage.users = this.courseUsers.map(user => { return (user.id).toString()});

      } else if (this.usersMode === "all-except-users" || this.usersMode === "only-some-users") {
          const users = this.gameElementToManage.users.map(user => {
          return parseInt((user.toString()).split("-").pop())
        });

        const array = [];
        for (let i = 0; i < users.length; i++) {
          for (let j = 0; j < this.courseUsers.length; j++) {
            if ((this.usersMode === "all-except-users" && users[i] !== this.courseUsers[j].id) ||
              (this.usersMode === "only-some-users" && users[i] === this.courseUsers[j].id)) {
              array.push(this.courseUsers[j].id);
            }
          }
        }
        this.gameElementToManage.users = array;
      }

      const gameElementConfig = await this.api.updateEditableGameElement(clearEmptyValues(this.gameElementToManage)).toPromise();
      const index = this.availableGameElements.findIndex(gameElement => gameElement.id === gameElementConfig.id);
      this.availableGameElements.removeAtIndex(index);
      this.availableGameElements.push(gameElementConfig);

      this.buildTable();
      this.loading.action = false;
      ModalService.closeModal('manage-game-element');
      AlertService.showAlert(AlertType.SUCCESS, 'Game element \'' + gameElementConfig.module + '\' configured');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }*/

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
      this.option = preference;
      //this.activeButton = this.gameElementChildren.findIndex(el => el === this.previousPreference);
    }
  }

  async getChildren(gameElement: string) {
    this.gameElementChildren =  await this.api.getChildrenGameElement(this.course.id, gameElement).toPromise();
  }

  async save(): Promise<void> {
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

  resetGameElementManage(){
    this.gameElementToManage = this.initGameElementToManage();
    this.usersMode = null;
    this.c.resetForm();
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
}

export interface GameElementManageData {
  id?: number,
  course?: number,
  module?: string,
  isActive?: boolean,
  notify?: boolean
}
