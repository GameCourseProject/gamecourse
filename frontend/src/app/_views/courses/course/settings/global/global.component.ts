import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../_services/api/api-http.service";

@Component({
  selector: 'app-global',
  templateUrl: './global.component.html',
  styleUrls: ['./global.component.scss']
})
export class GlobalComponent implements OnInit {

  loading: boolean;

  constructor(
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
  }

}
