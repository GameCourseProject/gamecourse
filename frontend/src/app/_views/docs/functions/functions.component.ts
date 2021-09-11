import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../_services/api/api-http.service";

@Component({
  selector: 'app-functions',
  templateUrl: './functions.component.html',
  styleUrls: ['./functions.component.scss']
})
export class FunctionsComponent implements OnInit {

  sections: string[] = [];

  constructor(
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.api.getSchema(); // FIXME: finish up when can enable modules
  }

  showContent(tab: { id: string, index: number}): void {
    // define the selected tab
    $('.tab.selected').removeClass("selected");
    $('#' + tab.id).addClass("selected");

    // define the visible section
    $('.section.visible').removeClass("visible");
    $(this.sections[tab.index]).addClass("visible");
  }

}
