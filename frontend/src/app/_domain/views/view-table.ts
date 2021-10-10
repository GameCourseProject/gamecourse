import {View, ViewDatabase, ViewType, VisibilityType} from "./view";
import {ViewRow, ViewRowDatabase} from "./view-row";
import {Role} from "../roles/role";

export class ViewTable extends View {

  private _headerRows: ViewRow[];
  private _rows: ViewRow[];
  private _nrColumns: number;
  private _nrRows: number;

  constructor(id: number, viewId: number, parentId: number, role: Role, headerRows: ViewRow[], rows: ViewRow[],
              nrColumns: number, loopData?: any, variables?: any, style?: any, cssId?: string, cl?: string, label?: string,
              visibilityType?: VisibilityType, visibilityCondition?: any, events?: any, link?: any, info?: any) {

    super(id, viewId, parentId, ViewType.TABLE, role, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events, link, info);

    this.headerRows = headerRows;
    this.rows = rows;
    this.nrColumns = nrColumns;
    this.nrRows = rows.length;
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

  get nrRows(): number {
    return this._nrRows;
  }

  set nrRows(value: number) {
    this._nrRows = value;
  }

  static fromDatabase(obj: ViewTableDatabase): ViewTable {
    const parsedObj = View.parse(obj);
    return new ViewTable(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      obj.headerRows.map(header => View.fromDatabase(header) as ViewRow),
      obj.rows.map(row => View.fromDatabase(row) as ViewRow),
      obj.columns,
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
  rows: ViewRowDatabase[];
  headerRows: ViewRowDatabase[];
  columns: number;
}
