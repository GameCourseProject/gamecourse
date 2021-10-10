import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ErrorService} from "../../../../_services/error.service";

@Component({
  selector: 'app-page',
  templateUrl: './page.component.html',
  styleUrls: ['./page.component.scss']
})
export class PageComponent implements OnInit {

  courseID: number;
  pageID: number;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(params => {
      this.courseID = params.id;

      this.route.params.subscribe(params => {
        this.pageID = params.id;
        this.getPage();

      }).unsubscribe();
    }).unsubscribe();
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getPage(): void {
    this.api.getView(this.courseID, this.pageID)
      .subscribe(
        res => console.log(res),
        error => ErrorService.set(error)
      )
  }

}
