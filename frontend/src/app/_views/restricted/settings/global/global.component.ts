import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../_services/api/api-endpoints.service";
import {finalize} from "rxjs/operators";

@Component({
  selector: 'app-global',
  templateUrl: './global.component.html',
  styleUrls: ['./global.component.scss']
})
export class GlobalComponent implements OnInit {

  loading: boolean = true;

  default: string;
  themes: string[];

  constructor(
    private api: ApiHttpService
  ) { }

  get API_ENDPOINT(): string {
    return ApiEndpointsService.API_ENDPOINT;
  }

  ngOnInit(): void {
    this.api.getThemes()
      .pipe( finalize(() => this.loading = false) )
      .subscribe(res => {
        this.default = res.current;
        this.themes = res.themes;
      });
  }

}
