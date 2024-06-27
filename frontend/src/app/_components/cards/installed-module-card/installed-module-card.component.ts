import {AfterViewInit, Component, Input, OnInit} from '@angular/core';
import { Module } from 'src/app/_domain/modules/module';

@Component({
  selector: 'app-installed-module-card',
  templateUrl: './installed-module-card.component.html'
})
export class InstalledModuleCardComponent implements OnInit, AfterViewInit {

  @Input() module: Module;

  svgIcon: string;

  constructor() {}

  ngOnInit(): void {
    this.svgIcon = this.module.icon.svg.replace('<svg', '<svg id="' + this.module.id + '-icon"');
  }

  ngAfterViewInit() {
    const svg = document.getElementById(this.module.id + '-icon');
    svg.style.width = '2rem';
    svg.style.height = '2rem';
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isCompatible(): boolean {
    return this.module.compatibility.project && this.module.compatibility.api;
  }

  getIncompatibleString(): string {
    if (!this.module.compatibility.project && !this.module.compatibility.api)
      return "Project & API";
    if (!this.module.compatibility.project)
      return "Project";
    else return "API";
  }

}
