import {Component, OnInit} from "@angular/core";
import {ActivatedRoute, Router} from "@angular/router";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {Course} from "../../../../../../../_domain/courses/course";
import {Page} from "src/app/_domain/views/pages/page";

import {initPageToManage, PageManageData} from "../views/views.component";
import {ViewType} from "../../../../../../../_domain/views/view-types/view-type";

@Component({
  selector: 'app-views-editor',
  templateUrl: './views-editor.component.html'
})
export class ViewsEditorComponent implements OnInit {

  loading = {
    page: true,
    action: false
  };

  course: Course;                 // Specific course in which page exists
  page: Page;                     // page where information will be saved
  pageToManage: PageManageData;   // Manage data

  previewMode: 'raw' | 'mock' | 'real' = 'raw';   // Preview mode selected to render
  optionSelected: 'add-component' | 'add-section' | 'choose-layout' | 'rearrange';

  options: {icon: string, description: string, color: 'primary' | null, subMenu: any}[]; // TODO (for later use)

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router,
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      this.setOptions();

      this.route.params.subscribe(async childParams => {
        const segment = this.route.snapshot.url[this.route.snapshot.url.length - 1].path;

        if (segment === 'new-page') {
          // Prepare for creation
          this.pageToManage = initPageToManage(courseID);
        } else {
          await this.getPage(parseInt(segment));
        }

      });

      this.loading.page = false;

    })
  }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getPage(pageID: number): Promise<void> {
    this.page = await this.api.getPageById(pageID).toPromise();
  }

  setOptions(){
    this.options =  [{icon: 'jam-plus-circle', description: 'Add component', color: null, subMenu:
      [ViewType.BLOCK, ViewType.BUTTON, ViewType.CHART, ViewType.COLLAPSE, ViewType.ICON, ViewType.IMAGE, ViewType.TABLE, ViewType.TEXT]
    }];
  }

  /*** ------------------------------------------------ ***/
  /*** -------------------- Actions ------------------- ***/
  /*** ------------------------------------------------ ***/

  selectOption(icon: 'add-component' | 'add-section' | 'choose-layout' | 'rearrange') {
    if (this.optionSelected !== icon) this.optionSelected = icon;
    else this.optionSelected = null;
  }

  async closeEditor(){
    await this.router.navigate(['pages'], {relativeTo: this.route.parent});
  }



  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getCourseColor(): string {
    return this.course.color;
  }

  getIcon(mode: string): string {
    if (mode === this.previewMode) return 'tabler-check';
    else return '';
  }

}
