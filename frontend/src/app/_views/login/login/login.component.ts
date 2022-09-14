import { Component, OnInit } from '@angular/core';

import { AuthType } from 'src/app/_domain/auth/auth-type';
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {Router} from "@angular/router";

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html'
})
export class LoginComponent implements OnInit {

  constructor(public api: ApiHttpService, public router: Router) { }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

  ngOnInit(): void {
  }

}
