import {Component, Input, OnInit, ViewChild} from '@angular/core';

import {dataLabels, general, legend, markers, stroke, subtitle, title, tooltip, update, xaxis} from "../ChartOptions";
import {BGDarkColor, BGLightColor, LineColor, TextColor} from "../ChartColors";

import {ThemingService} from "../../../_services/theming/theming.service";
import {UpdateService, UpdateType} from "../../../_services/update.service";

import {
  ApexAxisChartSeries,
  ApexChart,
  ApexDataLabels,
  ApexLegend, ApexMarkers, ApexPlotOptions,
  ApexStroke, ApexTitleSubtitle, ApexTooltip,
  ApexXAxis,
  ApexYAxis, ChartComponent
} from "ng-apexcharts";
import {Theme} from "../../../_services/theming/themes-available";

export type ChartOptions = {
  series: ApexAxisChartSeries;
  chart: ApexChart;
  colors: string[];
  dataLabels: ApexDataLabels;
  legend: ApexLegend;
  markers: ApexMarkers;
  plotOptions: ApexPlotOptions;
  stroke: ApexStroke;
  subtitle: ApexTitleSubtitle;
  title: ApexTitleSubtitle;
  tooltip: ApexTooltip;
  xaxis: ApexXAxis;
  yaxis: ApexYAxis;
};

@Component({
  selector: 'app-radar-chart',
  templateUrl: './radar-chart.component.html'
})
export class RadarChartComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                      // Unique ID
  @Input() series: ApexAxisChartSeries;                                     // Data series to plot
  @Input() classList?: string;                                              // Classes to add

  // Size
  @Input() height?: string = 'auto';                                         // Chart height
  @Input() width?: string = '100%';                                          // Chart width

  // Colors
  @Input() colors?: string[];                                               // Colors for data series

  // DataLabels
  @Input() dataLabels?: boolean;                                            // Show data labels
  @Input() dataLabelsOnSeries?: number[];                                   // Show data labels only on specific series
  @Input() dataLabelsFormatter?: string;                                    // Data labels formatter expression

  // Grid
  @Input() stripedGrid?: boolean;                                           // Show striped grid

  // Legend
  @Input() legend?: boolean;                                                // Show legend
  @Input() legendPosition?: 'top' | 'right' | 'bottom' | 'left' = 'bottom'; // Legend position

  // Markers
  @Input() markersSize?: number = 6;                                        // Data points marker size

  // Stroke
  @Input() strokeWidth?: number = 3;                                        // Stroke width

  // Title & Subtitle
  @Input() title?: string;                                                  // Title for chart
  @Input() subtitle?: string;                                               // Subtitle for chart
  @Input() align?: 'left' | 'center' | 'right' = 'left';                    // Title and subtitle alignment

  // Tooltip
  @Input() tooltip?: boolean = true;                                        // Data points tooltip
  @Input() tooltipXFormatter?: string;                                      // Tooltip formatter expression for X values
  @Input() tooltipYFormatter?: string;                                      // Tooltip formatter expression for Y values

  // X-Axis
  @Input() XAxisType?: 'category' | 'datetime' | 'numeric';                 // Type of X-axis
  @Input() XAxisCategories?: (string | number)[];                           // Categories for X-axis

  // Extras
  @Input() sparkline?: boolean;                                             // Hide everything but primary paths
  @Input() toolbar?: boolean;                                               // Show toolbar with actions
  @Input() toolbarActions?: ('download' | 'selection' | 'zoom' |            // Toolbar actions available
    'zoomin' | 'zoomout' | 'pan' | 'reset')[] = []

  @ViewChild("chart") chart: ChartComponent;
  public chartOptions: Partial<ChartOptions>;

  readonly CHART_TYPE = 'radar';

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
      dataLabels: dataLabels(this.dataLabels, this.dataLabelsOnSeries, this.dataLabelsFormatter),
      legend: legend(this.legend, this.legendPosition),
      markers: markers(this.markersSize),
      plotOptions: {
        radar: {
          polygons: {
            strokeColors: !this.sparkline ? LineColor(theme) : 'transparent',
            connectorColors: !this.sparkline ? LineColor(theme) : 'transparent',
            fill: {
              colors: !this.sparkline && this.stripedGrid ? [BGDarkColor(theme), BGLightColor(theme)] : undefined
            }
          }
        }
      },
      stroke: stroke('smooth', 'round', this.strokeWidth),
      subtitle: subtitle(this.subtitle, this.align),
      title: title(this.title, this.align),
      tooltip: tooltip(!this.sparkline && this.tooltip, {xaxis: this.tooltipXFormatter, yaxis: this.tooltipYFormatter},
        theme === Theme.DARK ? 'dark' : 'light'),
      xaxis: xaxis(this.XAxisType, this.XAxisCategories, LineColor(theme))
    };

    // Whenever theme changes, update colors
    this.updateManager.update.subscribe(type => {
      if (type === UpdateType.THEME) {
        const theme = this.themeService.getTheme();
        update(this.chart, this.chartOptions, theme === Theme.DARK ? 'dark' : 'light', this.stripedGrid,
          {text: TextColor(theme), line: LineColor(theme), bg: {dark: BGDarkColor(theme), light: BGLightColor(theme)}})
      }
    });
  }
}
