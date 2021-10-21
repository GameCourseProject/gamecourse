import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../../_services/error.service";
import {UpdateService, UpdateType} from "../../../../../../_services/update.service";

import {Page} from "../../../../../../_domain/pages & templates/page";
import {Template} from "../../../../../../_domain/pages & templates/template";
import {RoleType} from "../../../../../../_domain/roles/role-type";
import {Reduce} from "../../../../../../_utils/display/reduce";
import {DownloadManager} from "../../../../../../_utils/download/download-manager";

@Component({
  selector: 'app-views',
  templateUrl: './views.component.html',
  styleUrls: ['./views.component.scss']
})
export class ViewsComponent implements OnInit {

  loading: boolean;

  courseID: number;

  allPages: Page[];
  allViewTemplates: Template[];
  allGlobalTemplates: Template[];

  reduceForPages = new Reduce();
  reduceForViewTemplates = new Reduce();
  reduceForGlobalTemplates = new Reduce();
  searchQuery: string; // FIXME: create search component and remove this

  types: RoleType[];

  isViewModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  saving: boolean;

  newView: {view: {name: string, viewId: number, isEnabled: boolean, roleTypeId?: string}, type: 'page' | 'template'} = {
    view: {
      name: null,
      viewId: null,
      isEnabled: null,
      roleTypeId: null
    },
    type: null
  }
  viewToEdit: Page | Template;
  viewToDelete: {view: Page | Template, type: 'page' | 'template'};

  mode: 'add' | 'edit';

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
    private updateManager: UpdateService
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getViewsInfo();
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getViewsInfo(): void {
    this.loading = true;
    this.api.getViewsList(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(res => {
        this.allPages = res.pages.sort((a, b) => a.name.localeCompare(b.name));
        this.allViewTemplates = res.templates.sort((a, b) => a.name.localeCompare(b.name));
        this.allGlobalTemplates = res.globals.sort((a, b) => a.name.localeCompare(b.name));

        this.types = res.types;

        this.reduceList();

      }, error => {
        if (error.status === 404) {
          ErrorService.set(
            error.status === 404 ? error.error.error + ': module \'views\' is disabled' : error,
            error.status === 404 ? () => {
              this.router.navigate(['/']);
            } : null
          );
        }
      });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Search ------------------ ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduceForPages.search(this.allPages, query);
    this.reduceForViewTemplates.search(this.allViewTemplates, query);
    this.reduceForGlobalTemplates.search(this.allGlobalTemplates, query);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  saveView(): void {
    this.saving = true;

    if (this.mode === 'add') {
      if (this.newView.type === 'page') {
        this.api.createPage(this.courseID, this.newView.view)
          .pipe( finalize(() => {
            this.saving = false;
            this.isViewModalOpen = false;
            this.clearObject(this.newView.view);
            this.newView.type = null;
          }) )
          .subscribe(res => {
            this.getViewsInfo();
            this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
          }, error => ErrorService.set(error))

      } else if(this.newView.type === 'template') {
        this.api.createTemplate(this.courseID, this.newView.view)
          .pipe( finalize(() => {
            this.saving = false;
            this.isViewModalOpen = false;
            this.clearObject(this.newView.view);
            this.newView.type = null;
          }) )
          .subscribe(res => {
            this.getViewsInfo();
          }, error => ErrorService.set(error))
      }

    } else if (this.mode === 'edit') {
      if (this.newView.type === 'page') {
        this.viewToEdit = this.viewToEdit as Page;
        this.viewToEdit.name = this.newView.view.name;
        this.viewToEdit.viewId = this.newView.view.viewId;
        this.viewToEdit.isEnabled = this.newView.view.isEnabled;

        this.api.editPage(this.courseID, this.viewToEdit)
          .pipe( finalize(() => {
            this.saving = false;
            this.isViewModalOpen = false;
            this.clearObject(this.newView.view);
            this.newView.type = null;
          }) )
          .subscribe(res => {
              this.getViewsInfo();
              this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
            }, error => ErrorService.set(error))

      } else if (this.newView.type === 'template') {
        this.viewToEdit = this.viewToEdit as Template;
        this.viewToEdit.name = this.newView.view.name;
        this.viewToEdit.viewId = this.newView.view.viewId;
        this.viewToEdit.roleTypeId = this.newView.view.roleTypeId;

        this.api.editTemplate(this.courseID, this.viewToEdit)
          .pipe( finalize(() => {
            this.saving = false;
            this.isViewModalOpen = false;
            this.clearObject(this.newView.view);
            this.newView.type = null;
          }) )
          .subscribe(res => {
            this.getViewsInfo();
          }, error => ErrorService.set(error))
      }
    }
  }

  editViewInfo(view: Page | Template, type: 'page' | 'template'): void {
    this.viewToEdit = view;
    this.newView.type = type;

    if (type === 'page') {
      view = view as Page;
      this.newView.view.name = view.name;
      this.newView.view.viewId = view.viewId;
      this.newView.view.isEnabled = view.isEnabled;

    } else if (type === 'template') {
      view = view as Template;
      this.newView.view.name = view.name;
      this.newView.view.roleTypeId = view.roleTypeId;
    }

    this.mode = 'edit';
    this.isViewModalOpen = true;
  }

  deleteView(): void {
    this.saving = true;
    if (this.viewToDelete.type === 'page') {
      this.api.deletePage(this.courseID, this.viewToDelete.view as Page)
        .pipe( finalize(() => {
          this.saving = false;
          this.isDeleteVerificationModalOpen = false;
          this.viewToDelete = null;
        }) )
        .subscribe(
          res => {
            this.getViewsInfo();
            this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
          },
          error => ErrorService.set(error)
        )

    } else if (this.viewToDelete.type === 'template') {
      this.api.deleteTemplate(this.courseID, this.viewToDelete.view as Template)
        .pipe( finalize(() => {
          this.saving = false;
          this.isDeleteVerificationModalOpen = false;
          this.viewToDelete = null;
        }) )
        .subscribe(
          res => {
            this.getViewsInfo();
          },
          error => ErrorService.set(error)
        )
    }
  }

  globalize(template: Template): void {
    this.saving = true;
    this.api.globalizeTemplate(this.courseID, template)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(res => {
        this.getViewsInfo();
      }, error => ErrorService.set(error));
  }

  useGlobal(template: Template): void {
    // TODO: update from GameCourse v1
    ErrorService.set('Error: This action still needs to be updated to the current version. (views.component.ts::useGlobal(template))')
  }

  exportTemplate(template: Template): void {
    // TODO: update from GameCourse v1
    ErrorService.set('Error: This action still needs to be updated to the current version. (views.component.ts::exportTemplate(template)')
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToSubmit() {
    let isValid = function (text) {
      return (text != "" && text != undefined)
    }

    if (this.newView.type === 'page') {
      return isValid(this.newView.view.name) && isValid(this.newView.view.viewId);

    } else if (this.newView.type === 'template') {
      return isValid(this.newView.view.name) && isValid(this.newView.view.roleTypeId);
    }

    return false;
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

  get Template(): typeof Template {
    return Template;
  }

}
