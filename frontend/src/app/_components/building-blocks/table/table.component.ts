import {Component, Input, OnInit} from '@angular/core';
import {ViewTable} from "../../../_domain/views/view-table";
import {requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-table',
  templateUrl: './table.component.html'
})
export class TableComponent implements OnInit {

  @Input() view: ViewTable;
  edit: boolean;
  isEditingLayout: boolean;

  readonly TABLE_CLASS = 'table';
  readonly TABLE_HEADER_CLASS = 'table_header';
  readonly TABLE_BODY_CLASS = 'table_body';
  readonly TABLE_TOOLBAR_CLASS = 'table_toolbar';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.headerRows, this.view.rows, this.view.nrColumns]);
    this.edit = this.view.mode === ViewMode.EDIT;

    this.view.class += ' ' + this.TABLE_CLASS;
    this.view.headerRows.forEach(row => row.values.forEach(header => header.class += ' ' + this.TABLE_HEADER_CLASS));
    this.view.rows.forEach(row => row.values.forEach(r => r.class += ' ' + this.TABLE_BODY_CLASS));
  }

}
