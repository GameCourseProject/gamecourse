import { Component, OnInit } from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../../_services/error.service";

import {Template} from "../../../../../../_domain/pages & templates/template";
import {RoleTypeId} from "../../../../../../_domain/roles/role-type";
import {Role} from "../../../../../../_domain/roles/role";
import {User} from "../../../../../../_domain/users/user";

@Component({
  selector: 'app-view-editor',
  templateUrl: './views-editor.component.html',
  styleUrls: ['./views-editor.component.scss']
})
export class ViewsEditorComponent implements OnInit {

  loading: boolean;

  courseID: number;
  template: Template;
  user: User;

  courseRoles: Role[];
  fields: any[];
  rolesHierarchy: Role[];
  templates: Template[]
  view: any[];
  viewRoles: Role[];

  help: boolean = false;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = params.id;

      this.route.params.subscribe(childParams => {
        this.getTemplate(childParams.id);
      });
    });
  }

  get RoleTypeId(): typeof RoleTypeId {
    return RoleTypeId;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getEditInfo(template: Template): void {
    this.loading = true;
    this.api.getEdit(this.courseID, template, {viewerRole: 'Default', userRole: 'Default'})
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => {
          this.courseRoles = res.courseRoles;
          this.fields = res.fields;
          this.rolesHierarchy = res.rolesHierarchy;
          this.templates = res.templates;
          this.view = res.view;
          this.viewRoles = res.viewRoles;
        },
        error => ErrorService.set(error)
      )
  }

  getTemplate(templateId: number): void {
    this.loading = true;
    this.api.getTemplate(this.courseID, templateId)
      .subscribe(
        template => this.template = template,
        error => ErrorService.set(error),
        () => this.getEditInfo(this.template)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  goToViews(): void {
    this.router.navigate(['settings/views'], {relativeTo: this.route.parent});
  }

  canUndo(): boolean {
    return false;
  }

  canRedo(): boolean {
    return false;
  }

}
