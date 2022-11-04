import {
  Component,
  ComponentRef,
  EmbeddedViewRef,
  EventEmitter,
  Input,
  OnChanges,
  OnInit,
  Output,
  SimpleChanges,
  ViewContainerRef
} from '@angular/core';
import {getValue, isFilterable, TableData, TableDataType} from "../table-data/table-data.component";
import {Action} from "../../../_domain/modules/config/Action";
import * as _ from 'lodash';

@Component({
  selector: 'app-table',
  templateUrl: './table.component.html',
})
export class TableComponent implements OnInit, OnChanges {

  @Input() id: string;
  @Input() classList: string;

  @Input() headers: {label: string, align?: 'left' | 'middle' | 'right'}[];
  @Input() footers: string[];

  @Input() hasColumnFiltering: boolean = true;
  @Input() hasFooters: boolean = true;

  @Input() data: {type: TableDataType, content: any}[][];
  @Input() options?: any;

  @Input() loading: boolean;

  @Output() btnClicked: EventEmitter<{type: Action | 'single', row: number, col: number}> = new EventEmitter<{type: Action | 'single', row: number, col: number}>();
  @Output() valueChanged: EventEmitter<{value: any, row: number, col: number}> = new EventEmitter<{value: any, row: number, col: number}>();

  datatable: DataTables.Api;
  defaultOptions = {
    deferRender: true,
    language: {
      info: 'Showing _START_-_END_ of _TOTAL_',
      paginate: {
        next: '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>',
        previous: '<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>'
      }
    },
    createdRow: (row, data, dataIndex) => {
      row.classList = 'whitespace-nowrap bg-base-100 hover:bg-base-200';
    },
    columnDefs: [
      {
        targets: '_all',
        render: (data, type, row, meta) => { // Render cell with info for sorting, searching and filtering
          let value = '';
          if (data.type === TableDataType.TEXT) value = data.content['text'];
          else if (data.type === TableDataType.NUMBER) value = data.content['value']?.toString() ?? '';
          else if (data.type === TableDataType.DATE) value = data.content['date']?.format(data.content['dateFormat'] ?? 'DD/MM/YYYY') ?? 'Never';
          else if (data.type === TableDataType.TIME) value = data.content['time']?.format(data.content['timeFormat'] ?? 'HH:mm') ?? 'Never';
          else if (data.type === TableDataType.DATETIME) value = data.content['datetime']?.format(data.content['datetimeFormat'] ?? 'DD/MM/YYYY HH:mm') ?? 'Never';
          else if (data.type === TableDataType.COLOR) value = data.content['color'];
          else if (data.type === TableDataType.PILL) value = data.content['pillText'];
          else if (data.type === TableDataType.AVATAR) value = data.content['avatarTitle'] + (' ' + data.content['avatarSubtitle'] ?? '');
          else if (data.type === TableDataType.CHECKBOX) value = data.content['checkboxValue'].toString();
          else if (data.type === TableDataType.RADIO) value = data.content['radioValue'].toString();
          else if (data.type === TableDataType.TOGGLE) value = data.content['toggleValue'].toString();
          else if (data.type === TableDataType.CUSTOM) value = data.content['searchBy'] ?? '';
          return value.swapNonENChars();
        },
        createdCell: (td, cellData, rowData, rowIdx, colIdx) => { // Creating each cell according to its type
          td.innerHTML = '';

          const componentRef: ComponentRef<TableData> = this.viewContainerRef.createComponent(TableData);
          componentRef.instance.type = cellData.type;
          componentRef.instance.data = cellData.content;
          componentRef.instance.align = this.headers[colIdx].align;
          componentRef.instance.btnClicked.subscribe((event) => this.btnClicked.emit({type: event, row: rowIdx, col: colIdx}));
          componentRef.instance.valueChanged.subscribe((event) => this.valueChanged.emit({value: event, row: rowIdx, col: colIdx}));
          td.classList = '!border-b !border-b-base-content !border-opacity-20';

          const domElement = (componentRef.hostView as EmbeddedViewRef<any>).rootNodes[0] as HTMLElement;
          td.appendChild(domElement);
        }
      }
    ]
  };

  constructor(private viewContainerRef: ViewContainerRef) { }

  ngOnInit(): void {
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes.loading && !changes.loading.currentValue) this.buildDatatable();
    else if (!changes.loading && changes.data) this.buildDatatable();
  }

  buildDatatable(): void {
    // Reset table
    if (this.datatable) {
      this.datatable.destroy();
      $('#' + this.id + ' .filters').remove();
    }

    // Set options
    let opts = _.merge(this.options ?? {}, this.defaultOptions);

    // Set data
    opts['data'] = this.data;

    // Add footers
    if (this.hasFooters) this.footers = this.headers.map(header => header.label);

    const that = this;
    setTimeout(() => {
      // Add column filtering
      if (this.hasColumnFiltering && this.data.length > 0) {
        // Create filters header
        $('#' + this.id + ' thead tr')
          .clone(true)
          .addClass('filters')
          .appendTo('#' + this.id + ' thead');

        opts = _.merge({
            orderCellsTop: true,
            initComplete: function () {
              const api = this.api();

              // For each column
              api.columns().eq(0)
                .each(colIdx => {
                  const colType = that.data[0][colIdx].type;
                  const filterCell = $('#' + that.id + ' .filters th').eq($(api.column(colIdx).header()).index());

                  // Skip types that are not filterable
                  if (!isFilterable(colType)) {
                    $(filterCell).html('');

                  } else {
                    const title = $(filterCell).text().trim();

                    // Get all different options of column
                    let options = '';
                    if (colType === TableDataType.CHECKBOX || colType === TableDataType.RADIO || colType === TableDataType.TOGGLE) {
                      options += '<option value="true">' + title + '</option>';
                      options += '<option value="false">Not ' + title + '</option>';

                    } else {
                      let opts = [];
                      for (let row of that.data) {
                        const value = getValue(row[colIdx]);
                        if (value) opts.push(value);
                      }
                      opts = [...new Set(opts)]; // unique options
                      opts.sort((a, b) => {
                        if (typeof a === 'string' && typeof b === 'string') return a.localeCompare(b);
                        return a - b;
                      })
                      options = opts.map(option => '<option value="' + option + '">' + option + '</option>').join('');
                    }

                    // Add select with options
                    $(filterCell).html('<select class="select select-bordered select-sm w-full">' +
                      '<option selected value="undefined">Filter...</option>' + options + '</select>');

                    // On every keypress in the select
                    $('select', filterCell)
                      .off('keyup change')
                      .on('change', function (e) {
                        // Filter column
                        const regexr = colType === TableDataType.NUMBER ? '(^{search}$)' : '({search})';
                        const value = ($(this)[0] as HTMLSelectElement).value.swapNonENChars();
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
                });
            },
          }, opts);
      }

      // Create datatable
      this.datatable = $('#' + this.id).DataTable(opts);

      // Hide sorting columns
      opts['columnDefs'].forEach(option => {
        if (option.hasOwnProperty('orderData') && option.hasOwnProperty('targets'))
          this.datatable.column(option['orderData']).visible(false, false);
      });

      // Custom search
      $('#' + this.id + '_filter input')
        .unbind()
        .on('input', function (event) {
          let value = ($(this)[0] as HTMLInputElement).value.swapNonENChars();
          that.datatable.search(value).draw();
        })
    }, 0);
  }

  getAlign(align: 'left' | 'middle' | 'right'): string {
    if (align === 'left') return '!text-left !text-start !justify-start';
    if (align === 'right') return '!text-right !text-end !justify-end';
    return '!text-center !text-middle !justify-center';
  }

}

(function() {

  /*
   * Natural Sort algorithm for Javascript - Version 0.7 - Released under MIT license
   * Author: Jim Palmer (based on chunking idea from Dave Koelle)
   * Contributors: Mike Grier (mgrier.com), Clint Priest, Kyle Adams, guillermo
   * See: http://js-naturalsort.googlecode.com/svn/trunk/naturalSort.js
   */
  function naturalSort(a, b, html) {
    let re = /(^-?[0-9]+(\.?[0-9]*)[df]?e?[0-9]?%?$|^0x[0-9a-f]+$|[0-9]+)/gi,
      sre = /(^[ ]*|[ ]*$)/g,
      dre = /(^([\w ]+,?[\w ]+)?[\w ]+,?[\w ]+\d+:\d+(:\d+)?[\w ]?|^\d{1,4}[\/\-]\d{1,4}[\/\-]\d{1,4}|^\w+, \w+ \d+, \d{4})/,
      hre = /^0x[0-9a-f]+$/i,
      ore = /^0/,
      htmre = /(<([^>]+)>)/ig,
      // convert all to strings and trim()
      x = a.toString().replace(sre, '') || '',
      y = b.toString().replace(sre, '') || '';
    // remove html from strings if desired
    if (!html) {
      x = x.replace(htmre, '');
      y = y.replace(htmre, '');
    }
    // chunk/tokenize
    const xN = x.replace(re, '\0$1\0').replace(/\0$/, '').replace(/^\0/, '').split('\0'),
      yN = y.replace(re, '\0$1\0').replace(/\0$/, '').replace(/^\0/, '').split('\0'),
      // numeric, hex or date detection
      xD = parseInt(x.match(hre), 10) || (xN.length !== 1 && x.match(dre) && Date.parse(x)),
      yD = parseInt(y.match(hre), 10) || xD && y.match(dre) && Date.parse(y) || null;

    // first try and sort Hex codes or Dates
    if (yD) {
      if ( xD < yD ) {
        return -1;
      } else if ( xD > yD ) {
        return 1;
      }
    }

    // natural sorting through split numeric strings and default strings
    for (let cLoc = 0, numS = Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
      // find floats not starting with '0', string or 0 if not defined (Clint Priest)
      let oFxNcL = !(xN[cLoc] || '').match(ore) && parseFloat(xN[cLoc]) || xN[cLoc] || 0;
      let oFyNcL = !(yN[cLoc] || '').match(ore) && parseFloat(yN[cLoc]) || yN[cLoc] || 0;
      // handle numeric vs string comparison - number < string - (Kyle Adams)
      if (isNaN(oFxNcL) !== isNaN(oFyNcL)) {
        return (isNaN(oFxNcL)) ? 1 : -1;
      } else if (typeof oFxNcL !== typeof oFyNcL) {
        oFxNcL += '';
        oFyNcL += '';
      }
      if (oFxNcL < oFyNcL) {
        return -1;
      }
      if (oFxNcL > oFyNcL) {
        return 1;
      }
    }
    return 0;
  }

  // @ts-ignore
  $.extend( $.fn.dataTableExt.oSort, {
    "natural-asc"( a, b ) {
      return naturalSort(a, b, true);
    },

    "natural-desc"( a, b ) {
      return naturalSort(a, b, true) * -1;
    },

    "natural-nohtml-asc"( a, b ) {
      return naturalSort(a, b, false);
    },

    "natural-nohtml-desc"( a, b ) {
      return naturalSort(a, b, false) * -1;
    },

    "natural-ci-asc"( a, b ) {
      a = a.toString().toLowerCase();
      b = b.toString().toLowerCase();

      return naturalSort(a, b, true);
    },

    "natural-ci-desc"( a, b ) {
      a = a.toString().toLowerCase();
      b = b.toString().toLowerCase();

      return naturalSort(a, b, true) * -1;
    }
  } );

}());
