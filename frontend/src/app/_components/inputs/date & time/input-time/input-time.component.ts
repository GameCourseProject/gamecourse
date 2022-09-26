import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

import { InputColor, InputGroupLabelColor } from '../../InputColors';
import { InputGroupSize, InputSize } from '../../InputSizes';

@Component({
  selector: 'app-input-time',
  templateUrl: './input-time.component.html'
})
export class InputTimeComponent implements OnInit {

  TIME_PATTERN = '^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$';

  // Essentials
  @Input() id: string;                                                                  // Unique ID
  @Input() form: NgForm;                                                                // Form it's part of
  @Input() value: string;                                                               // Where to store the value
  @Input() placeholder: string = 'HH:mm';                                               // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                                     // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |                       // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                                          // Classes to add
  @Input() disabled?: boolean;                                                          // Make it disabled

  @Input() topLabel?: string;                                                           // Top label text
  @Input() leftLabel?: string;                                                          // Text on prepended label

  @Input() helperText?: string;                                                         // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';                        // Helper position

  // Validity
  @Input() pattern?: string = this.TIME_PATTERN;                                        // The pattern to be applied
  @Input() required?: boolean;                                                          // Make it required

  // Errors
  @Input() patternErrorMessage?: string = 'Invalid time. Valid format: HH:mm';          // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                                  // Message for required error
  @Input() incorrectErrorMessage?: string;                                              // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();

  @ViewChild('inputTime', { static: false }) inputTime: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputTime);
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
