import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view/build-view";
import {ErrorService} from "../../_services/error.service";
import {ViewRow, ViewRowDatabase} from "./view-row";
import {copyObject, exists} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";
import {baseFakeId, viewsAdded, viewTree} from "./build-view-tree/build-view-tree";
import {EventType} from "../events/event-type";
import {Event} from "../events/event";
import {Variable} from "../variables/variable";
import {ViewText} from "./view-text";

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
              variables?: {[name: string]: Variable}, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: {[key in EventType]?: Event}) {

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
    const nrColumns = headerRows.length > 0 ? headerRows[0].children.length : rows.length > 0 ? rows[0].children.length : 0;

    if (headerRows.length > 0 && !headerRows.every(row => row.children.length === nrColumns))
      ErrorService.set('Error: Couldn\'t create table - header rows don\'t have the same number of columns. (view-table.ts)');

    if (rows.length > 0 && !rows.every(row => row.children.length === nrColumns))
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
    if (exists(baseFakeId)) this.replaceWithFakeIds();

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

  removeChildView(childViewId: number) {
    // Table has its own editor, do nothing
  }

  replaceWithFakeIds(base?: number) {
    // Replace IDs in children
    for (const headerRow of this.headerRows) {
      headerRow.replaceWithFakeIds(exists(base) ? base : null);
    }
    for (const row of this.rows) {
      row.replaceWithFakeIds(exists(base) ? base : null);
    }

    const baseId = exists(base) ? base : baseFakeId;
    this.id = View.calculateFakeId(baseId, this.id);
    this.viewId = View.calculateFakeId(baseId, this.viewId);
    this.parentId = View.calculateFakeId(baseId, this.parentId);
  }

  findParent(parentId: number): View {
    if (this.id === parentId)  // Found parent
      return this;

    // Look for parent in children
    for (const headerRow of this.headerRows) {
      const parent = headerRow.findParent(parentId);
      if (parent) return parent;
    }
    for (const row of this.rows) {
      const parent = row.findParent(parentId);
      if (parent) return parent;
    }
    return null;
  }

  findView(viewId: number): View {
    if (this.viewId === viewId) return this;

    // Look for view in children
    for (const headerRow of this.headerRows) {
      const found = headerRow.findView(viewId);
      if (found) return headerRow;
    }
    for (const row of this.rows) {
      const found = row.findView(viewId);
      if (found) return row;
    }
    return null;
  }

  insertColumn(to: 'left'|'right', of: number, minID: number): number {
    // Insert in headers
    for (let headerRow of this.headerRows) {
      const defaultCell = ViewText.getDefault(--minID, headerRow.id, headerRow.role);
      if (to === 'left') headerRow.children.insertAtIndex(of, defaultCell);
      else if (to === 'right') headerRow.children.insertAtIndex(of + 1, defaultCell);
    }

    // Insert in body rows
    for (let row of this.rows) {
      const defaultCell = ViewText.getDefault(--minID, row.id, row.role);
      if (to === 'left') row.children.insertAtIndex(of, defaultCell);
      else if (to === 'right') row.children.insertAtIndex(of + 1, defaultCell);
    }

    this.nrColumns++;
    return this.headerRows.length + this.rows.length;
  }

  insertRow(type: 'header'|'body', to: 'up'|'down', of: number, minID: number): number {
    const rowID = --minID;
    const newRow = new ViewRow(rowID, rowID, this.id, this.role, ViewMode.EDIT,
      [...Array(this.nrColumns)].map(x => ViewText.getDefault(--minID, rowID, this.role)),
      null, null, null, null,
      View.VIEW_CLASS + ' ' + ViewRow.ROW_CLASS + ' ' + (type === 'header' ? ViewTable.TABLE_HEADER_CLASS : ViewTable.TABLE_BODY_CLASS));

    if (to === 'up') type === 'header' ? this.headerRows.insertAtIndex(of, newRow) : this.rows.insertAtIndex(of, newRow);
    else if (to === 'down') type === 'header' ? this.headerRows.insertAtIndex(of + 1, newRow) : this.rows.insertAtIndex(of + 1, newRow);

    return this.nrColumns + 1;
  }

  deleteColumn(index: number) {
    // Delete from headers
    for (let headerRow of this.headerRows)
      headerRow.children.removeAtIndex(index);

    // Delete body rows
    for (let row of this.rows)
      row.children.removeAtIndex(index);

    this.nrColumns--;
  }

  deleteRow(type: 'header'|'body', index: number) {
    if (type === 'header') this.headerRows.removeAtIndex(index);
    else if (type === 'body') this.rows.removeAtIndex(index);
  }

  moveColumn(to: 'left'|'right', of: number) {
    // Ignore edges
    if ((to === 'left' && of === 0) || (to === 'right' && of === this.nrColumns - 1)) return;

    // Move in headers
    for (let headerRow of this.headerRows) {
      const cellToMove = headerRow.children[of];
      headerRow.children.removeAtIndex(of);
      if (to === 'left') headerRow.children.insertAtIndex(of - 1, cellToMove);
      else if (to === 'right') headerRow.children.insertAtIndex(of + 1, cellToMove);
    }

    // Move in body rows
    for (let row of this.rows) {
      const cellToMove = row.children[of];
      row.children.removeAtIndex(of);
      if (to === 'left') row.children.insertAtIndex(of - 1, cellToMove);
      else if (to === 'right') row.children.insertAtIndex(of + 1, cellToMove);
    }
  }

  moveRow(type: 'header'|'body', to: 'up'|'down', of: number) {
    // Ignore edges
    if ((to === 'up' && of === 0) || (to === 'down' && of === ((type === 'header' ? this.headerRows.length : this.rows.length) - 1))) return;

    const rowToMove = type === 'header' ? this.headerRows[of] : this.rows[of];
    type === 'header' ? this.headerRows.removeAtIndex(of) : this.rows.removeAtIndex(of);
    if (to === 'up') type === 'header' ? this.headerRows.insertAtIndex(of - 1, rowToMove) : this.rows.insertAtIndex(of - 1, rowToMove);
    else if (to === 'down') type === 'header' ? this.headerRows.insertAtIndex(of + 1, rowToMove) : this.rows.insertAtIndex(of + 1, rowToMove);
  }

  /**
   * Gets a default view.
   */
  static getDefault(id: number = null, parentId: number = null, role: string = null, cl: string = null): ViewTable {
    return new ViewTable(id, id, parentId, role, ViewMode.EDIT,
      [ViewRow.getDefault(id - 1, id, role, this.TABLE_HEADER_CLASS)],
      [ViewRow.getDefault(id - 3, id, role, this.TABLE_BODY_CLASS)],
      null, null, null, null,
      View.VIEW_CLASS + ' ' + this.TABLE_CLASS + (!!cl ? ' ' + cl : ''));
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

    const headerRows = obj.headerRows ? obj.headerRows.map(row => buildView(Object.assign(row, {parentId: obj.id}))) as ViewRow[] : [];
    headerRows.forEach(row => row.children.forEach(header => header.class += ' ' + this.TABLE_HEADER_CLASS));
    const rows = obj.rows ? obj.rows.map(row => buildView(Object.assign(row, {parentId: obj.id}))) as ViewRow[] : [];
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
