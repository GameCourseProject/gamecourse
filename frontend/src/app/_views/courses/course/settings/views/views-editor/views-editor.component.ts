import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../../_services/error.service";

import {Template} from "../../../../../../_domain/pages & templates/template";
import {RoleTypeId} from "../../../../../../_domain/roles/role-type";
import {Role} from "../../../../../../_domain/roles/role";
import {User} from "../../../../../../_domain/users/user";
import {View} from "../../../../../../_domain/views/view";
import {ViewSelectionService} from "../../../../../../_services/view-selection.service";

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
  rolesHierarchy: Role[];
  templateRoles: {viewerRole: Role, userRole?: Role}[];

  view: View;
  viewerRole: Role;
  selectedViewerRole: string;
  userRole: Role;
  selectedUserRole: string;

  help: boolean = false;
  clickedHelpOnce: boolean = false;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    private selection: ViewSelectionService
  ) { }

  get RoleTypeId(): typeof RoleTypeId {
    return RoleTypeId;
  }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);

      this.route.params.subscribe(childParams => {
        this.getTemplate(childParams.id);
      });
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getTemplate(templateId: number): void {
    this.loading = true;
    this.api.getTemplate(this.courseID, templateId)
      .subscribe(
        template => {
          this.template = template;
          this.getTemplateEditInfo(this.template)
          // if (part.isTemplateRef) //TODO: templateRef
          //   $('#warning_ref').show();
        },
        error => ErrorService.set(error)
      )
  }

  getTemplateEditInfo(template: Template): void {
    this.loading = true;
    this.api.getTemplateEditInfo(this.courseID, template.id)
      .subscribe(
        data => {
          this.courseRoles = data.courseRoles;
          this.rolesHierarchy = data.rolesHierarchy;

          // Set template roles
          this.templateRoles = [];
          data.templateRoles.forEach(role => {
            let roleObj: {viewerRole: Role, userRole?: Role };
            if (template.roleType === RoleTypeId.ROLE_SINGLE) {
              roleObj = { viewerRole: Role.fromDatabase({name: role.split('>')[0]}) };

            } else if (template.roleType === RoleTypeId.ROLE_INTERACTION) {
              roleObj = {
                viewerRole: Role.fromDatabase({name: role.split('>')[1]}),
                userRole: Role.fromDatabase({name: role.split('>')[0]})
              };
            }

            if (!this.templateRoles.find(el => el.viewerRole === roleObj.viewerRole && el.userRole === roleObj.userRole))
              this.templateRoles.push(roleObj)
          });

          // Set selected roles
          this.selectedViewerRole = "Default";
          if (template.roleType == RoleTypeId.ROLE_INTERACTION) this.selectedUserRole = "Default";

          // Get view
          this.getTemplateEditView(template, this.selectedViewerRole, this.selectedUserRole);
        },
        error => ErrorService.set(error)
      )
  }

  getTemplateEditView(template: Template, viewerRole: string, userRole?: string): void {
    this.loading = true;
    this.api.getTemplateEditView(this.courseID, template.id, viewerRole, userRole)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        view => {
          this.view = view;
          console.log(this.view)
        },
        error => ErrorService.set(error)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getRoles(type: 'viewer' | 'user'): Role[] {
    const roles: Role[] = [];
    this.templateRoles.forEach(role => {
      if (!roles.find(el => type === 'viewer' ? el.name === role.viewerRole.name : el.name === role.userRole.name))
        roles.push(type === 'viewer' ? role.viewerRole : role.userRole);
    })

    const index = roles.findIndex(el => el.name === 'Default');
    if (index !== -1) roles.splice(index, 1);
    roles.unshift(new Role(null, 'Default', null, null));

    return roles;
  }

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
