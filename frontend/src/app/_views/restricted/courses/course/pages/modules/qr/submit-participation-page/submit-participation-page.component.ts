import { Component, OnInit } from '@angular/core';
import {Course} from "../../../../../../../../_domain/courses/course";
import {exists} from "../../../../../../../../_utils/misc/misc";

@Component({
  selector: 'app-submit-participation-page',
  templateUrl: './submit-participation-page.component.html'
})
export class SubmitParticipationPageComponent implements OnInit {

  loading: boolean = true;

  course: Course;
  participationKey: string;
  lectureNr: number;
  typeOfClass: string;
  typesOfClass: string[];

  constructor() { }

  ngOnInit(): void {
  }

  /*** --------------------------------------------- ***/
  /*** ------------- QR Participation -------------- ***/
  /*** --------------------------------------------- ***/

  async submitParticipation() {
    this.loading = true;

    // const loggedUser = await this.api.getLoggedUser().toPromise();
    // await this.api.addQRParticipation(this.course.id, loggedUser.id, this.lectureNr, this.typeOfClass, this.participationKey).toPromise();
    //
    // const successBox = $('.success_msg');
    // successBox.empty();
    // successBox.append("Your class participation was registered.<br />Congratulations! Keep participating. 😊");
    // successBox.show().delay(5000).fadeOut();

    this.loading = false;
  }

  isReadyToSubmitParticipation(): boolean {
    return exists(this.lectureNr) && this.lectureNr > 0 && exists(this.typeOfClass);
  }
}
