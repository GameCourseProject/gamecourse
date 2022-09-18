import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";
import { InputGroupSize, InputSize } from '../../Settings';

@Component({
  selector: 'app-input-date',
  templateUrl: './input-date.component.html',
  styles: [
  ]
})
export class InputDateComponent implements OnInit, AfterViewInit {

  DATE_PATTERN = '^(0?[1-9]|[12][0-9]|3[01])\\/(0?[1-9]|1[012])\\/\\d{4}$';

  // Essentials
  @Input() id: string;                                                                  // Unique ID
  @Input() form: NgForm;                                                                // Form it's part of
  @Input() value: string;                                                               // Where to store the value
  @Input() placeholder: string = 'DD/MM/YYYY';                                          // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                                     // Size
  @Input() color?: string;                                                              // Color
  @Input() classList?: string;                                                          // Classes to add
  @Input() disabled?: boolean;                                                          // Make it disabled

  @Input() topLabel?: string;                                                           // Top label text
  @Input() leftLabel?: string;                                                          // Text on prepended label

  // Validity
  @Input() pattern?: string = this.DATE_PATTERN;                                        // The pattern to be applied
  @Input() required?: boolean;                                                          // Make it required

  // Errors
  @Input() patternErrorMessage?: string = 'Invalid date. Valid format: dd/mm/yyyy';     // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                                  // Message for required error
  @Input() incorrectErrorMessage?: string;                                              // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();

  @ViewChild('inputDate', { static: false }) inputDate: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputDate);
  }

  get InputSize(): typeof InputSize {
    return InputSize;
  }

  get InputGroupSize(): typeof InputGroupSize {
    return InputGroupSize;
  }

}
