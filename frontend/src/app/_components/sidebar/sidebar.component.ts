import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';

@Component({
  selector: 'app-sidebar',
  templateUrl: './sidebar.component.html',
  styleUrls: ['./sidebar.component.scss']
})
export class SidebarComponent implements OnInit {

  @Input() filters: string[];
  @Input() orderBy: string[];

  @Output() onSearch: EventEmitter<string> = new EventEmitter();
  @Output() onFilterChange: EventEmitter<{filter: string, state: boolean }> = new EventEmitter();
  @Output() onOrderChange: EventEmitter<{orderBy: string, sort: number}> = new EventEmitter();

  searchInput: string;
  filtersInput: {filter: string, state: boolean}[] = [];
  orderInput: string;
  sortInput: number = 1; // 1: ascending, -1: descending

  constructor() { }

  ngOnInit(): void {
    if (this.filters)
      this.filters.forEach(filter => this.filtersInput.push({filter: filter, state: true}));

    if (this.orderBy && this.orderBy.length > 0)
      this.orderInput = this.orderBy[0];
  }

}
