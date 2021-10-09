import { Component, OnInit } from '@angular/core';
import {Router} from "@angular/router";

@Component({
  selector: 'app-course-settings',
  templateUrl: './settings.component.html',
  styleUrls: ['./settings.component.scss']
})
export class SettingsComponent implements OnInit {

  isViewEditor: boolean;

  constructor(
    private router: Router
  ) { }

  ngOnInit(): void {
    const url = this.router.url;
    this.isViewEditor = url.includes('views') && url.includes('editor');
  }

}
