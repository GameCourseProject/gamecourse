import {Component, Input, OnInit} from '@angular/core';

import {ViewTable} from "../../../_domain/views/view-types/view-table";
import {View, ViewMode} from "../../../_domain/views/view";
import {TableDataType} from "../../tables/table-data/table-data.component";
import {ViewType} from "../../../_domain/views/view-types/view-type";
import {ViewText} from "../../../_domain/views/view-types/view-text";
import {BBAnyComponent} from "../any/any.component";
import {ViewBlock} from "../../../_domain/views/view-types/view-block";

@Component({
  selector: 'bb-table',
  templateUrl: './table.component.html'
})
export class BBTableComponent implements OnInit {

  @Input() view: ViewTable;

  edit: boolean;
  classes: string;

  loading: boolean = true;

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-table';

    this.buildTable();
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Table ------------------- ***/
  /*** --------------------------------------------- ***/

  headers: {label: string, align?: 'left' | 'middle' | 'right'}[] = [];
  data: {type: TableDataType, content: any}[][];
  tableOptions: {[key: string]: any} = {
    searching: true,
    lengthChange: true,
    paging: true,
    info: true,
    columnDefs: [
      {type: 'natural', targets: []},
      {orderable: true}
    ]
  };

  buildTable(): void {
    this.loading = true;

    // Get headers
    if (this.view.headerRows?.length > 0) {
      for (const header of this.view.headerRows[0].children) { // NOTE: only allows one header row
        if (header.type === ViewType.TEXT) // NOTE: only allows text headers
          this.headers.push({label: (header as ViewText).text, align: 'middle'});
      }
    }

    // Get data
    let table: { type: TableDataType, content: any }[][] = [];
    if (this.view.bodyRows?.length > 0) {
      for (const row of this.view.bodyRows) {
        const rowData: { type: TableDataType, content: any}[] = [];
        row.children.forEach(cell => {
          rowData.push({
            type: TableDataType.CUSTOM,
            content: {component: BBAnyComponent, componentData: {view: cell}, searchBy: getSearchBy(cell, '')}
          });
        });
        table.push(rowData);
      }
    }
    this.data = table;

    // Init table options
    this.tableOptions['searching'] = this.view.searching;
    this.tableOptions['lengthChange'] = this.view.lengthChange;
    this.tableOptions['paging'] = this.view.paging;
    this.tableOptions['info'] = this.view.info;
    this.tableOptions['columnDefs'][0]['targets'] = Array.from(Array(this.headers.length).keys())
    this.tableOptions['columnDefs'][1]['orderable'] = this.view.ordering;

    this.loading = false;

    function getSearchBy(view: View, searchBy: string): string {
      if (view.type === ViewType.TEXT) return (view as ViewText).text;
      if (view.type === ViewType.BLOCK) {
        for (const child of (view as ViewBlock).children) {
          searchBy += getSearchBy(child, searchBy) + ' ';
        }
      }
      return searchBy;
    }
  }
}
