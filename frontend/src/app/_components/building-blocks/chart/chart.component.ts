import {Component, Input, OnInit} from '@angular/core';

import {ChartType, ViewChart} from "../../../_domain/views/view-types/view-chart";
import {ViewMode} from "../../../_domain/views/view";

import {exists} from "../../../_utils/misc/misc";
import {ApexAxisChartSeries, ApexNonAxisChartSeries} from 'ng-apexcharts';

@Component({
  selector: 'bb-chart',
  templateUrl: './chart.component.html'
})
export class BBChartComponent implements OnInit {

  @Input() view: ViewChart;

  edit: boolean;
  classes: string;
  seriesToPreview: ApexAxisChartSeries | ApexNonAxisChartSeries

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT || this.view.mode === ViewMode.PREVIEW || this.view.mode === ViewMode.REARRANGE;
    this.classes = 'bb-chart bb-' + this.view.chartType + '-chart';

    if (this.view.chartType === ChartType.PIE) {
      this.seriesToPreview = Array.isArray(this.view.data) ? this.view.data : [];
    }
    else {
      this.seriesToPreview = Array.isArray(this.view.data) ? this.view.data : [{ data: [] }];
    }
  }

  get ChartType(): typeof ChartType {
    return ChartType;
  }

  exists(value: any): boolean {
    return exists(value);
  }
}
