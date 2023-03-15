import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {NgForm} from "@angular/forms";

import {Course} from "../../../../../../../../_domain/courses/course";
import {exists} from "../../../../../../../../_utils/misc/misc";

import {ApiHttpService} from "../../../../../../../../_services/api/api-http.service";
import {AlertService, AlertType} from "../../../../../../../../_services/alert.service";

@Component({
  selector: 'app-submit-participation-page',
  templateUrl: './submit-participation-page.component.html'
})
export class SubmitParticipationPageComponent implements OnInit {

  loading = {
    page: true,
    action: false
  };

  course: Course;
  typesOfClasses: {value: string, text: string}[];

  key: string;
  classNr: number;
  typeOfClass: string;

  @ViewChild('f', { static: false }) f: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      // Get course information
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      // Get types of classes available
      await this.getTypesOfClass();

      // Get key information
      this.route.params.subscribe(async params => {
        this.key = params.key;
        this.loading.page = false;
      });
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getTypesOfClass(): Promise<void> {
    this.typesOfClasses = (await this.api.getTypesOfClass().toPromise()).map(type => {
      return {value: type, text: type};
    });
  }


  /*** --------------------------------------------- ***/
  /*** ------------- QR Participation -------------- ***/
  /*** --------------------------------------------- ***/

  async submitParticipation() {
    if (this.f.valid) {
      this.loading.action = true;

      const loggedUser = await this.api.getLoggedUser().toPromise();
      await this.api.addQRParticipation(this.course.id, loggedUser.id, this.classNr, this.typeOfClass, this.key).toPromise();

      this.loading.action = false;
      AlertService.showAlert(AlertType.SUCCESS, 'Your in-class participation was registered. \nCongratulations! Keep participating. 😊');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  isReadyToSubmitParticipation(): boolean {
    return exists(this.classNr) && this.classNr > 0 && exists(this.typeOfClass);
  }
}
