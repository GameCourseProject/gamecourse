import {Component, Input, OnInit} from '@angular/core';
import {ViewTable} from "../../../_domain/views/view-table";
import {requireValues} from "../../../_utils/misc/misc";

@Component({
  selector: 'bb-table',
  templateUrl: './table.component.html',
  styleUrls: ['./table.component.scss']
})
export class TableComponent implements OnInit {

  @Input() view: ViewTable;
  @Input() edit: boolean;

  readonly TABLE_CLASS = 'table';

  constructor() { }

  ngOnInit(): void {
    requireValues([this.view.headerRows, this.view.rows, this.view.nrColumns]);

    this.view.class += ' ' + this.TABLE_CLASS;
  }

}
