import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {Subject} from "rxjs";

import {ApiHttpService} from "../../../../../../_services/api/api-http.service";

import {Course} from "../../../../../../_domain/courses/course";
import {CourseUser} from "../../../../../../_domain/users/course-user";
import {Page} from "../../../../../../_domain/views/pages/page";
import {ResourceManager} from "../../../../../../_utils/resources/resource-manager";
import {DomSanitizer} from "@angular/platform-browser";

@Component({
  selector: 'app-global',
  templateUrl: './global.component.html',
  styleUrls: ['./global.component.scss']
})
export class GlobalComponent implements OnInit {

  loading: boolean = true;

  course: Course;
  activeStudents: CourseUser[];
  activePages: Page[];

  isViewDBModalOpen;
  isSuccessModalOpen;
  saving: boolean;

  originalStyles: string;
  styles: {contents: string, path: string};
  stylesLoaded: Subject<void> = new Subject<void>();

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      const courseID = parseInt(params.id);
      await this.getCourse(courseID);
      await this.getActiveStudents(courseID);
      await this.getActivePages(courseID);
      await this.getCourseStyles(courseID);
      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** ---------------- Information ---------------- ***/
  /*** --------------------------------------------- ***/

  async getCourse(courseID: number): Promise<void> {
    this.course = await this.api.getCourseById(courseID).toPromise();
  }

  async getActiveStudents(courseID: number): Promise<void> {
    this.activeStudents = await this.api.getCourseUsersWithRole(courseID, "Student", true).toPromise();
  }

  showDatabase(table: string): void {
    // TODO: update from GameCourse v1
  }


  /*** --------------------------------------------- ***/
  /*** ----------------- Navigation ---------------- ***/
  /*** --------------------------------------------- ***/

  async getActivePages(courseID: number): Promise<void> {
    this.activePages = []; // FIXME
  }

  move(page: Page, direction: number): void {
    // FIXME
  }

  orderBySeqId(): Page[] {
    return this.activePages.sort((a, b) => a.position - b.position)
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Styling ------------------ ***/
  /*** --------------------------------------------- ***/

  async getCourseStyles(courseID: number): Promise<void> {
    this.styles = await this.api.getCourseStyles(courseID).toPromise() ?? {contents: '', path: null};
    this.originalStyles = this.styles.contents;
    setTimeout(() => this.stylesLoaded.next(), 0);
    this.loadStyles(courseID);
  }

  async updateCourseStyles(courseID: number): Promise<void> {
    this.saving = true;
    await this.api.updateCourseStyles(courseID, this.styles.contents).toPromise();
    await this.getCourseStyles(courseID);
    this.isSuccessModalOpen = true;
    this.saving = false;
  }

  loadStyles(courseID): void {
    const mainID = courseID + '-main-styling';

    // Remove previous styles
    const style = document.getElementById(mainID);
    if (style) style.remove();

    // Append new styles
    if (!!this.styles && !this.styles.contents.isEmpty()) {
      // Prevent unwanted browser caching
      const path = new ResourceManager(this.sanitizer);
      path.set(this.styles.path);

      const head = document.getElementsByTagName('head')[0];
      const newStyle = document.createElement('link');
      newStyle.id = mainID;
      newStyle.rel = 'stylesheet';
      newStyle.href = path.get('URL').toString();
      head.appendChild(newStyle);
    }
  }
}
