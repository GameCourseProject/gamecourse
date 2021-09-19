import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {Role} from "../../../../../_domain/Role";
import {ErrorService} from "../../../../../_services/error.service";

@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  styleUrls: ['./roles.component.scss']
})
export class RolesComponent implements OnInit {

  loading: boolean;

  courseID: number;

  roles: Role[];
  rolesHierarchy: Role[];
  pages;

  selectedRole: string = null;
  selectedLandingPage: string = null;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.params.subscribe(params => {
      this.courseID = params.id;
      this.getRoles(this.courseID);
    });
  }

  getRoles(courseId: number): void {
    this.api.getRoles(courseId)
      .subscribe(res => {
        this.roles = res.roles;
        this.rolesHierarchy = res.rolesHierarchy;
        this.pages = res.pages;
        console.log(res)
        this.loading = false;
      },
        error => ErrorService.set(error));
  }

  saveLandingPage(role: Role): void {
    // TODO
  }

  addRole(): void {
    // TODO
  }

  undo(): void {
    // TODO
  }

  redo(): void {
    // TODO
  }

}
