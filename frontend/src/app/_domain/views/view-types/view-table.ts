import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {RowType, ViewRow} from "./view-row";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";
import {buildView} from "../build-view/build-view";
import {ErrorService} from "../../../_services/error.service";
import {
  getFakeId,
  groupedChildren,
  viewTree,
  viewsAdded,
  addVariantToGroupedChildren, addToGroupedChildren
} from "../build-view-tree/build-view-tree";
import * as _ from "lodash"
import { buildComponent } from "src/app/_views/restricted/courses/course/settings/views/views-editor/views-editor.component";
import { ViewText } from "./view-text";

export class ViewTable extends View {
  private _headerRows: ViewRow[];
  private _bodyRows: ViewRow[];

  private _footers: boolean;
  private _searching: boolean;
  private _columnFiltering: boolean;
  private _paging: boolean;
  private _lengthChange: boolean;
  private _info: boolean;
  private _ordering: boolean;
  private _orderingBy: string;

  constructor(mode: ViewMode, id: number, viewRoot: number, parent: View, aspect: Aspect, footers: boolean, searching: boolean,
              columnFiltering: boolean, paging: boolean, lengthChange: boolean, info: boolean, ordering: boolean, orderingBy: string,
              children: View[], cssId?: string, classList?: string, styles?: string, visibilityType?: VisibilityType,
              visibilityCondition?: string | boolean, loopData?: string, variables?: Variable[], events?: Event[]) {

    super(mode, ViewType.TABLE, id, viewRoot, parent, aspect, cssId, classList, styles, visibilityType, visibilityCondition,
      loopData, variables, events);

    const headerRows: ViewRow[] = (children.filter(row => (row as ViewRow).rowType === RowType.HEADER) as ViewRow[]);
    const bodyRows: ViewRow[] = (children.filter(row => (row as ViewRow).rowType === RowType.BODY) as ViewRow[]);

    check(headerRows, bodyRows);

    this.headerRows = headerRows;
    this.bodyRows = bodyRows;

    this.footers = footers;
    this.searching = searching;
    this.columnFiltering = columnFiltering;
    this.paging = paging;
    this.lengthChange = lengthChange;
    this.info = info;
    this.ordering = ordering;
    this.orderingBy = orderingBy;

    function check(headerRows: ViewRow[], rows: ViewRow[]) {
      const nrHeaders = headerRows.length;
      const nrRows = rows.length;
      const nrColumns = nrHeaders > 0 ? headerRows[0].children.length : nrRows > 0 ? rows[0].children.length : 0;

      if (nrHeaders > 0 && !headerRows.every(row => row.children.length === nrColumns))
        ErrorService.set('Error: Couldn\'t create table - header rows don\'t have the same number of columns. (view-table.ts)');

      if (nrRows > 0 && !rows.every(row => row.children.length === nrColumns))
        ErrorService.set('Error: Couldn\'t create table - rows don\'t have the same number of columns. (view-table.ts)');
    }
  }


  get headerRows(): ViewRow[] {
    return this._headerRows;
  }

  set headerRows(value: ViewRow[]) {
    this._headerRows = value;
  }

  get bodyRows(): ViewRow[] {
    return this._bodyRows;
  }

  set bodyRows(value: ViewRow[]) {
    this._bodyRows = value;
  }

  get footers(): boolean {
    return this._footers;
  }

  set footers(value: boolean) {
    this._footers = value;
  }

  get searching(): boolean {
    return this._searching;
  }

  set searching(value: boolean) {
    this._searching = value;
  }

  get columnFiltering(): boolean {
    return this._columnFiltering;
  }

  set columnFiltering(value: boolean) {
    this._columnFiltering = value;
  }

  get paging(): boolean {
    return this._paging;
  }

  set paging(value: boolean) {
    this._paging = value;
  }

  get lengthChange(): boolean {
    return this._lengthChange;
  }

  set lengthChange(value: boolean) {
    this._lengthChange = value;
  }

  get info(): boolean {
    return this._info;
  }

  set info(value: boolean) {
    this._info = value;
  }

  get ordering(): boolean {
    return this._ordering;
  }

  set ordering(value: boolean) {
    this._ordering = value;
  }

  get orderingBy(): string {
    return this._orderingBy;
  }

  set orderingBy(value: string) {
    this._orderingBy = value;
  }


  updateView(newView: View): ViewTable { // TODO: refactor view editor
    // if (this.id === newView.id) {
    //   const copy = copyObject(newView);
    //   copy.headerRows = this.headerRows;  // Keep same header rows
    //   copy.rows = this.rows;  // Keep same rows
    //   ViewSelectionService.unselect(copy);
    //   return copy as ViewTable;
    // }
    //
    // // Check if header row
    // for (let i = 0; i < this.headerRows.length; i++) {
    //   const headerRow = this.headerRows[i];
    //   const newHeaderRow = headerRow.updateView(newView);
    //   if (newHeaderRow !== null) {
    //     this.headerRows[i] = newHeaderRow;
    //     return this;
    //   }
    // }
    //
    // // Check if body row
    // for (let i = 0; i < this.rows.length; i++) {
    //   const row = this.rows[i];
    //   const newRow = row.updateView(newView);
    //   if (newRow !== null) {
    //     this.rows[i] = newRow;
    //     return this;
    //   }
    // }

    return null;
  }

  buildViewTree() {
    const viewForDatabase = ViewTable.toDatabase(this);

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

    // Build children into view tree
    for (const child of this.headerRows) {
      child.buildViewTree();
    }
    for (const child of this.bodyRows) {
      child.buildViewTree();
    }
  }

  addChildViewToViewTree(view: View, options?: 'header' | 'body') { // TODO: refactor view editor
    // if (options === 'header') {
    //   for (const headerRow of this.headerRows) {
    //     if ((headerRow as any as View[])[0].viewId === view.viewId) { // Found aspect it belongs
    //       (headerRow as any as View[]).push(view);
    //       return;
    //     }
    //   }
    //   (this.headerRows as any as View[][]).push([view]);  // No aspect found
    // }
    //
    // if (options === 'body') {
    //   for (const row of this.rows) {
    //     if ((row as any as View[])[0].viewId === view.viewId) { // Found aspect it belongs
    //       (row as any as View[]).push(view);
    //       return;
    //     }
    //   }
    //   (this.rows as any as View[][]).push([view]);  // No aspect found
    // }
  }

  removeChildView(childViewId: number) { // TODO: refactor view editor
    // Table has its own editor, do nothing
  }

  replaceWithFakeIds() {
    this.id = getFakeId();
    // Replace IDs in children
    for (const headerRow of this.headerRows) {
      headerRow.replaceWithFakeIds();
      headerRow.parent.id = this.id;
    }
    for (const row of this.bodyRows) {
      row.replaceWithFakeIds();
      row.parent.id = this.id;
    }
  }

  findParent(parentId: number): View { // TODO: refactor view editor
    // if (this.id === parentId)  // Found parent
    //   return this;
    //
    // // Look for parent in children
    // for (const headerRow of this.headerRows) {
    //   const parent = headerRow.findParent(parentId);
    //   if (parent) return parent;
    // }
    // for (const row of this.rows) {
    //   const parent = row.findParent(parentId);
    //   if (parent) return parent;
    // }
    return null;
  }

  findView(viewId: number): View {
    if (this.id === viewId) return this;

    // Look for view in children
    for (const headerRow of this.headerRows) {
      const found = headerRow.findView(viewId);
      if (found) return found;
    }
    for (const row of this.bodyRows) {
      const found = row.findView(viewId);
      if (found) return found;
    }
    return null;
  }

  replaceView(viewId: number, view: View) {
    this.headerRows.forEach((row) => {
      row.replaceView(viewId, view);
    })
    this.bodyRows.forEach((row) => {
      row.replaceView(viewId, view);
    })
  }

  switchMode(mode: ViewMode) {
    this.mode = mode;
    for (const headerRow of this.headerRows) {
      headerRow.switchMode(mode);
    }
    for (const row of this.bodyRows) {
      row.switchMode(mode);
    }
  }

  // fixes the entire view to be visible to an aspect
  modifyAspect(aspectsToReplace: Aspect[], newAspect: Aspect) {
    if (aspectsToReplace.filter(e => _.isEqual(this.aspect, e)).length > 0) {
      const oldId = this.id;
      this.replaceWithFakeIds();
      this.aspect = newAspect;
      if (this.parent) addVariantToGroupedChildren(this.parent.id, oldId, this.id);
      addToGroupedChildren(this, this.parent?.id ?? null)
      for (const child of this.headerRows) {
        child.replaceAspect(aspectsToReplace, newAspect);
      }
      for (const child of this.bodyRows) {
        child.replaceAspect(aspectsToReplace, newAspect);
      }
    }
    else {
      for (const child of this.headerRows) {
        child.modifyAspect(aspectsToReplace, newAspect);
      }
      for (const child of this.bodyRows) {
        child.modifyAspect(aspectsToReplace, newAspect);
      }
    }
  }

  // simply replaces without any other change (helper for the function above)
  replaceAspect(aspectsToReplace: Aspect[], newAspect: Aspect) {
    if (aspectsToReplace.filter(e => _.isEqual(this.aspect, e)).length > 0) {
      this.aspect = newAspect;
    }
    for (const child of this.headerRows) {
      child.replaceAspect(aspectsToReplace, newAspect);
    }
    for (const child of this.bodyRows) {
      child.replaceAspect(aspectsToReplace, newAspect);
    }
  }

  /**
   * Gets a default table view.
   */
  static getDefault(parent: View, viewRoot: number, id?: number, aspect?: Aspect): ViewTable {
    const defaultAspect = new Aspect(null, null);

    const header = ViewRow.getDefault(getFakeId(), null, viewRoot, aspect ?? defaultAspect, RowType.HEADER);
    header.children = [ViewText.getDefault(header, viewRoot, getFakeId(), aspect ?? defaultAspect, "Header")]

    const row = ViewRow.getDefault(getFakeId(), null, viewRoot, aspect ?? defaultAspect, RowType.BODY);
    row.children = [ViewText.getDefault(row, viewRoot, getFakeId(), aspect ?? defaultAspect, "Cell")]

    const table = new ViewTable(ViewMode.EDIT, id ?? getFakeId(), viewRoot, parent, aspect ?? defaultAspect, false, false, false, false, false, false, false, null,
      [header, row]);
    table.headerRows[0].parent = table;
    table.bodyRows[0].parent = table;

    return table;
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      footers: this.footers,
      searching: this.searching,
      columnFiltering: this.columnFiltering,
      paging: this.paging,
      lengthChange: this.lengthChange,
      info: this.info,
      ordering: this.ordering,
      orderingBy: this.orderingBy,
      children: [...this.headerRows, ...this.bodyRows]
    });
  }

  static fromDatabase(obj: ViewTableDatabase, edit: boolean): ViewTable {
    // Parse common view params
    const parsedObj = View.parse(obj);

    // Get a view of type table
    const table: ViewTable = new ViewTable(
      parsedObj.mode,
      parsedObj.id,
      parsedObj.viewRoot,
      null,
      parsedObj.aspect,
      obj.footers,
      obj.searching,
      obj.columnFiltering,
      obj.paging,
      obj.lengthChange,
      obj.info,
      obj.ordering,
      obj.orderingBy,
      obj.children ? edit ? obj.children.map(child => buildView(child[0], true)) : obj.children.map(child => buildView(child)) : [],
      parsedObj.cssId,
      parsedObj.classList,
      parsedObj.styles,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.events
    );

    // Update children's parent
    if (table.headerRows.length > 0)
      table.headerRows = table.headerRows.map(child => { child.parent = table; return child; });

    if (table.bodyRows.length > 0)
      table.bodyRows = table.bodyRows.map(child => { child.parent = table; return child; });

    return table;
  }

  static toDatabase(obj: ViewTable, component: boolean = false): ViewTableDatabase {
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
      footers: obj.footers,
      searching: obj.searching,
      columnFiltering: obj.columnFiltering,
      paging: obj.paging,
      lengthChange: obj.lengthChange,
      info: obj.info,
      ordering: obj.ordering,
      orderingBy: obj.orderingBy,
      children: component ? obj.headerRows.map(row => buildComponent(row)).concat(obj.bodyRows.map(row => buildComponent(row)))
        : (groupedChildren.get(obj.id) ?? [])
    }
  }
}

export interface ViewTableDatabase extends ViewDatabase {
  footers: boolean;
  searching: boolean;
  columnFiltering: boolean;
  paging: boolean;
  lengthChange: boolean;
  info: boolean;
  ordering: boolean;
  orderingBy: string;
  children?: ViewDatabase[] | (number | ViewDatabase)[][];
}
