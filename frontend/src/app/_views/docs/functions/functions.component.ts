import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../_services/api/api-http.service";

@Component({
  selector: 'app-functions',
  templateUrl: './functions.component.html',
  styleUrls: ['./functions.component.scss']
})
export class FunctionsComponent implements OnInit {

  constructor(
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.api.getSchema(); // FIXME: finish up when can enable modules
  }

}
