import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../_services/api/api-http.service";
import {Moment} from "moment";
import {Router} from "@angular/router";
import {ErrorService} from "../../_services/error.service";

@Component({
  selector: 'app-footer',
  templateUrl: './footer.component.html',
  styleUrls: ['./footer.component.scss']
})
export class FooterComponent implements OnInit {

  loading: boolean;
  lastRun: Moment;

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.api.getRulesSystemLastRun(this.getCourseId())
      .subscribe(
        date => this.lastRun = date,
        error => ErrorService.set(error),
        () => this.loading = false
        );
  }

  getCourseId(): number {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts[0] === 'courses')
      return parseInt(urlParts[1]);
    ErrorService.set('Error while getting course ID: getCourseId() -> footer.component');
    return null;
  }

}