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
import {ViewTable} from 'src/app/_domain/views/view-table';
import {ViewHeader} from "../../../../../../_domain/views/view-header";
import {EditorAction, ViewEditorService} from "../../../../../../_services/view-editor.service";
import {Subject} from "rxjs";
import {ChartType, ViewChart} from "../../../../../../_domain/views/view-chart";
import {ViewRow} from "../../../../../../_domain/views/view-row";

@Component({
  selector: 'app-view-editor',
  templateUrl: './views-editor.component.html',
  styleUrls: ['./views-editor.component.scss']
})
export class ViewsEditorComponent implements OnInit {

  loading: boolean;

  courseID: number;
  template: Template;
  matchingTemplates: Template[];
  user: User;

  courseRoles: Role[];
  rolesHierarchy: Role[];
  templateRoles: {viewerRole: Role, userRole?: Role}[];
  viewsByAspects: {[key: string]: View};
  enabledModules: string[];

  viewToShow: View;
  templateToAdd: number;

  selectedViewerRole: string;
  selectedUserRole: string;
  selectedRole: string;

  hasModalOpen: boolean;
  isEditSettingsModalOpen: boolean;
  viewToEdit: View;
  viewLoaded: Subject<void> = new Subject<void>();
  linkEnabled: boolean;
  eventToAdd: EventType;
  viewToEditEvents: {[key in EventType]?: string};
  variableToAdd: string;
  viewToEditVariables: {[name: string]: string};

  isPreviewExpressionModalOpen: boolean;

  isEditingLayout: boolean;
  isAddingPartModalOpen: boolean;
  partToAdd: string;
  fakeIDMin: number = 0; // need negative fake IDs for views that are being added and not in DB yet

  isSavingPartAsTemplate: boolean;
  viewToSave: View;
  templateName: string;
  useByRef: boolean;

  isSwitching: boolean;
  saving: boolean;

  isPreviewingView: boolean;
  viewToPreview: View;

  help: boolean = false;
  clickedHelpOnce: boolean = false;

  hasUnsavedChanges: boolean;
  viewsDeleted: number[] = []; // viewIds of views that were deleted; check if need to be deleted from database

  isMessageModalOpen: boolean;
  messageText: string;

  isVerificationModalOpen: boolean;
  verificationText: string;

  hasWarning: boolean;
  warningMsg: string;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    public selection: ViewSelectionService,
    private actionManager: ViewEditorService
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

  get ViewTable(): typeof ViewTable {
    return ViewTable;
  }

  get ViewChart(): typeof ViewChart {
    return ViewChart;
  }

  get VisibilityType(): typeof VisibilityType {
    return VisibilityType;
  }

  get ChartType(): typeof ChartType {
    return ChartType;
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

    this.actionManager.action.subscribe(action => {
      if (action.action === EditorAction.BLOCK_ADD_CHILD) this.isAddingPartModalOpen = true;
      else this.editLayout('table', action);
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
          this.templateRoles = Template.parseRoles(data.templateRoles, template.roleType);
          this.enabledModules = data.enabledModules;

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

  toolbarBtnClicked(btn: string, viewSelected?: View): void {
    if (!viewSelected) viewSelected = this.selection.get();
    this.viewToEdit = copyObject(viewSelected);

    if (btn === 'edit-layout') {  // Edit layout or Switch
      this.isEditingLayout = !this.isEditingLayout;
      (viewSelected as ViewBlock|ViewTable).isEditingLayout = !(viewSelected as ViewBlock|ViewTable).isEditingLayout;
      this.selection.toggleState();

    } else if (!this.isEditingLayout) {
      if (btn === 'edit-settings') {  // Edit view
        this.viewToEditVariables = this.viewToEdit.variables ? objectMap(copyObject(this.viewToEdit.variables), variable => variable.value) : {};
        this.viewToEditEvents = this.viewToEdit.events ? objectMap(copyObject(this.viewToEdit.events), event => '{actions.' + (event as Event).print() + '}') : {};
        if (this.viewToEdit.type === ViewType.TEXT || this.viewToEdit.type === ViewType.IMAGE)
          this.linkEnabled = exists((this.viewToEdit as ViewText|ViewImage).link);
        // TODO: don't show gamecourse classes on input
        this.isEditSettingsModalOpen = true;
        setTimeout(() => this.viewLoaded.next(), 0);

      } else if (btn === 'switch') {
        this.isSwitching = true;
        this.isAddingPartModalOpen = true;

      } else if (btn === 'remove') {  // Delete view
        this.verificationText = 'Are you sure you want to delete this view and all its aspects?';
        this.isVerificationModalOpen = true;

      } else if (btn === 'save-as-template') {  // Save view as template
        this.viewToSave = this.viewToEdit;
        this.isSavingPartAsTemplate = true;
      }
    }

    const noModal = ['edit-layout'];
    if (!noModal.includes(btn)) this.hasModalOpen = true;
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

    // Chart Options
    if (this.viewToEdit.type === ViewType.CHART) {
      const sparkline = (this.viewToEdit as ViewChart).info['spark'];
      if (!sparkline || !exists(sparkline))
        delete (this.viewToEdit as ViewChart).info['spark'];

      if ((this.viewToEdit as ViewChart).chartType === ChartType.PROGRESS)
        delete (this.viewToEdit as ViewChart).info['provider'];
    }

    this.updateView(this.viewToEdit);

    this.messageText = 'Saved!';
    this.isMessageModalOpen = true;
    this.loading = false;
  }

  saveChanges() {
    this.loading = true;
    const viewTree = buildViewTree(Object.values(this.viewsByAspects));

    this.api.saveTemplate(this.courseID, this.template.id, viewTree, this.viewsDeleted.length > 0 ? this.viewsDeleted : null)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => {
          this.hasUnsavedChanges = false;
          this.fakeIDMin = 0;
          this.viewsDeleted = [];
          this.getTemplateEditInfo(this.template);
        },
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

  async editLayout(type: string, action?: { action: EditorAction, params?: any }): Promise<void> {
    if (type === 'block' || type === 'switch') {

      if (this.partToAdd !== 'template') {
        let defaultView;

        // Create view
        if (this.partToAdd === 'text') defaultView = ViewText.getDefault(--this.fakeIDMin, this.viewToEdit.id, this.selectedRole);
        else if (this.partToAdd === 'image') defaultView = ViewImage.getDefault(--this.fakeIDMin, this.viewToEdit.id, this.selectedRole);
        else if (this.partToAdd === 'chart') defaultView = ViewChart.getDefault(--this.fakeIDMin, this.viewToEdit.id, this.selectedRole);
        else if (this.partToAdd === 'block') defaultView = ViewBlock.getDefault(--this.fakeIDMin, this.viewToEdit.id, this.selectedRole);
        else if (this.partToAdd === 'header') {
          defaultView = ViewHeader.getDefault(--this.fakeIDMin, this.viewToEdit.id, this.selectedRole);
          this.fakeIDMin -= 2;

        } else if (this.partToAdd === 'table') {
          defaultView = ViewTable.getDefault(--this.fakeIDMin, this.viewToEdit.id, this.selectedRole);
          this.fakeIDMin -= 4;
        }

        // Update view
        if (type === 'block' && defaultView) {
          (defaultView as View).parentId = this.viewToEdit.id;
          (this.viewToEdit as ViewBlock).children.push(defaultView);

        } else if (type === 'switch' && defaultView) {
          for (const aspect of Object.values(this.viewsByAspects)) {
            const parent = aspect.findParent(this.viewToEdit.parentId);
            if (parent) {
              for (let i = 0; i < (parent as ViewBlock | ViewRow).children.length; i++) {
                const child = (parent as ViewBlock | ViewRow).children[i];
                if (child.viewId === this.viewToEdit.viewId) {
                  (defaultView as View).parentId = parent.id;
                  (parent as ViewBlock | ViewRow).children.removeAtIndex(i);
                  (parent as ViewBlock | ViewRow).children.insertAtIndex(i, defaultView);
                  break;
                }
              }
            }
          }
        }

      } else {
        this.loading = true;
        this.useByRef = !!this.useByRef;

        let templateViewsByAspects: {[p: string]: View};
        await this.api.getTemplateEditInfo(this.courseID, this.templateToAdd).toPromise()
          .then(res => templateViewsByAspects = res.templateViewsByAspect)
          .catch(error => ErrorService.set(error))
          .finally(() => this.loading = false);

        if (!this.useByRef) {
          // Change view IDs
          for (const aspect of Object.values(templateViewsByAspects)) {
            aspect.replaceWithFakeIds(this.fakeIDMin);
          }
        }

        for (const [role, aspect] of Object.entries(templateViewsByAspects)) {
          if (Object.keys(this.viewsByAspects).includes(role)) {
            const view = this.viewsByAspects[role].findView(this.viewToEdit.viewId) as ViewBlock;
            aspect.parentId = view.id;
            view.children.push(aspect);
          }
        }
      }

      this.partToAdd = null;
      this.isAddingPartModalOpen = false;

    } else if (type === 'table') {
      const actionType = action.action;
      if (actionType === EditorAction.TABLE_INSERT_LEFT) this.fakeIDMin -= (this.viewToEdit as ViewTable).insertColumn('left', action.params, this.fakeIDMin);
      else if (actionType === EditorAction.TABLE_INSERT_RIGHT) this.fakeIDMin -= (this.viewToEdit as ViewTable).insertColumn('right', action.params, this.fakeIDMin);
      else if (actionType === EditorAction.TABLE_INSERT_UP) this.fakeIDMin -= (this.viewToEdit as ViewTable).insertRow(action.params.type, 'up', action.params.row, this.fakeIDMin);
      else if (actionType === EditorAction.TABLE_INSERT_DOWN) this.fakeIDMin -= (this.viewToEdit as ViewTable).insertRow(action.params.type, 'down', action.params.row, this.fakeIDMin);
      else if (actionType === EditorAction.TABLE_DELETE_COLUMN) (this.viewToEdit as ViewTable).deleteColumn(action.params);
      else if (actionType === EditorAction.TABLE_DELETE_ROW) (this.viewToEdit as ViewTable).deleteRow(action.params.type, action.params.row);
      else if (actionType === EditorAction.TABLE_MOVE_LEFT) (this.viewToEdit as ViewTable).moveColumn('left', action.params);
      else if (actionType === EditorAction.TABLE_MOVE_RIGHT) (this.viewToEdit as ViewTable).moveColumn('right', action.params);
      else if (actionType === EditorAction.TABLE_MOVE_UP) (this.viewToEdit as ViewTable).moveRow(action.params.type, 'up', action.params.row);
      else if (actionType === EditorAction.TABLE_MOVE_DOWN) (this.viewToEdit as ViewTable).moveRow(action.params.type, 'down', action.params.row);
    }


    this.updateView(this.viewToEdit);
    this.toolbarBtnClicked('edit-layout');
    this.isSwitching = false;
    this.hasUnsavedChanges = true;

    if (type === 'table' && action.action === EditorAction.TABLE_EDIT_ROW) this.toolbarBtnClicked('edit-settings', action.params.type === 'header' ?
      (this.viewToEdit as ViewTable).headerRows[action.params.row] :
      (this.viewToEdit as ViewTable).rows[action.params.row]);
  }

  async getMatchingTemplates(): Promise<void> {
    if (exists(this.matchingTemplates)) return;

    this.loading = true;
    await this.api.getViewsList(this.courseID).toPromise()
      .then(res => {
        this.matchingTemplates = res.templates
          .filter(template => template.roleType === this.template.roleType)
          .filter(template => {
            for (const role of template.roles) {
              if (!this.templateRoles.find(el => {
                if (this.template.roleType === RoleTypeId.ROLE_SINGLE) return el.viewerRole.name === role.viewerRole.name;
                else return el.viewerRole.name === role.viewerRole.name && el.userRole.name === role.userRole.name;
              })) return false;
            }
            return template.roleType === this.template.roleType && template.id !== this.template.id;
          })
          .sort((a, b) => a.name.localeCompare(b.name));
        // const allGlobalTemplates = res.globals;
      })
      .catch(error => ErrorService.set(error))
      .finally(() => this.loading = false);
  }

  saveAsTemplate(): void {
    this.useByRef = !!this.useByRef;

    // Check if already in DB if isRef, otherwise there's no view to link to
    if (!!this.useByRef && this.hasUnsavedChanges) {
      alert('You need to save your changes first.')
      this.isSavingPartAsTemplate = false;
      this.viewToSave = null;
      this.templateName = null;
      this.useByRef = null;
      return;
    }

    this.loading = true;

    // Find all view aspects
    const aspects = [];
    for (const aspect of Object.values(this.viewsByAspects)) {
      const view = aspect.findView(this.viewToSave.viewId);
      if (view) {
        view.parentId = null;
        aspects.push(view);
      }
    }

    // Build view tree & save
    const viewTree = buildViewTree(aspects, !!this.useByRef ? null : this.fakeIDMin);
    this.api.saveViewAsTemplate(this.courseID, this.templateName, viewTree, this.template.roleType, this.useByRef)
      .pipe(finalize(() => this.loading = false))
      .subscribe(
        res => {
          this.isSavingPartAsTemplate = false;
          this.viewToSave = null;
          this.templateName = null;
          this.useByRef = null;
        },
        error => ErrorService.set(error)
      )
  }

  deleteView(viewToDelete: View): void {
    // Delete view on all aspects
    for (const aspect of Object.values(this.viewsByAspects)) {
      const parent = aspect.findParent(viewToDelete.parentId);
      if (parent) parent.removeChildView(viewToDelete.viewId);
    }

    if (!this.viewsDeleted.includes(viewToDelete.viewId))
      this.viewsDeleted.push(viewToDelete.viewId);

    this.hasUnsavedChanges = true;
    this.isVerificationModalOpen = false;
    this.verificationText = null;
    this.hasModalOpen = false;
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
    this.router.navigate(['views'], {relativeTo: this.route.parent});
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

  getChartTypes(): string[] {
    return Object.values(ChartType);
  }

  isRootView(view: View): boolean {
    return !exists(view.parentId);
  }
}
