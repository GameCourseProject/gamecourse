import {AfterViewInit, Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {Subject} from "rxjs";

import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../_services/error.service";
import {ApiEndpointsService} from "../../../../../_services/api/api-endpoints.service";

import * as CodeMirror from 'codemirror';
import 'codemirror/mode/css/css';
import 'codemirror/addon/hint/show-hint';
import 'codemirror/addon/hint/css-hint';
import 'codemirror/addon/display/placeholder';

@Component({
  selector: 'app-global',
  templateUrl: './global.component.html',
  styleUrls: ['./global.component.scss']
})
export class GlobalComponent implements OnInit, AfterViewInit {

  loading: boolean;

  info: { id: number, name: string, activeUsers: number, awards: number, participations: number };
  navigation: {name: string, seqId: number}[];
  viewsModuleEnabled: boolean;

  isViewDBModalOpen;
  isSuccessModalOpen;
  saving: boolean;

  style: {contents: string, url: string};
  styleLoaded: Subject<void> = new Subject<void>();
  hasStyleFile: boolean;
  cssCodeMirror;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.params.subscribe(params => {
      this.getCourseInfo(params.id);
    });
  }

  ngAfterViewInit() {
    this.styleLoaded.subscribe(() => {
      setTimeout(() => this.initCodeMirror(), 0)
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
                if (nav.text === 'Views')
                  this.viewsModuleEnabled = true;

                else if (nav.text !== 'Users' && nav.text !== 'Course Settings')
                  this.navigation.push({ name: nav.text, seqId: parseInt(nav.seqId) });
              }
              if (this.viewsModuleEnabled ) this.getStyleFile(courseID);
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
          this.hasStyleFile = !!this.style.contents;
          if (this.hasStyleFile) this.styleLoaded.next();

          // Append style to global styles
          $('#css-file').remove();
          if (this.style.url && this.style.contents !== '')
            $('head').append('<link id="css-file" rel="stylesheet" type="text/css" href="' + ApiEndpointsService.API_ENDPOINT + '/' + data.url + '">');
        },
        error => ErrorService.set(error),
        () => this.loading = false
      );
  }

  initCodeMirror(): void {
    const options = {
      lineNumbers: true,
      styleActiveLine: true,
      mode: "css",
      value: this.style.contents || '',
      autohint: true,
      lineWrapping: true,
      theme: "mdn-like"
    };

    const textarea = $('#style-file')[0] as HTMLTextAreaElement;
    this.cssCodeMirror = CodeMirror.fromTextArea(textarea, options);

    this.cssCodeMirror.on("keyup", function (cm, event) {
      cm.showHint(CodeMirror.hint.css);
    });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  showDatabase(table: string): void {
    // TODO
    this.api.getTableData(this.info.id, table)
      .subscribe(
        res => {
          console.log(res);
          ErrorService.set('showDatabase() needs to be implemented');
        },
        error => ErrorService.set(error)
      )
  }

  move(item: {name: string, seqId: number}, direction: number): void {
    // TODO
    ErrorService.set('move() needs to be implemented');
  }

  createStyleFile(): void {
    this.saving = true;
    this.api.createStyleFile(this.info.id)
      .subscribe(url => {
        this.style = {contents: '', url: url};
        this.hasStyleFile = true;
        this.styleLoaded.next();
      }, error => ErrorService.set(error),
        () => this.saving = false);
  }

  saveStyleFile(): void {
    this.saving = true;
    const updatedStyle = this.cssCodeMirror.getValue();

    this.api.updateStyleFile(this.info.id, updatedStyle)
      .subscribe(url => {
        this.isSuccessModalOpen = true;
        this.style = {contents: updatedStyle, url: url};
        this.hasStyleFile = !!this.style.contents;

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
