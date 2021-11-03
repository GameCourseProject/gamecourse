import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";
import {ErrorService} from "../../_services/error.service";
import {ViewRow, ViewRowDatabase} from "./view-row";

export class ViewTable extends View {

  private _headerRows: ViewRow[];
  private _rows: ViewRow[];
  private _nrColumns: number;

  // Edit only params
  private _isEditingLayout?: boolean;

  static readonly TABLE_CLASS = 'table';
  static readonly TABLE_HEADER_CLASS = 'table_header';
  static readonly TABLE_BODY_CLASS = 'table_body';
  static readonly TABLE_TOOLBAR_CLASS = 'table_toolbar';

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

  static fromDatabase(obj: ViewTableDatabase): ViewTable {
    const parsedObj = View.parse(obj);

    const headerRows = obj.headerRows.map(row => buildView(row)) as ViewRow[];
    headerRows.forEach(row => row.children.forEach(header => header.class += ' ' + this.TABLE_HEADER_CLASS));
    const rows = obj.rows.map(row => buildView(row)) as ViewRow[];
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
