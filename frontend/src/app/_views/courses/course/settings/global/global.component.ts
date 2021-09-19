import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";
import {ErrorService} from "../../../../../_services/error.service";
import {ActivatedRoute} from "@angular/router";

declare let CodeMirror;

@Component({
  selector: 'app-global',
  templateUrl: './global.component.html',
  styleUrls: ['./global.component.scss']
})
export class GlobalComponent implements OnInit {

  loading: boolean;

  info: { id: number, name: string, activeUsers: number, awards: number, participations: number };
  navigation: {name: string, seqId: number}[];

  isViewDBModalOpen;
  saving: boolean;

  styleFileContent: string;
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
              },
              error => ErrorService.set(error),
              () => this.loading = false);
        },
        error => ErrorService.set(error)
      )
  }

  initCodeMirror(): void {
    this.cssCodeMirror = CodeMirror.fromTextArea(document.getElementById("style-file"), {
      lineNumbers: true, styleActiveLine: true, mode: "css", value: this.styleFileContent || '', autohint: true,
      lineWrapping: true, theme: "mdn-like"
    });

    this.cssCodeMirror.on("keyup", function (cm, event) {
      console.log('key up');
      // cm.showHint(CodeMirror.hint.css);
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


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  orderBySeqId() {
    if (this.navigation.length === 0) return [];
    return this.navigation.sort((a, b) => a.seqId - b.seqId)
  }

}
