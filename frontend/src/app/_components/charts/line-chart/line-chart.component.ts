import {Component, Input, OnInit, ViewChild} from '@angular/core';

import {
  ApexAxisChartSeries,
  ApexChart,
  ApexDataLabels,
  ApexGrid,
  ApexStroke,
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
  stroke: ApexStroke;
  title: ApexTitleSubtitle;
  tooltip: ApexTooltip;
};

@Component({
  selector: 'app-line-chart',
  templateUrl: './line-chart.component.html',
  styleUrls: ['./line-chart.component.scss']
})
export class LineChartComponent implements OnInit {

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

  // Stroke
  @Input() strokeCurve?: "smooth" | " straight" | "stepline" = 'smooth';    // Whether to draw smooth or straight lines
  @Input() strokeWidth?: number = 2;                                        // Stroke width
  @Input() strokeColor?: string = 'steelblue';                              // Stroke color

  // Extras
  @Input() sparkline?: boolean = false;                                     // Hide everything but primary paths
  @Input() toolbar?: boolean = false;                                       // Show toolbar with actions

  @ViewChild("chart") chart: ChartComponent;
  public chartOptions: Partial<ChartOptions>;

  readonly CHART_TYPE = 'line';

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
      stroke: {
        // @ts-ignore
        curve: this.strokeCurve,
        width: this.strokeWidth,
        colors: [this.strokeColor]
      },
      grid: {
        show: false
      },
      xaxis: {
        type: this.xAxisType
      },
      yaxis: {
        tickAmount: this.yAxisTickAmount,
        reversed: this.yAxisReversed,
      },
      tooltip: {
        enabled: !this.sparkline
      }
    };

    if (this.xAxisType === 'category') this.chartOptions.xaxis.categories = this.categories;
    if (exists(this.height)) this.chartOptions.chart.height = this.height;
    if (exists(this.width)) this.chartOptions.chart.width = this.width;
    if (exists(this.yAxisMin)) this.chartOptions.yaxis.min = this.yAxisMin;
    if (exists(this.yAxisMax)) this.chartOptions.yaxis.max = this.yAxisMax;
    if (exists(this.xAxisLabel)) this.chartOptions.xaxis.title = { text: this.xAxisLabel };
    if (exists(this.yAxisLabel)) this.chartOptions.yaxis.title = { text: this.yAxisLabel };

    // FIXME: should be general
    if (exists(this.xAxisLabel) && this.xAxisLabel === 'Time (Days)') {
      this.chartOptions.tooltip.x = { formatter(val: number, opts?: any): string {
        return 'Day ' + val;
        }
      }
    }

    if (exists(this.yAxisLabel) && this.yAxisLabel === 'Position') {
      this.chartOptions.yaxis.labels = { formatter(val: number, opts?: any): string | string[] {
        return Math.round(val).toString();
        }
      }
      this.chartOptions.tooltip.y = { formatter(val: number, opts?: any): string {
        return '#' + val;
        }
      }
    }
  }

}
