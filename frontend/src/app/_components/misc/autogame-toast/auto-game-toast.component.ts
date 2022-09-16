import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {Moment} from "moment";
import {Router} from "@angular/router";

@Component({
  selector: 'app-autogame-toast',
  templateUrl: './auto-game-toast.component.html'
})
export class AutoGameToastComponent implements OnInit {

  loading: boolean = true;
  lastRun: Moment;

  constructor(
    private api: ApiHttpService,
    private router: Router
  ) { }

  async ngOnInit(): Promise<void> {
    this.lastRun = await this.api.getAutoGameLastRun(this.getCourseIDFromURL()).toPromise();
    this.loading = false;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getCourseIDFromURL(): number {
    const urlParts = this.router.url.substr(1).split('/');
    if (urlParts.includes('courses') && urlParts.length >= 2) return parseInt(urlParts[1]);
    else return null;
  }

}
