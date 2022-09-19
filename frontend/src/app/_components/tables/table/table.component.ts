import {Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges} from '@angular/core';
import {TableDataType} from "../table-data/table-data.component";
import {Action} from "../../../_domain/modules/config/Action";

@Component({
  selector: 'app-table',
  templateUrl: './table.component.html',
})
export class TableComponent implements OnInit, OnChanges {

  @Input() id: string;
  @Input() classList: string;

  @Input() headers: {label: string, align?: 'left' | 'middle' | 'right'}[];
  @Input() footers: string[];
  @Input() hasFooters: boolean = true;

  @Input() data: {type: TableDataType, content: any}[][];
  @Input() options: any;

  @Input() loading: boolean;

  @Output() btnClicked: EventEmitter<{type: Action | 'single', row: number, col: number}> = new EventEmitter<{type: Action | 'single', row: number, col: number}>();
  @Output() valueChanged: EventEmitter<{value: any, row: number, col: number}> = new EventEmitter<{value: any, row: number, col: number}>();

  datatable: DataTables.Api;
  defaultOptions = {
    language: {
      info: 'Showing _START_-_END_ of _TOTAL_',
      paginate: {
        next: '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>',
        previous: '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>'
      }
    },
  };

  constructor() { }

  ngOnInit(): void {
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes.loading && !changes.loading.currentValue) this.buildDatatable();
    else if (!changes.loading && changes.data) this.buildDatatable();
  }

  buildDatatable(): void {
    if (this.datatable) this.datatable.destroy();

    if (this.hasFooters) this.footers = this.headers.map(header => header.label);

    const opts = this.options ? Object.assign(this.options, this.defaultOptions) : this.defaultOptions;
    setTimeout(() => {
      this.datatable = $('#' + this.id).DataTable(opts);

      // Hide sorting columns
      if (opts.hasOwnProperty('columnDefs')) {
        opts['columnDefs'].forEach(option => {
          if (option.hasOwnProperty('orderData') && option.hasOwnProperty('targets'))
            this.datatable.column(option['orderData']).visible(false, false);
        });
      }
    }, 0);
  }

  getAlign(align: 'left' | 'middle' | 'right'): string {
    if (align === 'left') return '!text-left !text-start !justify-start';
    if (align === 'right') return '!text-right !text-end !justify-end';
    return '!text-center !text-middle !justify-center';
  }

}
