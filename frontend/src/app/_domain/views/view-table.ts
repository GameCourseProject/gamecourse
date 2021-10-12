import {View, ViewDatabase, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";
import {ErrorService} from "../../_services/error.service";

export class ViewTable extends View {

  private _headerRows: View[][];
  private _rows: View[][];
  private _nrColumns: number;

  constructor(id: number, viewId: number, parentId: number, role: string, headerRows: View[][], rows: View[][], loopData?: any,
              variables?: any, style?: any, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any, link?: any, info?: any) {

    super(id, viewId, parentId, ViewType.TABLE, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, link, info);

    this.check(headerRows, rows);

    this.headerRows = headerRows;
    this.rows = rows;
    this.nrColumns = headerRows.length > 0 ? headerRows[0].length : rows.length > 0 ? rows[0].length : 0;
  }

  get headerRows(): View[][] {
    return this._headerRows;
  }

  set headerRows(value: View[][]) {
    this._headerRows = value;
  }

  get rows(): View[][] {
    return this._rows;
  }

  set rows(value: View[][]) {
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
  private check(headerRows: View[][], rows: View[][]) {
    const nrColumns = headerRows[0].length;

    if (!headerRows.every(row => row.length === nrColumns))
      ErrorService.set('Error: Couldn\'t create table - header rows don\'t have the same number of columns. (view-table.ts)');

    if (!rows.every(row => row.length === nrColumns))
      ErrorService.set('Error: Couldn\'t create table - rows don\'t have the same number of columns. (view-table.ts)');
  }

  static fromDatabase(obj: ViewTableDatabase): ViewTable {
    const parsedObj = View.parse(obj);
    return new ViewTable(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      obj.headerRows.map(row => row.map(header => buildView(header))),
      obj.rows.map(row => row.map(r => buildView(r))),
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events,
      parsedObj.link,
      parsedObj.info
    );
  }
}

export interface ViewTableDatabase extends ViewDatabase {
  headerRows: ViewDatabase[][];
  rows: ViewDatabase[][];
}
