import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../../_services/error.service";

import {Template} from "../../../../../../_domain/pages & templates/template";
import {RoleTypeId} from "../../../../../../_domain/roles/role-type";
import {Role} from "../../../../../../_domain/roles/role";
import {User} from "../../../../../../_domain/users/user";
import {View, VisibilityType} from "../../../../../../_domain/views/view";
import {ViewSelectionService} from "../../../../../../_services/view-selection.service";
import {ViewType} from 'src/app/_domain/views/view-type';
import {ViewBlock} from "../../../../../../_domain/views/view-block";
import {copyObject, exists, objectMap} from "../../../../../../_utils/misc/misc";
import {buildViewTree} from "../../../../../../_domain/views/build-view-tree/build-view-tree";
import {EventType} from "../../../../../../_domain/events/event-type";
import {Event} from "../../../../../../_domain/events/event";
import {buildEvent} from "../../../../../../_domain/events/build-event";
import {ViewText} from "../../../../../../_domain/views/view-text";
import {ViewImage} from "../../../../../../_domain/views/view-image";
import {Variable} from "../../../../../../_domain/variables/variable";

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

  viewToShow: View;

  selectedViewerRole: string;
  selectedUserRole: string;
  selectedRole: string;

  hasModalOpen: boolean;
  isEditSettingsModalOpen: boolean;
  viewToEdit: View;
  linkEnabled: boolean;
  eventToAdd: EventType;
  viewToEditEvents: {[key in EventType]?: string};
  variableToAdd: string;
  viewToEditVariables: {[name: string]: string};

  isPreviewExpressionModalOpen: boolean;

  isEditingLayout: boolean;
  saving: boolean;

  isPreviewingView: boolean;
  viewToPreview: View;

  help: boolean = false;
  clickedHelpOnce: boolean = false;

  hasUnsavedChanges: boolean;

  isVerificationModalOpen: boolean;
  verificationText: string;

  hasWarning: boolean;
  warningMsg: string;

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

  get VisibilityType(): typeof VisibilityType {
    return VisibilityType;
  }

  capitalize(str: string): string {
    return str.capitalize();
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

  changeViewToShow(clearSelection: boolean = true): void {
    this.selectedRole = this.template.roleType == RoleTypeId.ROLE_INTERACTION ? this.selectedUserRole + '>' + this.selectedViewerRole: this.selectedViewerRole;
    this.viewToShow = this.viewsByAspects[this.selectedRole];
    if (clearSelection) this.selection.clear();
  }

  toolbarBtnClicked(btn: string): void {
    const viewSelected = this.selection.get();

    if (btn === 'edit-settings') {
      this.viewToEdit = copyObject(viewSelected);
      this.viewToEditVariables = this.viewToEdit.variables ? objectMap(copyObject(this.viewToEdit.variables), variable => variable.value) : {};
      this.viewToEditEvents = this.viewToEdit.events ? objectMap(copyObject(this.viewToEdit.events), event => '{actions.' + (event as Event).print() + '}') : {};
      if (this.viewToEdit.type === ViewType.TEXT || this.viewToEdit.type === ViewType.IMAGE)
        this.linkEnabled = exists((this.viewToEdit as ViewText|ViewImage).link);
      // TODO: don't show gamecourse classes on input
      this.isEditSettingsModalOpen = true;

    } else if (btn === 'edit-layout') {
      this.isEditingLayout = !this.isEditingLayout;
      (viewSelected as ViewBlock).isEditingLayout = !(viewSelected as ViewBlock).isEditingLayout;
    }
    // TODO

    if (btn !== 'edit-layout') this.hasModalOpen = true;
  }

  updateView(newView: View): void {
    // Change on all aspects
    const aspects = Object.keys(this.viewsByAspects);
    for (const aspect of aspects) {
      const newAspect = this.viewsByAspects[aspect].updateView(newView);
      if (newAspect !== null) {
        this.viewsByAspects[aspect] = newAspect;
        this.hasUnsavedChanges = true;
      }
    }
    this.changeViewToShow(false);
  } // TODO: needs testing

  saveEdit() {
    this.loading = true;
    this.isEditSettingsModalOpen = false;
    this.hasModalOpen = false;

    // Variables
    this.viewToEditVariables = Object.fromEntries(Object.entries(this.viewToEditVariables).filter(([k, v]) => !!v && v != '{}'));
    this.viewToEdit.variables = Object.keys(this.viewToEditVariables).length > 0 ? copyObject(objectMap(this.viewToEditVariables, (value, name) => new Variable(name, value))) : null;

    // Events
    this.viewToEditEvents = Object.fromEntries(Object.entries(this.viewToEditEvents).filter(([k, v]) => !!v && v != '{}'));
    this.viewToEdit.events = Object.keys(this.viewToEditEvents).length > 0 ? copyObject(objectMap(this.viewToEditEvents, (eventStr, type) => buildEvent(type, eventStr))) : null;

    // Link
    if (this.viewToEdit.type === ViewType.TEXT || this.viewToEdit.type === ViewType.IMAGE) {
      const link = (this.viewToEdit as ViewText|ViewImage).link;
      if (!this.linkEnabled || !exists(link) || link.isEmpty())
        (this.viewToEdit as ViewText|ViewImage).link = null;
    }

    // Loop Data
    if (!exists(this.viewToEdit.loopData) || this.viewToEdit.loopData.isEmpty())
      this.viewToEdit.loopData = null;

    this.updateView(this.viewToEdit);

    this.verificationText = 'Saved!';
    this.isVerificationModalOpen = true;
    this.loading = false;
  }

  saveChanges() {
    this.loading = true;
    const viewTree = buildViewTree(Object.values(this.viewsByAspects));
    this.api.saveTemplate(this.courseID, this.template.id, viewTree)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => this.hasUnsavedChanges = false,
        error => ErrorService.set(error)
      )
  } // TODO: needs testing

  previewView() {
    this.loading = true;
    this.hasWarning = true;
    this.warningMsg = 'You need to save changes first for them to appear!';
    this.isPreviewingView = true;
    this.viewToPreview = null;
    this.api.previewTemplate(this.courseID, this.template.id, this.selectedViewerRole, this.selectedUserRole)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        view => this.viewToPreview = view,
        error => ErrorService.set(error)
      )
  }

  changeVisibility(): void {
    if (this.viewToEdit.visibilityType !== VisibilityType.CONDITIONAL)
      this.viewToEdit.visibilityCondition = null;
  }

  addEvent(): void {
    this.viewToEditEvents[this.eventToAdd] = '{}';
    this.eventToAdd = null;
  }

  deleteEvent(type: string): void {
    this.viewToEditEvents[type] = null;
  }

  addVariable(): void {
    this.viewToEditVariables[this.variableToAdd] = '{}';
    this.variableToAdd = null;
  }

  deleteVariable(name: string): void {
    this.viewToEditVariables[name] = null;
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

  getVisibilityTypes(): string[] {
    return Object.values(VisibilityType);
  }

  getEventsAvailableToAdd(): EventType[] {
    return Object.values(EventType).filter(type => !this.viewToEditEvents.hasOwnProperty(type)).sort();
  }

  getEvents(): string[] {
    return Object.keys(this.viewToEditEvents);
  }

  getVariables(): string[] {
    return Object.keys(this.viewToEditVariables);
  }

  goToViews(): void {
    if (this.hasUnsavedChanges) {
      if (confirm('There are unsaved changes. Leave page without saving?')) {
        this.router.navigate(['settings/views'], {relativeTo: this.route.parent});
      }
    }
    this.router.navigate(['settings/views'], {relativeTo: this.route.parent});
  }

  canUndo(): boolean {
    return false;
  } // TODO

  canRedo(): boolean {
    return false;
  } // TODO

  clearSelection() {
    if (!this.isPreviewingView && !this.hasModalOpen && !this.isEditingLayout)
      this.selection.clear();
  }
}
