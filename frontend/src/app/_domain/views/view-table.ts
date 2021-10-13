import {View, ViewDatabase, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";
import {ErrorService} from "../../_services/error.service";
import {ViewRow, ViewRowDatabase} from "./view-row";

export class ViewTable extends View {

  private _headerRows: ViewRow[];
  private _rows: ViewRow[];
  private _nrColumns: number;

  constructor(id: number, viewId: number, parentId: number, role: string, headerRows: ViewRow[], rows: ViewRow[], loopData?: any,
              variables?: any, style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, info?: any) {

    super(id, viewId, parentId, ViewType.TABLE, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, info);

    this.check(headerRows, rows);

    this.headerRows = headerRows;
    this.rows = rows;
    this.nrColumns = headerRows.length > 0 ? headerRows[0].values.length : rows.length > 0 ? rows[0].values.length : 0;
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

  /**
   * Checks if values are in a correct format.
   *
   * @param headerRows
   * @param rows
   */
  private check(headerRows: ViewRow[], rows: ViewRow[]) {
    const nrColumns = headerRows[0].values.length;

    if (!headerRows.every(row => row.values.length === nrColumns))
      ErrorService.set('Error: Couldn\'t create table - header rows don\'t have the same number of columns. (view-table.ts)');

    if (!rows.every(row => row.values.length === nrColumns))
      ErrorService.set('Error: Couldn\'t create table - rows don\'t have the same number of columns. (view-table.ts)');
  }

  static fromDatabase(obj: ViewTableDatabase): ViewTable {
    const parsedObj = View.parse(obj);
    return new ViewTable(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      obj.headerRows.map(row => buildView(row)) as ViewRow[],
      obj.rows.map(row => buildView(row)) as ViewRow[],
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events,
      parsedObj.info
    );
  }
}

export interface ViewTableDatabase extends ViewDatabase {
  headerRows: ViewRowDatabase[];
  rows: ViewRowDatabase[];
}
