import {Component, Input, OnInit, ViewChild} from '@angular/core';

import {ThemingService} from "../../../_services/theming/theming.service";
import {UpdateService, UpdateType} from "../../../_services/update.service";
import {
  ApexChart,
  ApexLegend,
  ApexNonAxisChartSeries,
  ApexPlotOptions,
  ApexTitleSubtitle,
  ChartComponent
} from "ng-apexcharts";

import {
  general,
  legend,
  subtitle,
  title,
  update
} from "../ChartOptions";

import {BGDarkColor, BGLightColor, LineColor, TextColor} from "../ChartColors";
import {Theme} from "../../../_services/theming/themes-available";

export type ChartOptions = {
  series: ApexNonAxisChartSeries;
  chart: ApexChart;
  colors: string[];
  labels: any;
  legend: ApexLegend;
  plotOptions: ApexPlotOptions;
  subtitle: ApexTitleSubtitle;
  title: ApexTitleSubtitle;
}

@Component({
  selector: 'app-pie-chart',
  templateUrl: './pie-chart.component.html'
})

// TODO
export class PieChartComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                      // Unique ID
  @Input() series: ApexNonAxisChartSeries;                                  // Data series to plot
  @Input() classList?: string;                                              // Classes to add

  // Size
  @Input() height?: string = 'auto';                                        // Chart height
  @Input() width?: string = '100%';                                         // Chart width

  // Colors
  @Input() colors?: string[];                                               // Colors for data series

  // DataLabels
  @Input() labels?: number[] | string[];

  // Legend
  @Input() legend?: boolean;                                                // Show legend
  @Input() legendPosition?: 'top' | 'right' | 'bottom' | 'left' = 'bottom'; // Legend position

  // Title & Subtitle
  @Input() title?: string;                                                  // Title for chart
  @Input() subtitle?: string;                                               // Subtitle for chart
  @Input() align?: 'left' | 'center' | 'right' = 'left';                    // Title and subtitle alignment


  // Extras
  @Input() startAngle?: number = 0;                                         // Custom angle from which the pie slices start
  @Input() endAngle?: number = 360;                                         // Custom angle from which the pie slices end
  @Input() customScale?: number = 1;                                        // Transform the scale of whole pie overriding default calculations
  @Input() offsetX?: number = 0;                                            // Sets the left offset of the whole pie area
  @Input() offsetY?: number = 0;                                            // Sets the top offset of the whole pie area
  @Input() expandOnClick?: boolean = true;                                  // When clicked slice expands it distinguished visually
  @Input() dataLabels?: { offset: number, minAngleToShowLabel: number } =   // offset = Offset by which labels will move outside / inside the pie area
    { offset: 0, minAngleToShowLabel: 10 };                                 // minAngleToShowLabel = Minimum angle to allow data-labels to show. If the slice angle is less than this number, the label would not show to prevent overlapping issues
  @Input() sparkline?: boolean;                                             // Hide everything but primary paths
  @Input() toolbar?: boolean;                                               // Show toolbar with actions
  @Input() toolbarActions?: ('download' | 'selection' | 'zoom' |            // Toolbar actions available
    'zoomin' | 'zoomout' | 'pan' | 'reset')[] = [];

  @ViewChild("chart") chart: ChartComponent;
  public chartOptions: Partial<ChartOptions>;

  readonly CHART_TYPE = 'pie';

  constructor(
    private themeService: ThemingService,
    private updateManager: UpdateService
  ) { }

  ngOnInit(): void {
    const theme = this.themeService.getTheme();

    // Set chart options
    this.chartOptions = {
      series: this.series,
      chart: general(this.CHART_TYPE, this.height, this.width, TextColor(theme), this.sparkline, this.toolbar, this.toolbarActions),
      colors: this.colors,
      labels: this.labels,
      legend: legend(this.legend, this.legendPosition),
      plotOptions: {
        pie: {
          startAngle: this.startAngle,
          endAngle: this.endAngle,
          customScale: this.customScale,
          offsetX: this.offsetX,
          offsetY: this.offsetY,
          expandOnClick: this.expandOnClick,
          dataLabels: this.dataLabels
        }
      },
      subtitle: subtitle(this.subtitle, this.align),
      title: title(this.title, this.align),
    };

    this.updateManager.update.subscribe(type => {
      if (type === UpdateType.THEME) {
        const theme = this.themeService.getTheme();
        update(this.chart, this.chartOptions, theme === Theme.DARK ? 'dark' : 'light', false,
          {text: TextColor(theme), line: LineColor(theme), bg: {dark: BGDarkColor(theme), light: BGLightColor(theme)}})
      }
    })
  }
}
