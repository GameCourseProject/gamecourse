import { Component, OnInit } from '@angular/core';

import { AuthType } from 'src/app/_domain/auth/auth-type';
import {ApiHttpService} from "../../../_services/api/api-http.service";

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {

  constructor(public api: ApiHttpService) { }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

  ngOnInit(): void {
  }

}
