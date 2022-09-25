import {AfterViewInit, Component, Input, OnInit} from '@angular/core';
import { Module } from 'src/app/_domain/modules/module';

@Component({
  selector: 'app-module-card',
  templateUrl: './module-card.component.html'
})
export class ModuleCardComponent implements OnInit, AfterViewInit {

  @Input() module: Module;

  svgIcon: string;
  constructor() {}

  ngOnInit(): void {
    this.svgIcon = this.module.icon.replace('<svg', '<svg id="' + this.module.id + '-icon"');
  }

  ngAfterViewInit() {
    const svg = document.getElementById(this.module.id + '-icon');
    svg.style.width = '2.5rem';
    svg.style.height = '2.5rem';
  }

}
