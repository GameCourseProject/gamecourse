import { Component, HostListener, OnInit } from '@angular/core';
import { Router } from "@angular/router";

import { ApiHttpService } from "../../../_services/api/api-http.service";

import * as moment from 'moment';
import { Moment } from "moment";

@Component({
  selector: 'app-autogame-toast',
  templateUrl: './auto-game-toast.component.html'
})
export class AutoGameToastComponent implements OnInit {

  loading: boolean = true;
  lastRun: Moment;

  hide: boolean = false;
  toastPos: DOMRect;

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

  now(): Moment {
    return moment();
  }


  @HostListener('window:mousemove', ['$event'])
  onMouseMove(event: any) {
    // Hide toast when mouse inside
    // NOTE: allows interaction with any elements behind it

    const toast = document.getElementById('autogame-toast');
    if (toast) this.toastPos = toast.getBoundingClientRect();

    if (this.toastPos) {
      const mousePos = { x: event.clientX, y: event.clientY };
      this.hide = mousePos.x >= this.toastPos.x && mousePos.x <= this.toastPos.right && mousePos.y >= this.toastPos.y &&
        mousePos.y <= this.toastPos.bottom;
    }
  }
}
