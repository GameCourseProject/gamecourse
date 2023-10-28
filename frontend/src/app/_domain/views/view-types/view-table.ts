import {View, ViewDatabase, ViewMode} from "../view";
import {ViewType} from "./view-type";
import {RowType, ViewRow} from "./view-row";
import {Aspect} from "../aspects/aspect";
import {VisibilityType} from "../visibility/visibility-type";
import {Variable} from "../variables/variable";
import {Event} from "../events/event";

import {buildView} from "../build-view/build-view";

import {ErrorService} from "../../../_services/error.service";
import { buildViewTree } from "src/app/_views/restricted/courses/course/settings/views/views-editor/views-editor.component";

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

  buildViewTree() { // TODO: refactor view editor
    // if (exists(baseFakeId)) this.replaceWithFakeIds();
    //
    // if (!viewsAdded.has(this.id)) { // View hasn't been added yet
    //   const copy = copyObject(this);
    //
    //   // Strip children
    //   copy.headerRows = [];
    //   copy.rows = [];
    //
    //   if (this.parentId !== null) { // Has parent
    //     const parent = viewsAdded.get(this.parentId);
    //     parent.addChildViewToViewTree(copy);
    //
    //   } else viewTree.push(copy); // Is root
    //   viewsAdded.set(copy.id, copy);
    // }
    //
    // // Build header & body rows into view tree
    // for (const headerRow of this.headerRows) {
    //   headerRow.buildViewTree('header');
    // }
    // for (const row of this.rows) {
    //   row.buildViewTree('body');
    // }
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

  replaceWithFakeIds(base?: number) { // TODO: refactor view editor
    // // Replace IDs in children
    // for (const headerRow of this.headerRows) {
    //   headerRow.replaceWithFakeIds(exists(base) ? base : null);
    // }
    // for (const row of this.rows) {
    //   row.replaceWithFakeIds(exists(base) ? base : null);
    // }
    //
    // const baseId = exists(base) ? base : baseFakeId;
    // this.id = View.calculateFakeId(baseId, this.id);
    // this.viewId = View.calculateFakeId(baseId, this.viewId);
    // this.parentId = View.calculateFakeId(baseId, this.parentId);
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

  findView(viewId: number): View { // TODO: refactor view editor
    // if (this.viewId === viewId) return this;
    //
    // // Look for view in children
    // for (const headerRow of this.headerRows) {
    //   const found = headerRow.findView(viewId);
    //   if (found) return headerRow;
    // }
    // for (const row of this.rows) {
    //   const found = row.findView(viewId);
    //   if (found) return row;
    // }
    return null;
  }

  switchMode(mode: ViewMode) {
    this.mode = mode;
  }

  insertColumn(to: 'left'|'right', of: number, minID: number): number { // TODO: refactor view editor
    return null;
    // // Insert in headers
    // for (let headerRow of this.headerRows) {
    //   const defaultCell = ViewText.getDefault(--minID, headerRow.id, headerRow.role);
    //   if (to === 'left') headerRow.children.insertAtIndex(of, defaultCell);
    //   else if (to === 'right') headerRow.children.insertAtIndex(of + 1, defaultCell);
    // }
    //
    // // Insert in body rows
    // for (let row of this.rows) {
    //   const defaultCell = ViewText.getDefault(--minID, row.id, row.role);
    //   if (to === 'left') row.children.insertAtIndex(of, defaultCell);
    //   else if (to === 'right') row.children.insertAtIndex(of + 1, defaultCell);
    // }
    //
    // this.nrColumns++;
    // return this.headerRows.length + this.rows.length;
  }

  insertRow(type: 'header'|'body', to: 'up'|'down', of: number, minID: number): number { // TODO: refactor view editor
    return null;
    // const rowID = --minID;
    // const newRow = new ViewRow(rowID, rowID, this.id, this.role, ViewMode.EDIT,
    //   [...Array(this.nrColumns)].map(x => ViewText.getDefault(--minID, rowID, this.role)),
    //   null, null, null, null,
    //   View.VIEW_CLASS + ' ' + ViewRow.ROW_CLASS + ' ' + (type === 'header' ? ViewTable.TABLE_HEADER_CLASS : ViewTable.TABLE_BODY_CLASS));
    //
    // if (to === 'up') type === 'header' ? this.headerRows.insertAtIndex(of, newRow) : this.rows.insertAtIndex(of, newRow);
    // else if (to === 'down') type === 'header' ? this.headerRows.insertAtIndex(of + 1, newRow) : this.rows.insertAtIndex(of + 1, newRow);
    //
    // return this.nrColumns + 1;
  }

  deleteColumn(index: number) { // TODO: refactor view editor
    // // Delete from headers
    // for (let headerRow of this.headerRows)
    //   headerRow.children.removeAtIndex(index);
    //
    // // Delete body rows
    // for (let row of this.rows)
    //   row.children.removeAtIndex(index);
    //
    // this.nrColumns--;
  }

  deleteRow(type: 'header'|'body', index: number) { // TODO: refactor view editor
    // if (type === 'header') this.headerRows.removeAtIndex(index);
    // else if (type === 'body') this.rows.removeAtIndex(index);
  }

  moveColumn(to: 'left'|'right', of: number) { // TODO: refactor view editor
    // // Ignore edges
    // if ((to === 'left' && of === 0) || (to === 'right' && of === this.nrColumns - 1)) return;
    //
    // // Move in headers
    // for (let headerRow of this.headerRows) {
    //   const cellToMove = headerRow.children[of];
    //   headerRow.children.removeAtIndex(of);
    //   if (to === 'left') headerRow.children.insertAtIndex(of - 1, cellToMove);
    //   else if (to === 'right') headerRow.children.insertAtIndex(of + 1, cellToMove);
    // }
    //
    // // Move in body rows
    // for (let row of this.rows) {
    //   const cellToMove = row.children[of];
    //   row.children.removeAtIndex(of);
    //   if (to === 'left') row.children.insertAtIndex(of - 1, cellToMove);
    //   else if (to === 'right') row.children.insertAtIndex(of + 1, cellToMove);
    // }
  }

  moveRow(type: 'header'|'body', to: 'up'|'down', of: number) { // TODO: refactor view editor
    // // Ignore edges
    // if ((to === 'up' && of === 0) || (to === 'down' && of === ((type === 'header' ? this.headerRows.length : this.rows.length) - 1))) return;
    //
    // const rowToMove = type === 'header' ? this.headerRows[of] : this.rows[of];
    // type === 'header' ? this.headerRows.removeAtIndex(of) : this.rows.removeAtIndex(of);
    // if (to === 'up') type === 'header' ? this.headerRows.insertAtIndex(of - 1, rowToMove) : this.rows.insertAtIndex(of - 1, rowToMove);
    // else if (to === 'down') type === 'header' ? this.headerRows.insertAtIndex(of + 1, rowToMove) : this.rows.insertAtIndex(of + 1, rowToMove);
  }


  /**
   * Gets a default table view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewTable { // TODO: refactor view editor
    return null;
    // return new ViewTable(id, id, parentId, role, ViewMode.EDIT,
    //   [ViewRow.getDefault(id - 1, id, role, this.TABLE_HEADER_CLASS)],
    //   [ViewRow.getDefault(id - 3, id, role, this.TABLE_BODY_CLASS)],
    //   null, null, null, null,
    //   View.VIEW_CLASS + ' ' + this.TABLE_CLASS + (!!cl ? ' ' + cl : ''));
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

  static toDatabase(obj: ViewTable): ViewTableDatabase {
    return {
      id: obj.id,
      viewRoot: obj.viewRoot,
      aspect: obj.aspect,
      type: obj.type,
      cssId: obj.cssId,
      class: obj.classList,
      style: obj.styles,
      visibilityType: obj.visibilityType,
      visibilityCondition: obj.visibilityCondition,
      loopData: obj.loopData,
      variables: obj.variables,
      events: obj.events,
      footers: obj.footers,
      searching: obj.searching,
      columnFiltering: obj.columnFiltering,
      paging: obj.paging,
      lengthChange: obj.lengthChange,
      info: obj.info,
      ordering: obj.ordering,
      orderingBy: obj.orderingBy,
      children: obj.headerRows.map(child => buildViewTree(child))
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
  children?: ViewDatabase[] | ViewDatabase[][];
}
