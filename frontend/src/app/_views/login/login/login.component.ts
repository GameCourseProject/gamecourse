import { Component, OnInit } from '@angular/core';

import { AuthType } from 'src/app/_domain/AuthType';
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {Router} from "@angular/router";
import {ErrorService} from "../../../_services/error.service";

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
    this.api.isLoggedIn().subscribe(
      isLoggedIn => {
        if (isLoggedIn) this.router.navigate(['/my-info']);
      },
      error => {
        if (error.status === 401) {
          this.router.navigate(['/no-access'])
        }

        else if (error.status === 409) {
          this.router.navigate(['/setup'])
        }

        else ErrorService.set(error);
      });
  }

}
