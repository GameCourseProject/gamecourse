import {Component, Input, OnInit, ViewChild} from '@angular/core';
import { NgForm, Validators} from "@angular/forms";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {Router} from "@angular/router";
import {finalize, map} from "rxjs/operators";

import {ThemingService} from "../../../_services/theming/theming.service";
import {Theme} from "../../../_services/theming/themes-available";
import {AuthType} from "../../../_domain/auth/auth-type";

@Component({
  selector: 'app-setup',
  templateUrl: './setup.component.html',
  styleUrls: ['./setup.component.scss']
})
export class SetupComponent implements OnInit {

  actionInProgress: boolean;

  courseName: string;
  courseColor: string;
  teacherId: number;
  teacherUsername: string;

  theme: Theme;

  @ViewChild('f', { static: false }) f:NgForm;

  constructor(
    private api: ApiHttpService,
    private router: Router,
    public themeService: ThemingService
  ) { this.theme = themeService.getTheme()}

  ngOnInit(): void {
  }

  submit(): void{
    if (this.f.valid) {
      this.actionInProgress = true;
        this.api.doSetup(this.courseName, this.courseColor, this.teacherId, this.teacherUsername)
        .pipe( finalize(() => this.actionInProgress = false) )
        .subscribe(setupDone => {
          if (setupDone) this.router.navigate(['/']);
        });
    }
  }
}

export interface SetupData {
  courseName: string,
  courseColor: string,
  teacherId: number,
  teacherUsername: string
}
