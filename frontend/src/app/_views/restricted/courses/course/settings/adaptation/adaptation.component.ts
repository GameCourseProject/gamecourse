import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {Component, OnInit } from "@angular/core";
import {ActivatedRoute} from "@angular/router";
import {Course} from "../../../../../../_domain/courses/course";

@Component({
  selector: '',
  templateUrl: './adaptation.component.html'
})
export class AdaptationComponent implements OnInit {

  loading = {
    page: true,
    action: false
  }

  course: Course;

  selectedGameElement: string;
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

  getChildren(gameElement: string) {
    return ["a", "b", "c"];
  }

  discard() {
    // TODO
  }

  save() {
    // TODO
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getButtonColor(i){
    if (this.activeButton === i){ return "active";}
    else return "primary";
  }

  setActive(index : number){
    this.activeButton = index;
  }

}
