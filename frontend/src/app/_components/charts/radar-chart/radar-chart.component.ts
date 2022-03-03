import {Component, Input, OnInit, ViewChild} from '@angular/core';
import {
  ApexAxisChartSeries,
  ApexChart,
  ApexDataLabels, ApexFill,
  ApexGrid, ApexMarkers, ApexPlotOptions,
  ApexStroke, ApexTitleSubtitle, ApexTooltip,
  ApexXAxis,
  ApexYAxis, ChartComponent
} from "ng-apexcharts";
import {exists} from "../../../_utils/misc/misc";
import * as _ from "lodash";

export type ChartOptions = {
  series: ApexAxisChartSeries;
  chart: ApexChart;
  xaxis: ApexXAxis;
  yaxis: ApexYAxis;
  dataLabels: ApexDataLabels;
  grid: ApexGrid;
  stroke: ApexStroke;
  title: ApexTitleSubtitle;
  tooltip: ApexTooltip;
  fill: ApexFill;
  markers: ApexMarkers;
  plotOptions: ApexPlotOptions;
  colors: string[];
};

@Component({
  selector: 'app-radar-chart',
  templateUrl: './radar-chart.component.html',
  styleUrls: ['./radar-chart.component.scss']
})
export class RadarChartComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                      // Chart ID
  @Input() series: any[];                                                   // Actual data
  @Input() xAxisType: 'category' | 'datetime' | 'numeric';                  // Type of X-axis
  @Input() categories: any[];                                               // X-axis
  @Input() axisMax: number[];                                               // Max values for axis

  // Size
  @Input() width?: number;                                                  // Chart width
  @Input() height?: number;                                                 // Chart height

  // Extras
  @Input() sparkline?: boolean = false;                                     // Hide everything but primary paths
  @Input() toolbar?: boolean = false;                                       // Show toolbar with actions

  @ViewChild("chart") chart: ChartComponent;
  public chartOptions: Partial<ChartOptions>;

  readonly CHART_TYPE = 'radar';

  constructor() { }

  originalSeries;

  ngOnInit(): void {
    this.scale();
    this.chartOptions = {
      series: this.series,
      chart: {
        type: this.CHART_TYPE,
        sparkline: { enabled: this.sparkline },
        toolbar: { show: this.toolbar },
        zoom: { enabled: false }
      },
      colors: ['#00e396', '#008ffb'],
      fill: {
        opacity: 0.3
      },
      markers: {
        strokeColors: ["transparent", "transparent"],
        fillOpacity: 0.3
      },
      grid: {
        show: false
      },
      dataLabels: {
        enabled: false
      },
      xaxis: {
        type: this.xAxisType,
        categories: this.categories,
      },
      yaxis: {
        show: false,
        min: 0,
        max: 100,
        tickAmount: 5
      },
      tooltip: { }
    };

    if (exists(this.height)) this.chartOptions.chart.height = this.height;
    if (exists(this.width)) this.chartOptions.chart.width = this.width;

    const that = this;
    this.chartOptions.tooltip.y = { formatter(val: number, opts?: any): string {
        return Math.round(that.unscale(val, opts.dataPointIndex)).toString();
      }
    }
  }

  scale() {
    this.originalSeries = _.cloneDeep(this.series);
    for (let i = 0; i < this.series.length; i++) {
      for (let j = 0; j < this.series[i]['data'].length; j++) {
        this.series[i]['data'][j] = this.series[i]['data'][j] * 100 / this.axisMax[j];
      }
    }
    console.log(this.series)
  }

  unscale(val: number, index: number) {
    return val * this.axisMax[index] / 100;
  }

}
