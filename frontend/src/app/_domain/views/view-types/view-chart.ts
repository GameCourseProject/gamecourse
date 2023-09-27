import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";

export class ViewChart extends View {
  private _chartType: ChartType;
  private _data: string | any;
  private _options: {[key: string]: any};


  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, chartType: ChartType, data: string | any,
              options: {[key: string]: any}, cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType,
              visibilityCondition?: string | boolean, loopData?: string, variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.CHART, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    this.chartType = chartType;
    this.data = data;
    this.options = options;
  }


  get chartType(): ChartType {
    return this._chartType;
  }

  set chartType(value: ChartType) {
    this._chartType = value;
  }

  get data(): string | any {
    return this._data;
  }

  set data(value: string | any) {
    this._data = value;
  }

  get options(): {[key: string]: any} {
    return this._options;
  }

  set options(value: {[key: string]: any}) {
    this._options = value;
  }


  updateView(newView: View): ViewChart { // TODO: refactor view editor
    // if (this.id === newView.id) {
    //   const copy = copyObject(newView);
    //   ViewSelectionService.unselect(copy);
    //   return copy as ViewChart;
    // }
    return null;
  }

  buildViewTree() { // TODO: refactor view editor
    // if (exists(baseFakeId)) this.replaceWithFakeIds();
    //
    // if (!viewsAdded.has(this.id)) { // View hasn't been added yet
    //   const copy = copyObject(this);
    //   if (this.parentId !== null) { // Has parent
    //     const parent = viewsAdded.get(this.parentId);
    //     parent.addChildViewToViewTree(copy);
    //
    //   } else viewTree.push(copy); // Is root
    //   viewsAdded.set(copy.id, copy);
    // }
  }

  addChildViewToViewTree(view: View) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  replaceWithFakeIds(base?: number) { // TODO: refactor view editor
    // const baseId = exists(base) ? base : baseFakeId;
    // this.id = View.calculateFakeId(baseId, this.id);
    // this.viewId = View.calculateFakeId(baseId, this.viewId);
    // this.parentId = View.calculateFakeId(baseId, this.parentId);
  }

  findParent(parentId: number): View { // TODO: refactor view editor
    // Doesn't have children, cannot be parent
    return null;
  }

  findView(viewId: number): View { // TODO: refactor view editor
    // if (this.viewId === viewId) return this;
    return null;
  }


  /**
   * Gets a default chart view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewChart { // TODO: refactor view editor
    return null;
    // return new ViewChart(id, id, parentId, role, ViewMode.EDIT, ChartType.LINE, {provider: ''},
    //   null, null, null, null, View.VIEW_CLASS + ' ' + this.CHART_CLASS + (!!cl ? ' ' + cl : ''));
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
      data: this.data,
      options: this.options,
    });
  }

  static fromDatabase(obj: ViewChartDatabase): ViewChart {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type chart
    return new ViewChart(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      obj.chartType as ChartType,
      obj.data,
      obj.options,
      parsedObj.cssId,
      parsedObj.classList,
      parsedObj.styles,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.events
    );
  }
}

export interface ViewChartDatabase extends ViewDatabase {
  chartType: ChartType;
  data: string | any
  options: {[key: string]: any};
}

export enum ChartType {
  BAR = 'bar',
  COMBO = 'combo',
  LINE = 'line',
  PROGRESS = 'progress',
  RADAR = 'radar',
  PIE = 'pie'
}
