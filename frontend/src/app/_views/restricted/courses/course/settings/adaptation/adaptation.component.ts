import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {Component, OnInit } from "@angular/core";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../_domain/courses/course";
import { User } from "src/app/_domain/users/user";

@Component({
  selector: 'app-adaptation',
  templateUrl: './adaptation.component.html'
})
export class AdaptationComponent implements OnInit {

  loading = {
    page: true,
    action: false
  }

  course: Course;
  user: User;

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
    })
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
    let gameNames = await this.api.getAdaptationRoles(courseID).toPromise();

    for (let i = 0; i < gameNames.length; i++){
      this.availableGameElements.push({value: gameNames[i], text: gameNames[i]});
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

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
