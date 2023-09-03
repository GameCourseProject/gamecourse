import {Component, OnInit} from "@angular/core";
import {ActivatedRoute, Router} from "@angular/router";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {Course} from "../../../../../../../_domain/courses/course";
import { Page } from "src/app/_domain/views/pages/page";

import {initPageToManage, PageManageData} from "../views/views.component";

@Component({
  selector: 'app-views-editor',
  templateUrl: './views-editor.component.html'
})
export class ViewsEditorComponent implements OnInit {

  loading = {
    page: true,
    action: false,
    table: false
  };

  course: Course;                 // Specific course in which page exists
  page: Page;                     // page where information will be saved
  pageToManage: PageManageData;   // Manage data

  // Preview mode selected to render
  previewMode: 'raw' | 'mock' | 'real' = 'raw';

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);

      this.route.params.subscribe(async childParams => {
        const segment = this.route.snapshot.url[this.route.snapshot.url.length - 1].path;

        console.log(segment);
        if (segment === 'new-page') {
          // Prepare for creation
          this.pageToManage = initPageToManage(courseID);
          console.log(this.pageToManage);
        } else {
          await this.getPage(parseInt(segment));
        }

      })

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

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getIcon(mode: string): string {
    if (mode === this.previewMode) return 'tabler-check';
    else return '';
  }

}
