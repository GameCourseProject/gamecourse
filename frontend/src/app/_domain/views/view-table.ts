import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view/build-view";
import {ErrorService} from "../../_services/error.service";
import {ViewRow, ViewRowDatabase} from "./view-row";
import {copyObject} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";
import {viewsAdded, viewTree} from "./build-view-tree/build-view-tree";

export class ViewTable extends View {

  private _headerRows: ViewRow[];
  private _rows: ViewRow[];
  private _nrColumns: number;

  // Edit only params
  private _isEditingLayout?: boolean;

  static readonly TABLE_CLASS = 'gc-table';
  static readonly TABLE_HEADER_CLASS = 'gc-table_header';
  static readonly TABLE_BODY_CLASS = 'gc-table_body';
  static readonly TABLE_TOOLBAR_CLASS = 'gc-table_toolbar';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, headerRows: ViewRow[], rows: ViewRow[], loopData?: any,
              variables?: any, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any) {

    super(id, viewId, parentId, ViewType.TABLE, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

    this.check(headerRows, rows);

    this.headerRows = headerRows;
    this.rows = rows;
    this.nrColumns = headerRows.length > 0 ? headerRows[0].children.length : rows.length > 0 ? rows[0].children.length : 0;
  }

  get headerRows(): ViewRow[] {
    return this._headerRows;
  }

  set headerRows(value: ViewRow[]) {
    this._headerRows = value;
  }

  get rows(): ViewRow[] {
    return this._rows;
  }

  set rows(value: ViewRow[]) {
    this._rows = value;
  }

  get nrColumns(): number {
    return this._nrColumns;
  }

  set nrColumns(value: number) {
    this._nrColumns = value;
  }

  get isEditingLayout(): boolean {
    return this._isEditingLayout;
  }

  set isEditingLayout(value: boolean) {
    this._isEditingLayout = value;
  }

  /**
   * Checks if values are in a correct format.
   *
   * @param headerRows
   * @param rows
   */
  private check(headerRows: ViewRow[], rows: ViewRow[]) {
    const nrColumns = headerRows[0].children.length;

    if (!headerRows.every(row => row.children.length === nrColumns))
      ErrorService.set('Error: Couldn\'t create table - header rows don\'t have the same number of columns. (view-table.ts)');

    if (!rows.every(row => row.children.length === nrColumns))
      ErrorService.set('Error: Couldn\'t create table - rows don\'t have the same number of columns. (view-table.ts)');
  }

  updateView(newView: View): ViewTable {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      copy.headerRows = this.headerRows;  // Keep same header rows
      copy.rows = this.rows;  // Keep same rows
      ViewSelectionService.unselect(copy);
      return copy as ViewTable;
    }

    // Check if header row
    for (let i = 0; i < this.headerRows.length; i++) {
      const headerRow = this.headerRows[i];
      const newHeaderRow = headerRow.updateView(newView);
      if (newHeaderRow !== null) {
        this.headerRows[i] = newHeaderRow;
        return this;
      }
    }

    // Check if body row
    for (let i = 0; i < this.rows.length; i++) {
      const row = this.rows[i];
      const newRow = row.updateView(newView);
      if (newRow !== null) {
        this.rows[i] = newRow;
        return this;
      }
    }

    return null;
  }

  buildViewTree() {
    if (!viewsAdded.has(this.id)) { // View hasn't been added yet
      const copy = copyObject(this);

      // Strip children
      copy.headerRows = [];
      copy.rows = [];

      if (this.parentId !== null) { // Has parent
        const parent = viewsAdded.get(this.parentId);
        parent.addChildViewToViewTree(copy);

      } else viewTree.push(copy); // Is root
      viewsAdded.set(copy.id, copy);
    }

    // Build header & body rows into view tree
    for (const headerRow of this.headerRows) {
      headerRow.buildViewTree('header');
    }
    for (const row of this.rows) {
      row.buildViewTree('body');
    }
  }

  addChildViewToViewTree(view: View, options?: 'header' | 'body') {
    if (options === 'header') {
      for (const headerRow of this.headerRows) {
        if ((headerRow as any as View[])[0].viewId === view.viewId) { // Found aspect it belongs
          (headerRow as any as View[]).push(view);
          return;
        }
      }
      (this.headerRows as any as View[][]).push([view]);  // No aspect found
    }

    if (options === 'body') {
      for (const row of this.rows) {
        if ((row as any as View[])[0].viewId === view.viewId) { // Found aspect it belongs
          (row as any as View[]).push(view);
          return;
        }
      }
      (this.rows as any as View[][]).push([view]);  // No aspect found
    }
  }

  /**
   * Custom way to stringify this class.
   * This is needed so that the output of JSON.stringify()
   * doesn't have '_' on attributes
   */
  toJSON(){
    const obj = View.toJson(this);
    return Object.assign(obj, {
      headerRows: this.headerRows,
      rows: this.rows,
    });
  }

  static fromDatabase(obj: ViewTableDatabase): ViewTable {
    const parsedObj = View.parse(obj);

    const headerRows = obj.headerRows.map(row => buildView(Object.assign(row, {parentId: obj.id}))) as ViewRow[];
    headerRows.forEach(row => row.children.forEach(header => header.class += ' ' + this.TABLE_HEADER_CLASS));
    const rows = obj.rows.map(row => buildView(Object.assign(row, {parentId: obj.id}))) as ViewRow[];
    rows.forEach(row => row.children.forEach(r => r.class += ' ' + this.TABLE_BODY_CLASS));

    return new ViewTable(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      headerRows,
      rows,
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.TABLE_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events
    );
  }
}

export interface ViewTableDatabase extends ViewDatabase {
  headerRows: ViewRowDatabase[];
  rows: ViewRowDatabase[];
}
