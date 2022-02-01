import {Component, Input, OnInit, ViewChild} from '@angular/core';

import {
  ApexAxisChartSeries,
  ApexChart,
  ApexDataLabels,
  ApexGrid, ApexLegend, ApexPlotOptions,
  ApexTitleSubtitle, ApexTooltip,
  ApexXAxis, ApexYAxis,
  ChartComponent
} from 'ng-apexcharts';
import {exists} from "../../../_utils/misc/misc";

export type ChartOptions = {
  series: ApexAxisChartSeries;
  chart: ApexChart;
  xaxis: ApexXAxis;
  yaxis: ApexYAxis;
  dataLabels: ApexDataLabels;
  grid: ApexGrid;
  title: ApexTitleSubtitle;
  tooltip: ApexTooltip;
  plotOptions: ApexPlotOptions;
  colors: any;
  legend: ApexLegend;
};

@Component({
  selector: 'app-bar-chart',
  templateUrl: './bar-chart.component.html',
  styleUrls: ['./bar-chart.component.scss']
})
export class BarChartComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                      // Chart ID
  @Input() data: any[];                                                     // Actual data
  @Input() name: string;                                                    // What is the data about
  @Input() xAxisType: 'category' | 'datetime' | 'numeric';                  // Type of X-axis
  @Input() categories?: any[];                                              // X-axis categories

  // Size
  @Input() width?: number;                                                  // Chart width
  @Input() height?: number;                                                 // Chart height

  // Axis
  @Input() yAxisMin?: number;                                               // Lowest value for Y-axis
  @Input() yAxisMax?: number;                                               // Highest value for Y-axis
  @Input() yAxisTickAmount?: number = 6;                                    // Number of ticks for Y-Axis
  @Input() xAxisLabel?: string;                                             // X-axis label
  @Input() yAxisLabel?: string;                                             // Y-axis label
  @Input() yAxisReversed?: boolean = false;                                 // Reverse Y-axis

  // Colors
  @Input() primaryColor?: string = '#a33c30';                               // Primary color
  @Input() highlightColor?: string = 'steelblue';                           // Highlight color
  @Input() highlightedValue?: any;                                          // Value to highlight

  // Extras
  @Input() sparkline?: boolean = false;                                     // Hide everything but primary paths
  @Input() toolbar?: boolean = false;                                       // Show toolbar with actions

  @ViewChild("chart") chart: ChartComponent;
  public chartOptions: Partial<ChartOptions>;

  readonly CHART_TYPE = 'bar';

  constructor() { }

  ngOnInit(): void {
    this.chartOptions = {
      series: [
        {
          name: this.name,
          data: this.data
        }
      ],
      chart: {
        type: this.CHART_TYPE,
        sparkline: { enabled: this.sparkline },
        toolbar: { show: this.toolbar },
        zoom: { enabled: false }
      },
      plotOptions: {
        bar: {
          columnWidth: "90%",
          distributed: true
        }
      },
      dataLabels: {
        enabled: false
      },
      grid: {
        show: false
      },
      legend: {
        show: false
      },
      xaxis: {
        type: this.xAxisType,
        tickAmount: 10
      },
      yaxis: {
        tickAmount: this.yAxisTickAmount,
        reversed: this.yAxisReversed,
      },
      tooltip: { }
    };

    if (this.xAxisType === 'category') this.chartOptions.xaxis.categories = this.categories;
    if (exists(this.height)) this.chartOptions.chart.height = this.height;
    if (exists(this.width)) this.chartOptions.chart.width = this.width;
    if (exists(this.yAxisMin)) this.chartOptions.yaxis.min = this.yAxisMin;
    if (exists(this.yAxisMax)) this.chartOptions.yaxis.max = this.yAxisMax;
    if (exists(this.xAxisLabel)) this.chartOptions.xaxis.title = { text: this.xAxisLabel };
    if (exists(this.yAxisLabel)) this.chartOptions.yaxis.title = { text: this.yAxisLabel };

    // FIXME: should be general
    this.chartOptions.colors = Array.from(Array(this.categories.length).fill(this.primaryColor));
    const index = this.categories.findIndex(el => el === this.highlightedValue);
    this.chartOptions.colors[index] = this.highlightColor;

    this.chartOptions.tooltip.y = { formatter(val: number, opts?: any): string {
        return Math.round(val).toString();
      }
    }
    this.chartOptions.yaxis.labels = { formatter(val: number, opts?: any): string | string[] {
        return Math.round(val).toString();
      }
    }

    if (exists(this.xAxisLabel) && this.xAxisLabel === 'XP') {
      this.chartOptions.tooltip.x = { formatter(val: number, opts?: any): string {
          return val + '-' + (val + 500)  + ' XP';
        }
      }
    }

    if (exists(this.xAxisLabel) && this.xAxisLabel === 'Badges') {
      this.chartOptions.tooltip.x = { formatter(val: number, opts?: any): string {
          return val + ' Badges';
        }
      }
    }
  }

}
