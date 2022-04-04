import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {finalize} from "rxjs/operators";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../../_services/error.service";
import {UpdateService, UpdateType} from "../../../../../../_services/update.service";

import {Page} from "../../../../../../_domain/pages & templates/page";
import {Template} from "../../../../../../_domain/pages & templates/template";
import {RoleType} from "../../../../../../_domain/roles/role-type";
import {Reduce} from "../../../../../../_utils/lists/reduce";
import {exists} from "../../../../../../_utils/misc/misc";
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

  isPageModalOpen: boolean;
  isTemplateModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;

  pageSelected: Page;
  templateSelected: Template;

  importedFile: File;

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

  get Page(): typeof Page {
    return Page;
  }

  get Template(): typeof Template {
    return Template;
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
  /*** ------------------- Pages ------------------- ***/
  /*** --------------------------------------------- ***/

  savePage(page: Page): void {
    this.saving = true;

    if (this.mode === 'add') {
      this.api.createPage(this.courseID, page)
        .pipe( finalize(() => {
          this.saving = false;
          this.isPageModalOpen = false;
          this.pageSelected = null;
        }) )
        .subscribe(res => {
          this.getViewsInfo();
          this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
        }, error => ErrorService.set(error))

    } else if (this.mode === 'edit') {
      this.api.editPage(this.courseID, page)
        .pipe( finalize(() => {
          this.saving = false;
          this.isPageModalOpen = false;
          this.pageSelected = null;
        }) )
        .subscribe(res => {
          this.getViewsInfo();
          this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
        }, error => ErrorService.set(error))
    }
  }

  editPage(page: Page): void {
    this.pageSelected = page;
    this.mode = 'edit';
    this.isPageModalOpen = true;
  }

  deletePage(): void {
    this.saving = true;
    this.api.deletePage(this.courseID, this.pageSelected)
      .pipe( finalize(() => {
        this.saving = false;
        this.clear();
        this.isDeleteVerificationModalOpen = false;
      }) )
      .subscribe(
        res => {
          this.getViewsInfo();
          this.updateManager.triggerUpdate(UpdateType.ACTIVE_PAGES);
        },
        error => ErrorService.set(error)
      )
  }


  /*** --------------------------------------------- ***/
  /*** ----------------- Templates ----------------- ***/
  /*** --------------------------------------------- ***/

  saveTemplate(template: Template): void {
    this.saving = true;

    if (this.mode === 'add') {
      this.api.createTemplate(this.courseID, template)
        .pipe( finalize(() => {
          this.saving = false;
          this.isTemplateModalOpen = false;
          this.templateSelected = null;
        }) )
        .subscribe(res => {
          this.getViewsInfo();
        }, error => ErrorService.set(error))

    } else if (this.mode === 'edit') {
      this.api.editTemplateBasicInfo(this.courseID, template)
        .pipe( finalize(() => {
          this.saving = false;
          this.isTemplateModalOpen = false;
          this.templateSelected = null;
        }) )
        .subscribe(res => {
          this.getViewsInfo();
        }, error => ErrorService.set(error))
    }
  }

  editTemplate(template: Template): void {
    this.templateSelected = template;
    this.mode = 'edit';
    this.isTemplateModalOpen = true;
  }

  deleteTemplate(): void {
    this.saving = true;

    // Check if there's a page binding to this template
    let canDelete = true;
    this.allPages.forEach(page => {
      if (page.viewId === this.templateSelected.viewId) {
        canDelete = false;
        ErrorService.set('Page \'' + page.name + '\' uses this template. Please delete this page first and try again.');
      }
    });

    if (canDelete) {
      this.api.deleteTemplate(this.courseID, this.templateSelected)
        .pipe( finalize(() => {
          this.saving = false;
          this.clear();
          this.isDeleteVerificationModalOpen = false;
        }) )
        .subscribe(
          res => {
            this.getViewsInfo();
          },
          error => ErrorService.set(error)
        )

    } else {
      this.saving = false;
      this.clear();
      this.isDeleteVerificationModalOpen = false;
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

  importTemplate(): void {
    this.saving = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedTemplate = reader.result;
      this.api.importTemplate(this.courseID, importedTemplate)
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.saving = false;
        }) )
        .subscribe(
          () => this.getViewsInfo(),
          error => ErrorService.set(error)
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportTemplate(template: Template): void {
    this.saving = true;
    this.api.exportTemplate(this.courseID, template.id)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(res => {
        DownloadManager.downloadAsText('Template - ' + template.name, res)
      }, error => ErrorService.set(error))
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
  }

  isReadyToSubmit(type: 'page' | 'template') {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    if (type === 'page') {
      return isValid(this.pageSelected.name) && isValid(this.pageSelected.viewId);

    } else {
      return isValid(this.templateSelected.name) && isValid(this.templateSelected.roleType);
    }
  }

  getEmptyPage(): Page {
    return new Page(null, null, null, null, null, null, null, null);
  }

  getEmptyTemplate(): Template {
    return new Template(null, null, null, null, null, null);
  }

  clear(): void {
    this.pageSelected = null;
    this.templateSelected = null;
  }

}
