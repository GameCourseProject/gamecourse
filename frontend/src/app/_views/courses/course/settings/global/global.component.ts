import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {Subject} from "rxjs";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../_services/error.service";
import {ApiEndpointsService} from "../../../../../_services/api/api-endpoints.service";
import {Page} from "../../../../../_domain/pages & templates/page";
import {finalize} from "rxjs/operators";

@Component({
  selector: 'app-global',
  templateUrl: './global.component.html',
  styleUrls: ['./global.component.scss']
})
export class GlobalComponent implements OnInit {

  loading: boolean;

  info: { id: number, name: string, activeUsers: number, awards: number, participations: number };
  activePages: Page[];

  isViewDBModalOpen;
  isSuccessModalOpen;
  saving: boolean;

  style: {contents: string, url: string};
  hasStyleFile: boolean;
  styleLoaded: Subject<void> = new Subject<void>();
  styleInput: string = '';

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.getCourseInfo(parseInt(params.id));
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getCourseInfo(courseID: number): void {
    this.api.getCourseGlobal(courseID)
      .subscribe(
        info => {
          this.info = Object.assign({id: courseID}, info);

          this.api.getCourseWithInfo(courseID)
            .subscribe(courseInfo => {
              this.activePages = courseInfo.activePages;
                this.getStyleFile(courseID);
              },
              error => ErrorService.set(error));
        },
        error => ErrorService.set(error)
      )
  }

  getStyleFile(courseID: number): void {
    this.api.getCourseStyleFile(courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        data => {
          this.style = {contents: data.styleFile, url: data.url};
          this.hasStyleFile = !this.style.contents.isEmpty();
          if (this.hasStyleFile) setTimeout(() => this.styleLoaded.next(), 0);

          // Append style to global styles
          $('#css-file').remove();
          if (this.style.url && this.style.contents !== '')
            $('head').append('<link id="css-file" rel="stylesheet" type="text/css" href="' + ApiEndpointsService.API_ENDPOINT + '/' + data.url + '">');
        },
        error => ErrorService.set(error)
      );
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  showDatabase(table: string): void {
    // TODO: update from GameCourse v1
    this.api.getTableData(this.info.id, table)
      .subscribe(
        res => ErrorService.set('Error: This action still needs to be updated to the current version. (global.component.ts::showDatabase(table))'),
        error => ErrorService.set(error)
      )
  }

  move(page: Page, direction: number): void {
    // TODO: update from GameCourse v1
    ErrorService.set('Error: This action still needs to be updated to the current version. (global.component.ts::move(item, direction))');
  }

  createStyleFile(): void {
    this.saving = true;
    this.api.createCourseStyleFile(this.info.id)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(url => {
        this.style = {contents: '', url: url};
        this.hasStyleFile = true;
        setTimeout(() => this.styleLoaded.next(), 0);
      }, error => ErrorService.set(error));
  }

  saveStyleFile(): void {
    this.saving = true;
    const updatedStyle = this.styleInput;

    this.api.updateCourseStyleFile(this.info.id, updatedStyle)
      .pipe( finalize(() => this.saving = false) )
      .subscribe(url => {
        this.isSuccessModalOpen = true;
        this.style = {contents: updatedStyle, url: url};
        this.hasStyleFile = !this.style.contents.isEmpty();

        // Append style to global styles
        $('#css-file').remove();
        if (this.style.url && this.style.contents !== '')
          $('head').append('<link id="css-file" rel="stylesheet" type="text/css" href="' + ApiEndpointsService.API_ENDPOINT + '/' + url + '">');
      }, error => ErrorService.set(error));
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  orderBySeqId(): Page[] {
    if (this.activePages.length === 0) return [];
    return this.activePages.sort((a, b) => a.seqId - b.seqId)
  }

}
