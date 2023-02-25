import {Component, Input, OnInit} from '@angular/core';

import {ChartType, ViewChart} from "../../../_domain/views/view-types/view-chart";
import {ViewMode} from "../../../_domain/views/view";

import {exists} from "../../../_utils/misc/misc";

@Component({
  selector: 'bb-chart',
  templateUrl: './chart.component.html'
})
export class BBChartComponent implements OnInit {

  @Input() view: ViewChart;

  edit: boolean;
  classes: string;

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-chart bb-' + this.view.chartType + '-chart';
  }

  get ChartType(): typeof ChartType {
    return ChartType;
  }

  exists(value: any): boolean {
    return exists(value);
  }
}
