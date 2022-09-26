import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

import { InputColor, InputGroupLabelColor } from '../../InputColors';
import { InputGroupSize, InputSize } from '../../InputSizes';

@Component({
  selector: 'app-input-datetime',
  templateUrl: './input-datetime.component.html'
})
export class InputDatetimeComponent implements OnInit {

  DATETIME_PATTERN = '^\\d{4}-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])T([0-1]?[0-9]|2[0-3]):[0-5][0-9]$';

  // Essentials
  @Input() id: string;                                                          // Unique ID
  @Input() form: NgForm;                                                        // Form it's part of
  @Input() value: string;                                                       // Where to store the value
  @Input() placeholder: string = 'dd/mm/yyyy, hh:mm:ss';

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                             // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |               // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                                  // Classes to add
  @Input() disabled?: boolean;

  @Input() topLabel?: string;                                                   // Top label text
  @Input() leftLabel?: string;                                                  // Text on prepended label

  @Input() helperText?: string;                                                 // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';                // Helper position

  // Validity
  @Input() pattern?: string = this.DATETIME_PATTERN;                            // The pattern to be applied
  @Input() required?: boolean;

  // Errors
  @Input() patternErrorMessage?: string =
    'Invalid datetime. Valid format: dd/mm/yyyy, hh:mm:ss';                     // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                          // Message for required error
  @Input() incorrectErrorMessage?: string;

  @Output() valueChange = new EventEmitter<string>();

  @ViewChild('inputDateTime', { static: false }) inputDateTime: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputDateTime);
  }

  get InputSize(): typeof InputSize {
    return InputSize;
  }

  get InputGroupSize(): typeof InputGroupSize {
    return InputGroupSize;
  }

  get InputColor(): typeof InputColor {
    return InputColor;
  }

  get InputGroupLabelColor(): typeof InputGroupLabelColor {
    return InputGroupLabelColor;
  }

}
