import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {NgForm} from "@angular/forms";

import {Reduce} from "../../../../_utils/lists/reduce";

@Component({
  selector: 'app-input-search',
  templateUrl: './input-search.component.html',
  styles: [
  ]
})
export class InputSearchComponent implements OnInit {

  // Essentials
  @Input() id: string;                                    // Unique ID
  @Input() form: NgForm;                                  // Form it's part of
  @Input() value: string;                                 // Where to store the value
  @Input() placeholder: string = 'Search';                // Message to show by default
  @Input() items: any[];                                  // Items to search

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';       // Size
  @Input() color?: string;                                // Color
  @Input() classList?: string;                            // Classes to add
  @Input() disabled?: boolean;                            // Make it disabled

  @Output() valueChange = new EventEmitter<string>();
  @Output() btnClicked = new EventEmitter<void>();
  @Output() onSearch = new EventEmitter<any[]>();

  reduce = new Reduce();

  constructor() { }

  ngOnInit(): void {
  }

  search(): void {
    this.reduce.search(this.items, this.value);
    this.onSearch.emit(this.reduce.items);
  }

}
