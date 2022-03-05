import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../_services/api/api-http.service";
import {Moment} from "moment";
import {Router} from "@angular/router";
import {ErrorService} from "../../_services/error.service";
import {finalize} from "rxjs/operators";

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
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        date => {
          this.lastRun = date;
          console.log(this.lastRun)
        },
        error => ErrorService.set(error)
      );
  }

  getCourseId(): number {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts[0] === 'courses')
      return parseInt(urlParts[1]);
    ErrorService.set('Error: Couldn\'t get course ID. (footer.component.ts::getCourseId())');
    return null;
  }

}
