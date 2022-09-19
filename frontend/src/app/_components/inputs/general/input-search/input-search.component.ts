import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {NgForm} from "@angular/forms";

import {Reduce} from "../../../../_utils/lists/reduce";

@Component({
  selector: 'app-input-search',
  templateUrl: './input-search.component.html'
})
export class InputSearchComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                      // Unique ID
  @Input() form: NgForm;                                                    // Form it's part of
  @Input() value: string;                                                   // Where to store the value
  @Input() placeholder: string = 'Search';                                  // Message to show by default
  @Input() items: any[];                                                    // Items to search

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                         // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |           // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                              // Classes to add
  @Input() disabled?: boolean;                                              // Make it disabled

  @Input() topLabel?: string;                                               // Top label text
  @Input() leftLabel?: string;                                              // Text on prepended label

  // Validity
  @Input() pattern?: string;                                                // The pattern to be applied
  @Input() required?: boolean;                                              // Make it required
  @Input() minLength?: number;                                              // Enforce a minimum length
  @Input() maxLength?: number;                                              // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string;                                    // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                      // Message for required error
  @Input() minLengthErrorMessage?: string =
    'Query size needs to be greater than or equal to ' + this.minLength;    // Message for minLength error
  @Input() maxLengthErrorMessage?: string =
    'Query size needs to be smaller than or equal to ' + this.maxLength;    // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                                  // Message for incorrect error

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
