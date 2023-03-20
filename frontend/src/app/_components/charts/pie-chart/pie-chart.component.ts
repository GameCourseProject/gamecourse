import {Component, Input, OnInit} from '@angular/core';

import {ThemingService} from "../../../_services/theming/theming.service";
import {UpdateService} from "../../../_services/update.service";

@Component({
  selector: 'app-pie-chart',
  templateUrl: './pie-chart.component.html'
})
// TODO
export class PieChartComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                      // Unique ID
  @Input() classList?: string;

  constructor(
    private themeService: ThemingService,
    private updateManager: UpdateService
  ) { }

  ngOnInit(): void {
    const theme = this.themeService.getTheme();
  }
}
