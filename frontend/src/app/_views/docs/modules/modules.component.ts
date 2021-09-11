import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {

  sections = ["#create","#init","#config","#resources","#data"]

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
