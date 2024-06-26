import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import {getFakeId, viewTree, viewsAdded, addVariantToGroupedChildren} from "../build-view-tree/build-view-tree";
import * as _ from "lodash"

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

  buildViewTree() {
    const viewForDatabase = ViewChart.toDatabase(this);

    if (!viewsAdded.has(this.id)) {
      if (this.parent) {
        const parent = viewsAdded.get(this.parent.id);
        const group = (parent as any).children.find((e) => e.includes(this.id));
        const index = group.indexOf(this.id);
        if (index != -1) {
          group.splice(index, 1, viewForDatabase);
        }
      }
      else viewTree.push(viewForDatabase); // Is root
    }
    viewsAdded.set(this.id, viewForDatabase);
  }

  addChildViewToViewTree(view: View) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    // Doesn't have children, do nothing
  }

  replaceWithFakeIds() {
    this.id = getFakeId();
  }

  findParent(parentId: number): View { // TODO: refactor view editor
    // Doesn't have children, cannot be parent
    return null;
  }

  findView(viewId: number): View {
    if (this.id === viewId) return this;
    else return null;
  }

  replaceView(viewId: number, view: View) {
  }

  switchMode(mode: ViewMode) {
    this.mode = mode;
  }

  // fixes the entire view to be visible to an aspect
  modifyAspect(aspectsToReplace: Aspect[], newAspect: Aspect) {
    if (aspectsToReplace.filter(e => _.isEqual(this.aspect, e)).length > 0) {
      const oldId = this.id;
      this.replaceWithFakeIds();
      this.aspect = newAspect;
      if (this.parent) addVariantToGroupedChildren(this.parent.id, oldId, this.id);
    }
  }

  // simply replaces without any other change (helper for the function above)
  replaceAspect(aspectsToReplace: Aspect[], newAspect: Aspect) {
    if (aspectsToReplace.filter(e => _.isEqual(this.aspect, e)).length > 0) {
      this.aspect = newAspect;
    }
  }

  /**
   * Gets a default chart view.
   */
  static getDefault(parent: View, viewRoot: number, id?: number, aspect?: Aspect): ViewChart {
    return new ViewChart(ViewMode.EDIT, id ?? getFakeId(), viewRoot, parent, aspect ?? new Aspect(null, null), ChartType.LINE, null, {});
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

  static toDatabase(obj: ViewChart): ViewChartDatabase {
    return {
      id: obj.id,
      viewRoot: obj.viewRoot,
      aspect: Aspect.toDatabase(obj.aspect),
      type: obj.type,
      cssId: obj.cssId,
      class: obj.classList,
      style: obj.styles,
      visibilityType: obj.visibilityType,
      visibilityCondition: obj.visibilityCondition,
      loopData: obj.loopData,
      variables: obj.variables.map(variable => Variable.toDatabase(variable)),
      events: obj.events.map(event => Event.toDatabase(event)),
      chartType: obj.chartType,
      data: obj.data,
      options: obj.options
    }
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
