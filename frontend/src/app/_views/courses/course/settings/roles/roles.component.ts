import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {Role} from "../../../../../_domain/Role";
import {ErrorService} from "../../../../../_services/error.service";
import {Page} from "../../../../../_domain/Page";

@Component({
  selector: 'app-roles',
  templateUrl: './roles.component.html',
  styleUrls: ['./roles.component.scss']
})
export class RolesComponent implements OnInit {

  loading: boolean;

  courseID: number;

  defaultRoles: string[] = ['Teacher', 'Student', 'Watcher'];
  roles: Role[];
  pages: Page[];

  selected: {role: string, page: string} = {
    role: null,
    page: ''
  };

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


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getRoles(courseId: number): void {
    this.api.getRoles(courseId)
      .subscribe(res => {
        this.roles = res.roles;
        this.pages = res.pages;
        // FIXME: remove
        this.roles[1].children = [Role.fromDatabase({name: 'Profiling'})]
      },
        error => ErrorService.set(error),
        () => {
        this.loading = false;
        setTimeout(() => {
          // @ts-ignore
          $('#roles-config').nestable({dropdown: this.pages.map(page => {name: page.name})});
        }, 0);
      });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

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
