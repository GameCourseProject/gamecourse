import { Component, OnInit } from '@angular/core';

import { AuthType } from 'src/app/_domain/AuthType';
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {Router} from "@angular/router";

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {

  constructor(
    public api: ApiHttpService,
    private router: Router
  ) { }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

  ngOnInit(): void {
    this.api.checkLogin().subscribe(
      isLoggedIn => {
        if (isLoggedIn) this.router.navigate(['/main']);
      },
      error => {
        if (error.status === 401) {
          this.router.navigate(['/no-access'])
        }

        if (error.status === 409) {
          this.router.navigate(['/setup'])
        }

        // TODO: alert
        console.error(error)
      });
  }

}
