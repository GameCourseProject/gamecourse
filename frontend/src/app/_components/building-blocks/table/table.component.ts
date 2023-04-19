import {Component, Input, OnInit} from '@angular/core';

import {ViewTable} from "../../../_domain/views/view-types/view-table";
import {View, ViewMode} from "../../../_domain/views/view";
import {TableDataType} from "../../tables/table-data/table-data.component";
import {ViewType} from "../../../_domain/views/view-types/view-type";
import {ViewText} from "../../../_domain/views/view-types/view-text";
import {BBAnyComponent} from "../any/any.component";
import {ViewBlock} from "../../../_domain/views/view-types/view-block";

import {dateFromDatabase} from "../../../_utils/misc/misc";

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
    order: [[ 0, 'asc' ]], // default order
    columnDefs: [
      {type: 'natural', targets: []},
      {searchable: false, targets: []},
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
      if (!this.view.ordering) this.headers.push({label: 'sorting'});
    }

    // Get data
    let table: { type: TableDataType, content: any }[][] = [];
    if (this.view.bodyRows?.length > 0) {
      for (let i = 0; i < this.view.bodyRows.length; i++) {
        const row = this.view.bodyRows[i];
        let rowData: { type: TableDataType, content: any}[] = [];
        const sortingData: { type: TableDataType, content: any}[] = [];
        row.children.forEach((cell, index) => {
          const dataType = getDataType(cell);
          if (dataType !== TableDataType.TEXT) {
            if (i == 0) {
              this.headers.push({label: this.headers[index] + ' (sorting)'});
              this.tableOptions['columnDefs'][1]['targets'].push(this.headers.length - 1);
              this.tableOptions['columnDefs'].push({orderData: this.headers.length - 1, targets: index});
            }
            sortingData.push({
              type: TableDataType.NUMBER,
              content: {value: dateFromDatabase((cell as ViewText).text).unix()}
            });
          }

          if (dataType === TableDataType.DATE) {
            rowData.push({
              type: TableDataType.DATETIME,
              content: {date: dateFromDatabase((cell as ViewText).text), dateFormat: 'DD/MM/YYYY'}
            });

          } else if (dataType === TableDataType.TIME) {
            rowData.push({
              type: TableDataType.TIME,
              content: {time: dateFromDatabase((cell as ViewText).text), timeFormat: 'HH:mm'}
            });

          } else if (dataType === TableDataType.DATETIME) {
            rowData.push({
              type: TableDataType.DATETIME,
              content: {datetime: dateFromDatabase((cell as ViewText).text), datetimeFormat: 'DD/MM/YYYY HH:mm'}
            });

          } else {
            rowData.push({
              type: TableDataType.CUSTOM,
              content: {component: BBAnyComponent, componentData: {view: cell}, searchBy: getSearchBy(cell, '')}
            });
          }
        });
        if (!this.view.ordering) rowData.push({
          type: TableDataType.NUMBER,
          content: {value: i}
        });
        rowData = rowData.concat(sortingData);
        table.push(rowData);
      }
    }
    this.data = table;

    // Init table options
    this.tableOptions['searching'] = this.view.searching;
    this.tableOptions['lengthChange'] = this.view.lengthChange;
    this.tableOptions['paging'] = this.view.paging;
    this.tableOptions['info'] = this.view.info;
    this.tableOptions['columnDefs'][0]['targets'] = Array.from(Array(this.headers.length).keys());
    this.tableOptions['columnDefs'][2]['orderable'] = this.view.ordering;
    if (!this.view.ordering) {
      this.tableOptions['columnDefs'][2]['targets'] = Array.from(Array(this.headers.length).keys());
      this.tableOptions['columnDefs'].push({target: this.headers.length - 1, visible: false, searchable: false});
      this.tableOptions['order'][0][0] = this.headers.length - 1;

    } else {
      this.tableOptions['order'] = this.view.orderingBy.split(',').map(o => {
        const parts = o.trim().split(':');
        return [parseInt(parts[1].trim()), parts[0].trim().toLowerCase()];
      });
    }

    this.loading = false;

    function getDataType(view: View): TableDataType {
      if (view.type === ViewType.TEXT) {
        const text = (view as ViewText).text;

        // Date
        let FORMAT = /^\d{4}-\d{2}-\d{2}$/g;
        if (FORMAT.test(text)) return TableDataType.DATE;

        // Time
        FORMAT = /^\d{2}:\d{2}:\d{2}$/g;
        if (FORMAT.test(text)) return TableDataType.TIME;

        // Datetime
        FORMAT = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/g;
        if (FORMAT.test(text)) return TableDataType.DATETIME;
      }

      return TableDataType.TEXT;
    }

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
