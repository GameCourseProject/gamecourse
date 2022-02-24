import {Component, EventEmitter, Input, OnInit, Output, SimpleChanges} from '@angular/core';

declare let $;

@Component({
  selector: 'app-datatable',
  templateUrl: './datatable.component.html',
  styleUrls: ['./datatable.component.scss']
})
export class DatatableComponent implements OnInit {

  @Input() id: string;
  @Input() classList?: string;

  @Input() headers: string[];
  @Input() footers?: string[];

  @Input() data: string[][];
  @Input() options?: any;

  @Input() loading: boolean;

  @Output() editBtnClicked: EventEmitter<number> = new EventEmitter<number>();
  @Output() deleteBtnClicked: EventEmitter<number> = new EventEmitter<number>();
  @Output() valueChanged: EventEmitter<{value: any, row: number, col: number}> = new EventEmitter<{value: any, row: number, col: number}>();

  datatable: DataTables.Api;
  defaultOptions = {
    orderCellsTop: true,
    fixedHeader: true,
    pagingType: "full_numbers"
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

      // Adding column filtering
      const that = this;
      $('#' + this.id + ' thead tr').clone(true).appendTo('#' + this.id + ' thead');
      $('#' + this.id + ' thead tr:eq(1) th').each( function (i) {
        var title = $(this).text();
        $(this).html( '<input type="text" class="database_search" placeholder="Search '+ title +'" />' );

        $( 'input', this ).on( 'keyup change', function () {
          if ( that.datatable.column(i).search() !== this.value ) {
            that.datatable
              .column(i)
              .search( this.value )
              .draw();
          }
        } );
      } );
    }, 0);
  }

}
