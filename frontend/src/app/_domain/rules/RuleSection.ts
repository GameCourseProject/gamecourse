import {Action, ActionScope} from "../modules/config/Action";
import {TableDataType} from "../../_components/tables/table-data/table-data.component";

export class RuleSection{

  private _id: number;
  private _course: number;
  private _name: string;
  private _position: number;
  private _module: string;

  // for tables
  /*private _headers: {label: string, align?: 'left' | 'middle' | 'right'}[];
  private _data: {type: TableDataType, content: any}[][];
  private _options: any;
  private _loadingTable: boolean;
  private _showTable: boolean;*/

  constructor(id: number, course: number, name: string, position: number, module: string) {
    this._id = id;
    this._course = course;
    this._name = name;
    this._position = position;
    this._module = module;
  }

  get id(): number {
    return this._id;
  }

  set id(value: number) {
    this._id = value;
  }

  get course(): number {
    return this._course;
  }

  set course(value: number) {
    this._course = value;
  }

  get name(): string {
    return this._name;
  }

  set name(value: string) {
    this._name = value;
  }

  get position(): number {
    return this._position;
  }

  set position(value: number) {
    this._position = value;
  }

  get module (): string {
    return this._module;
  }

  set module(value: string) {
    this._module = value;
  }

  /*get headers(): {label: string, align?: 'left' | 'middle' | 'right'}[]{
    return this._headers;
  }

  set headers(value: {label: string, align?: 'left' | 'middle' | 'right'}[]) {
    this._headers = value;
  }

  get data() : {type: TableDataType, content: any}[][] {
    return this._data;
  }

  set data(value: {type: TableDataType, content: any}[][]){
    this._data = value;
  }

  get options(): any {
    return this._options;
  }

  set options(value: any){
    this._options = value;
  }

  get loadingTable(): boolean {
    return this._loadingTable;
  }

  get showTable(): boolean {
    return this._showTable;
  }

  set loadingTable(value: boolean){
    this._loadingTable = value;
  }

  set showTable(value: boolean) {
    this._showTable = value;
  }*/

  static fromDatabase(obj: RuleSectionDatabase): RuleSection {
    return new RuleSection(
      obj.id,
      obj.course,
      obj.name,
      obj.position,
      obj.module
    );
  }
}

interface RuleSectionDatabase {
  id: number,
  course: number,
  name: string,
  position: number,
  module: string
}
