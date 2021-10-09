import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {Subject} from "rxjs";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../_services/error.service";
import {ApiEndpointsService} from "../../../../../_services/api/api-endpoints.service";

@Component({
  selector: 'app-global',
  templateUrl: './global.component.html',
  styleUrls: ['./global.component.scss']
})
export class GlobalComponent implements OnInit {

  loading: boolean;

  info: { id: number, name: string, activeUsers: number, awards: number, participations: number };
  navigation: {name: string, seqId: number}[];
  viewsModuleEnabled: boolean;

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
      this.getCourseInfo(params.id);
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

          this.api.getCourseInfo(courseID)
            .subscribe(courseInfo => {
              this.navigation = [];
              for (const nav of courseInfo.navigation) {
                if (nav.text !== 'Users' && nav.text !== 'Course Settings')
                  this.navigation.push({ name: nav.text, seqId: parseInt(nav.seqId) });
              }

              if (courseInfo.settings.find(el => el.text === 'Views'))
                this.viewsModuleEnabled = true;

              if (this.viewsModuleEnabled) this.getStyleFile(courseID);
              else this.loading = false;
              },
              error => ErrorService.set(error));
        },
        error => ErrorService.set(error)
      )
  }

  getStyleFile(courseID: number): void {
    this.api.getStyleFile(courseID)
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
        error => ErrorService.set(error),
        () => this.loading = false
      );
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  showDatabase(table: string): void {
    // TODO: update from GameCourse v1
    this.api.getTableData(this.info.id, table)
      .subscribe(
        res => ErrorService.set('This action still needs to be updated to the current version. Action: showDatabase()'),
        error => ErrorService.set(error)
      )
  }

  move(item: {name: string, seqId: number}, direction: number): void {
    // TODO: update from GameCourse v1
    ErrorService.set('This action still needs to be updated to the current version. Action: move()');
  }

  createStyleFile(): void {
    this.saving = true;
    this.api.createStyleFile(this.info.id)
      .subscribe(url => {
        this.style = {contents: '', url: url};
        this.hasStyleFile = true;
        setTimeout(() => this.styleLoaded.next(), 0);
      }, error => ErrorService.set(error),
        () => this.saving = false);
  }

  saveStyleFile(): void {
    this.saving = true;
    const updatedStyle = this.styleInput;

    this.api.updateStyleFile(this.info.id, updatedStyle)
      .subscribe(url => {
        this.isSuccessModalOpen = true;
        this.style = {contents: updatedStyle, url: url};
        this.hasStyleFile = !this.style.contents.isEmpty();

        // Append style to global styles
        $('#css-file').remove();
        if (this.style.url && this.style.contents !== '')
          $('head').append('<link id="css-file" rel="stylesheet" type="text/css" href="' + ApiEndpointsService.API_ENDPOINT + '/' + url + '">');
      },
        error => ErrorService.set(error),
        () => this.saving = false)
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  orderBySeqId() {
    if (this.navigation.length === 0) return [];
    return this.navigation.sort((a, b) => a.seqId - b.seqId)
  }

}
