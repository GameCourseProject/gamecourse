import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-views',
  templateUrl: './views.component.html',
  styleUrls: ['./views.component.scss']
})
export class ViewsComponent implements OnInit {

  sections = ["#views", "#view-parts", "#expression-language", "#part-configuration"];

  constructor() { }

  ngOnInit(): void {
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
