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
import {ViewType} from 'src/app/_domain/views/view-type';
import {ViewBlock} from "../../../../../../_domain/views/view-block";
import {copyObject} from "../../../../../../_utils/misc/misc";
import {ViewTable} from "../../../../../../_domain/views/view-table";
import {ViewHeader} from "../../../../../../_domain/views/view-header";

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
  viewsByAspects: {[key: string]: View};
  viewTree;

  viewToShow: View;

  selectedViewerRole: string;
  selectedUserRole: string;
  selectedRole: string;

  isEditingLayout: boolean;
  saving: boolean;

  hasModalOpen: boolean;
  isEditSettingsModalOpen: boolean;
  viewToEdit: View;

  isPreviewExpressionModalOpen: boolean;

  isPreviewingView: boolean;
  viewToPreview: View;

  help: boolean = false;
  clickedHelpOnce: boolean = false;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    public selection: ViewSelectionService
  ) { }

  get RoleTypeId(): typeof RoleTypeId {
    return RoleTypeId;
  }

  get ViewType(): typeof ViewType {
    return ViewType;
  }

  get ViewBlock(): typeof ViewBlock {
    return ViewBlock;
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
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        data => {
          this.courseRoles = data.courseRoles;
          this.rolesHierarchy = data.rolesHierarchy;
          this.viewsByAspects = data.templateViewsByAspect;
          this.viewTree = data.templateViewTree;

          // Set template roles
          this.templateRoles = [];
          data.templateRoles.forEach(role => {
            let roleObj: {viewerRole: Role, userRole?: Role };
            if (template.roleType === RoleTypeId.ROLE_SINGLE) {
              roleObj = { viewerRole: Role.fromDatabase({name: role}) };

            } else if (template.roleType === RoleTypeId.ROLE_INTERACTION) {
              roleObj = {
                viewerRole: Role.fromDatabase({name: role.split('>')[1]}),
                userRole: Role.fromDatabase({name: role.split('>')[0]})
              };
            }
            this.templateRoles.push(roleObj)
          });

          // Set selected roles
          this.selectedViewerRole = this.templateRoles.length !== 0 ? this.templateRoles[0].viewerRole.name : 'Default';
          if (template.roleType == RoleTypeId.ROLE_INTERACTION)
            this.selectedUserRole = this.templateRoles.length !== 0 ? this.templateRoles[0].userRole.name : 'Default';

          // Set view to show
          this.changeViewToShow();
        },
        error => ErrorService.set(error)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  changeViewToShow(): void {
    this.selectedRole = this.template.roleType == RoleTypeId.ROLE_INTERACTION ? this.selectedUserRole + '>' + this.selectedViewerRole: this.selectedViewerRole;
    this.viewToShow = this.viewsByAspects[this.selectedRole];
    this.selection.clear();
  }

  toolbarBtnClicked(btn: string): void {
    const viewSelected = this.selection.get();

    if (btn === 'edit-settings') {
      this.viewToEdit = copyObject(viewSelected);
      this.isEditSettingsModalOpen = true;

    } else if (btn === 'edit-layout') {
      this.isEditingLayout = !this.isEditingLayout;
      (viewSelected as ViewBlock).isEditingLayout = !(viewSelected as ViewBlock).isEditingLayout;
    }
    // TODO

    if (btn !== 'edit-layout') this.hasModalOpen = true;
  } // TODO

  saveEdit() {
    // TODO
    console.log(this.viewToEdit)
    // this.view = this.updateView(this.view, this.viewToEdit);
    console.log(this.viewToShow);
  } // TODO

  updateView(view: View, newView: View): View {
    if (view.id === newView.id) {
      return newView;

    } else if (view.type === ViewType.BLOCK) {
      for (let child of (view as ViewBlock).children) {
        child = this.updateView(child, newView);
      }

    } else if (view.type === ViewType.TABLE) {
      for (const headerRow of (view as ViewTable).headerRows) {
        for (const row of headerRow.children) {
          const viewFound = this.updateView(row, newView);
          if (viewFound) return viewFound;
        }
      }

      for (const bodyRow of (view as ViewTable).rows) {
        for (const row of bodyRow.children) {
          const viewFound = this.updateView(row, newView);
          if (viewFound) return viewFound;
        }
      }

    } else if (view.type === ViewType.HEADER) {
      let viewFound = this.updateView((view as ViewHeader).image, newView);
      if (!viewFound) viewFound = this.updateView((view as ViewHeader).title, newView);
      if (viewFound) return viewFound;
    }

    return view;
  } // TODO

  previewView() {
    this.loading = true;
    this.isPreviewingView = true;
    this.viewToPreview = null;
    this.api.previewTemplate(this.courseID, this.template.id, this.selectedViewerRole, this.selectedUserRole)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        view => this.viewToPreview = view,
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
    return roles;
  }

  goToViews(): void {
    this.router.navigate(['settings/views'], {relativeTo: this.route.parent});
  }

  canUndo(): boolean {
    return false;
  } // TODO

  canRedo(): boolean {
    return false;
  } // TODO
}
