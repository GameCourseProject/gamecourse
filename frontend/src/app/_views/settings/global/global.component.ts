import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {ErrorService} from "../../../_services/error.service";

@Component({
  selector: 'app-global',
  templateUrl: './global.component.html',
  styleUrls: ['./global.component.scss']
})
export class GlobalComponent implements OnInit {

  loading: boolean;

  default: string;
  themes: {name: string, preview: boolean}[];

  constructor(
    private api: ApiHttpService
  ) { }

  get API_ENDPOINT(): string {
    return ApiEndpointsService.API_ENDPOINT;
  }

  ngOnInit(): void {
    this.loading = true;
    this.api.getSettingsGlobal()
      .subscribe(global => {
        this.default = global.theme;
        this.themes = global.themes;
        this.loading = false;
      },
        error => ErrorService.set(error));
  }

}
