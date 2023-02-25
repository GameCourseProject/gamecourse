import { Component, OnInit } from '@angular/core';

import { ApiHttpService } from "../../../_services/api/api-http.service";
import { ThemingService } from "../../../_services/theming/theming.service";

import { AuthType } from 'src/app/_domain/auth/auth-type';
import {Theme} from "../../../_services/theming/themes-available";
import {environment} from "../../../../environments/environment";

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html'
})
export class LoginComponent implements OnInit {

  constructor(
    public api: ApiHttpService,
    public themeService: ThemingService
  ) { }

  ngOnInit(): void {
  }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  get DefaultLogoImg(): string {
    const theme = this.themeService.getTheme();
    return theme === Theme.DARK ? environment.logoPicture.dark : environment.logoPicture.light;
  }
}
