import { Component, OnInit } from '@angular/core';

import { ApiHttpService } from "../../../_services/api/api-http.service";

import { AuthType } from 'src/app/_domain/auth/auth-type';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html'
})
export class LoginComponent implements OnInit {

  constructor(
    public api: ApiHttpService
  ) { }

  ngOnInit(): void {
  }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

}
