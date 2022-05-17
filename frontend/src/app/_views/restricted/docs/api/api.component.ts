import { Component, OnInit } from '@angular/core';

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ApiEndpointsService} from "../../../../_services/api/api-endpoints.service";

import SwaggerUI from 'swagger-ui';

@Component({
  selector: 'app-api',
  templateUrl: './api.component.html',
  styleUrls: ['./api.component.scss']
})
export class ApiComponent implements OnInit {

  constructor(
    private api: ApiHttpService,
    private apiEndpoint: ApiEndpointsService
  ) { }

  ngOnInit(): void {
    SwaggerUI({
      domNode: document.getElementById('api'),
      url: this.apiEndpoint.createUrlWithQueryParameters('docs/')
    });
  }
}
