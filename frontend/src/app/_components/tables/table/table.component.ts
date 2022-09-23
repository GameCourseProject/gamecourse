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
    if (this.datatable) {
      this.datatable.destroy();
      $('#' + this.id + ' .filters').remove();
    }

    const that = this;
    let opts = this.options ? Object.assign(this.options, this.defaultOptions) : this.defaultOptions;

    // Add footers
    if (this.hasFooters) this.footers = this.headers.map(header => header.label);

    // Add column filtering
    if (this.data.length > 0) {
      $('#' + this.id + ' thead tr')
        .clone(true)
        .addClass('filters')
        .appendTo('#' + this.id + ' thead');

      opts = Object.assign({
        orderCellsTop: true,
        initComplete: function () {
          const api = this.api();

          // For each column
          api.columns().eq(0)
            .each(colIdx => {
              const colType = that.data[0][colIdx].type;
              const cell = $('.filters th').eq($(api.column(colIdx).header()).index());

              // Skip types that are not filterable
              if (colType === TableDataType.IMAGE || colType === TableDataType.BUTTON || colType === TableDataType.ACTIONS
                || colType === TableDataType.CUSTOM) {
                $(cell).html('');

              } else {
                const title = $(cell).text().trim();

                // Get all different options of column
                let options = '';
                if (colType === TableDataType.CHECKBOX || colType === TableDataType.RADIO || colType === TableDataType.TOGGLE) {
                  options += '<option value="true">' + title + '</option>';
                  options += '<option value="false">Not ' + title + '</option>';

                } else {
                  let opts = [];
                  for (let row of that.data) {
                    const value = getValue(row[colIdx]);
                    if (value !== null && value !== undefined && value !== '') opts.push(value);
                  }
                  opts = [...new Set(opts)]; // unique options
                  opts.sort();
                  options = opts.map(option => '<option value="' + option + '">' + option + '</option>').join('');
                }

                // Add select with options
                $(cell).html('<select class="select select-bordered select-sm w-full">' +
                  '<option selected value="undefined">Filter...</option>' + options + '</select>');

                // On every keypress in the select
                $('select', $('.filters th').eq($(api.column(colIdx).header()).index()))
                  .off('keyup change')
                  .on('change', function (e) {
                    // Filter column
                    const regexr = '({search})';
                    let value = ($(this)[0] as HTMLSelectElement).value;
                    api.column(colIdx)
                      .search(
                        value !== null && value !== undefined && value !== 'undefined' ?
                          regexr.replace('{search}', '(((' + value + ')))') :
                          '',
                        value !== null && value !== undefined && value !== 'undefined',
                        value === null || value === undefined || value === 'undefined'
                      )
                      .draw()
                  })
              }

              function getValue(cell: {type: TableDataType, content: any}): string {
                if (cell.type === TableDataType.TEXT) return cell.content['text'];
                if (cell.type === TableDataType.NUMBER) return cell.content['value'];
                if (cell.type === TableDataType.DATE) return cell.content['date']?.format(cell.content['dateFormat'] ?? 'DD/MM/YYYY') ?? null;
                if (cell.type === TableDataType.TIME) return cell.content['time']?.format(cell.content['timeFormat'] ?? 'HH:mm') ?? null;
                if (cell.type === TableDataType.DATETIME) return cell.content['datetime']?.format(cell.content['datetimeFormat'] ?? 'DD/MM/YYYY HH:mm') ?? null;
                if (cell.type === TableDataType.COLOR) return cell.content['color'];
                if (cell.type === TableDataType.PILL) return cell.content['pillText'];
                if (cell.type === TableDataType.AVATAR) return cell.content['avatarTitle'];
                return null;
              }
            });
        },
      }, opts);
    }

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