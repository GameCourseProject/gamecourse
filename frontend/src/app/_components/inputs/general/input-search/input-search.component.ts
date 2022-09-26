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
  @Input() placeholder: string = 'Search...';                               // Message to show by default
  @Input() items: any[];                                                    // Items to search

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                         // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |           // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                              // Classes to add
  @Input() disabled?: boolean;                                              // Make it disabled

  @Input() topLabel?: string;                                               // Top label text
  @Input() leftLabel?: string;                                              // Text on prepended label

  @Input() helperText?: string;                                             // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';            // Helper position

  // Validity
  @Input() pattern?: string;                                                // The pattern to be applied
  @Input() required?: boolean;                                              // Make it required
  @Input() minLength?: number;                                              // Enforce a minimum length
  @Input() maxLength?: number;                                              // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string;                                    // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                      // Message for required error
  @Input() minLengthErrorMessage?: string;                                  // Message for minLength error
  @Input() maxLengthErrorMessage?: string;                                  // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                                  // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();
  @Output() btnClicked = new EventEmitter<void>();

  @Output() onSearch = new EventEmitter<any[]>();

  query: string;
  reduce = new Reduce();

  constructor() { }

  ngOnInit(): void {
    // Init default min/max length error messaged
    if (!this.minLengthErrorMessage) this.minLengthErrorMessage = 'Query size needs to be greater than or equal to ' + this.minLength;
    if (!this.maxLengthErrorMessage) this.maxLengthErrorMessage = 'Query size needs to be smaller than or equal to ' + this.maxLength;
  }

  search(): void {
    this.reduce.search(this.items, this.query);
    this.onSearch.emit(this.reduce.items);
  }

}
