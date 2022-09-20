import {Component, OnInit} from '@angular/core';

import {ApiHttpService} from "../../../_services/api/api-http.service";

import {Course} from "../../../_domain/courses/course";
import {User} from "../../../_domain/users/user";

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html'
})
export class HomeComponent implements OnInit {

  loading = true;

  user: User;
  activeCourses: Course[];

  constructor(
    private api: ApiHttpService
  ) { }

  async ngOnInit(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
    this.activeCourses = await this.api.getUserCourses(this.user.id, true, true).toPromise();
    this.loading = false;
  }

}
