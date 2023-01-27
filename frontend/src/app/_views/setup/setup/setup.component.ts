import {Component, OnInit, ViewChild} from '@angular/core';
import { NgForm} from "@angular/forms";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {Router} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ThemingService} from "../../../_services/theming/theming.service";
import {Theme} from "../../../_services/theming/themes-available";

import {environment} from "../../../../environments/environment";
import {AlertService, AlertType} from "../../../_services/alert.service";

@Component({
  selector: 'app-setup',
  templateUrl: './setup.component.html'
})
export class SetupComponent implements OnInit {

  setupData: SetupData = {
    courseName: null,
    courseColor: null,
    adminId: null,
    adminUsername: null
  };

  actionInProgress: boolean;

  @ViewChild('f', { static: false }) f:NgForm;

  constructor(
    private api: ApiHttpService,
    private router: Router,
    public themeService: ThemingService
  ) {}

  ngOnInit(): void {
  }

  submit(): void{
    if (this.f.valid) {
      this.actionInProgress = true;

      this.api.doSetup(this.setupData)
        .pipe( finalize(() => this.actionInProgress = false) )
        .subscribe(setupDone => {
          if (setupDone) {
            AlertService.showAlert(AlertType.SUCCESS, 'GameCourse is ready to go!');
            this.router.navigate(['/']);
          }
        });

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  get DefaultLogoImg(): string {
    const theme = this.themeService.getTheme();
    return theme === Theme.DARK ? environment.logoPicture.dark : environment.logoPicture.light;
  }
}

export interface SetupData {
  courseName: string,
  courseColor: string,
  adminId: number,
  adminUsername: string
}
