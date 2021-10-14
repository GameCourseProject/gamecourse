import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {ErrorService} from "../../../../_services/error.service";
import {View} from "../../../../_domain/views/view";

@Component({
  selector: 'app-page',
  templateUrl: './page.component.html',
  styleUrls: ['./page.component.scss']
})
export class PageComponent implements OnInit {

  courseID: number;
  pageID: number;
  pageView: View;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);

      this.route.params.subscribe(params => {
        this.pageID = parseInt(params.id);
        this.getPage();

      }).unsubscribe();
    }).unsubscribe();
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getPage(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
          this.api.getView(this.courseID, this.pageID, 'page', user.id)
            .subscribe(
              view => this.pageView = view,
              error => ErrorService.set(error)
            );
      }, error => ErrorService.set(error));
  }

}
