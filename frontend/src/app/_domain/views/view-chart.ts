import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {Variable} from "../variables/variable";
import {EventType} from "../events/event-type";
import {Event} from "../events/event";
import {ViewType} from "./view-type";
import {copyObject, exists} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";
import {baseFakeId, viewsAdded, viewTree} from "./build-view-tree/build-view-tree";

export class ViewChart extends View {

  private _chartType: ChartType;
  private _info: {[key: string]: any};

  static readonly CHART_CLASS = 'gc-chart';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, chartType: ChartType,
              info: {[key: string]: any}, loopData?: any, variables?: {[name: string]: Variable}, style?: string,
              cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType, visibilityCondition?: any,
              events?: {[key in EventType]?: Event}) {

    super(id, viewId, parentId, ViewType.CHART, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

    this.chartType = chartType;
    this.info = info;
  }

  get chartType(): ChartType {
    return this._chartType;
  }

  set chartType(value: ChartType) {
    this._chartType = value;
  }

  get info(): {[key: string]: any} {
    return this._info;
  }

  set info(value: {[key: string]: any}) {
    this._info = value;
  }

  updateView(newView: View): ViewChart {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      ViewSelectionService.unselect(copy);
      return copy as ViewChart;
    }
    return null;
  }

  buildViewTree() {
    if (exists(baseFakeId)) this.replaceWithFakeIds();

    if (!viewsAdded.has(this.id)) { // View hasn't been added yet
      const copy = copyObject(this);
      if (this.parentId !== null) { // Has parent
        const parent = viewsAdded.get(this.parentId);
        parent.addChildViewToViewTree(copy);

      } else viewTree.push(copy); // Is root
      viewsAdded.set(copy.id, copy);
    }
  }

  addChildViewToViewTree(view: View) {
    // Doesn't have children, do nothing
  }

  removeChildView(childViewId: number) {
    // Doesn't have children, do nothing
  }

  replaceWithFakeIds(base?: number) {
    const baseId = exists(base) ? base : baseFakeId;
    this.id = View.calculateFakeId(baseId, this.id);
    this.viewId = View.calculateFakeId(baseId, this.viewId);
    this.parentId = View.calculateFakeId(baseId, this.parentId);
  }

  findParent(parentId: number): View {
    // Doesn't have children, cannot be parent
    return null;
  }

  findView(viewId: number): View {
    if (this.viewId === viewId) return this;
    return null;
  }

  /**
   * Gets a default view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewChart {
    return new ViewChart(id, id, parentId, role, ViewMode.EDIT, ChartType.LINE, {provider: ''},
      null, null, null, null, View.VIEW_CLASS + ' ' + this.CHART_CLASS + (!!cl ? ' ' + cl : ''));
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      chartType: this.chartType,
      info: this.info,
    });
  }

  static fromDatabase(obj: ViewChartDatabase): ViewChart {
    const parsedObj = View.parse(obj);
    return new ViewChart(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      obj.chartType as ChartType,
      obj.info,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.CHART_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events
    );
  }
}

export interface ViewChartDatabase extends ViewDatabase {
  chartType: string;
  info: {[key: string]: any};
}

export enum ChartType {
  LINE = 'line',
  BAR = 'bar',
  STAR = 'star',
  PROGRESS = 'progress'
}
