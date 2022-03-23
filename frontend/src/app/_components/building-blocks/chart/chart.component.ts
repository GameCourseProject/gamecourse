import {Component, Input, OnInit} from '@angular/core';
import {ChartType, ViewChart} from "../../../_domain/views/view-chart";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode, VisibilityType} from "../../../_domain/views/view";
import {Event} from "../../../_domain/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/events/event-action";
import {EventGoToPage} from 'src/app/_domain/events/event-go-to-page';
import {EventHideView} from 'src/app/_domain/events/event-hide-view';
import {EventShowView} from 'src/app/_domain/events/event-show-view';
import {EventToggleView} from 'src/app/_domain/events/event-toggle-view';

@Component({
  selector: 'bb-chart',
  templateUrl: './chart.component.html'
})
export class ChartComponent implements OnInit {

  @Input() view: ViewChart;
  edit: boolean;

  // FIXME: should be made general
  chartType: 'starPlot' | 'xpEvolution' | 'leaderboardEvolution' | 'xpWorld' | 'badgeWorld';

  readonly DEFAULT = 'Chart';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.chartType, this.view.info]);
    if (!this.edit && !!this.view.events?.click) this.view.class += ' gc-clickable';
    this.edit = this.view.mode === ViewMode.EDIT;

    if (!this.edit && this.view.chartType !== ChartType.PROGRESS) {
      // FIXME: should be made general
      if (this.view.info['labelX'] === 'Time (Days)' && this.view.info['labelY'] === 'XP') this.chartType = 'xpEvolution';
      if (this.view.info['labelX'] === 'Time (Days)' && this.view.info['labelY'] === 'Position') this.chartType = 'leaderboardEvolution';
      if (this.view.info['labelX'] === 'XP' && this.view.info['labelY'] === '# Players') this.chartType = 'xpWorld';
      if (this.view.info['labelX'] === 'Badges' && this.view.info['labelY'] === '# Players') this.chartType = 'badgeWorld';
      if (!this.chartType) this.chartType = 'starPlot';

      if (this.chartType !== 'starPlot')
        this.view.info['values'] = this.parseValues(this.view.info['values']);

      else {
        const parsed = this.parseRadarValues(this.view.info['user'], this.view.info['average'], this.view.info['params']);
        this.view.info['user'] = parsed.user;
        this.view.info['average'] = parsed.world;
      }
    }

    if (this.view.visibilityType === VisibilityType.INVISIBLE && !this.edit) {
      this.view.style = this.view.style || '';
      this.view.style = this.view.style.concatWithDivider('display: none', ';');
    }
  }

  get ChartType(): typeof ChartType {
    return ChartType;
  }


  /*** ---------------------------------------- ***/
  /*** ---------- Data Manipulation ----------- ***/
  /*** ---------------------------------------- ***/

  parseValues(values: {x: number, y: number}[]): {x: number[], y: number[]} {
    return {
      x: values.map(val => val.x),
      y: values.map(val => val.y)
    }
  }

  parseRadarValues(user: {[key: string]: number|null}, world: {[key: string]: number|null}|null, params): {user: number[], world: number[]} {
    return {
      user: Object.values(user).map((val, i) => exists(val) ? val : 0),
      world: world !== null ? Object.values(world).map((val, i) => exists(val) ? val : 0) : null
    }
  }

  parseCategories(categories: {id: string, label: string, max: number}[]): string[] {
    return categories.map(category => category.label);
  }

  parseAxisMax(params: {id: string, label: string, max: number}[]): number[] {
    return params.map(param => param.max);
  }


  /*** ---------------------------------------- ***/
  /*** ---------------- Events ---------------- ***/
  /*** ---------------------------------------- ***/

  getEvent(action: EventAction): Event {
    if (!exists(this.view.events)) return null;
    return getEventFromAction(this.view.events, action);
  }

  get EventAction(): typeof EventAction {
    return EventAction;
  }

  get EventGoToPage(): typeof EventGoToPage {
    return EventGoToPage;
  }

  get EventHideView(): typeof EventHideView {
    return EventHideView;
  }

  get EventShowView(): typeof EventShowView {
    return EventShowView;
  }

  get EventToggleView(): typeof EventToggleView {
    return EventToggleView;
  }

}
