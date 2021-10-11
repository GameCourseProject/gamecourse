import {Component, Input, OnInit} from '@angular/core';
import {ViewTable} from "../../../_domain/views/view-table";

@Component({
  selector: 'bb-table',
  templateUrl: './table.component.html',
  styleUrls: ['./table.component.scss']
})
export class TableComponent implements OnInit {

  @Input() view: ViewTable;
  @Input() edit: boolean;

  constructor() { }

  ngOnInit(): void {
  }

}
