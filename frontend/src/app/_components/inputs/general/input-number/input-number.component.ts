import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from '@angular/forms';

import { InputColor, InputGroupBtnColor, InputGroupLabelColor } from '../../InputColors';
import {InputGroupSize, InputSize } from '../../InputSizes';

@Component({
  selector: 'app-input-number',
  templateUrl: './input-number.component.html',
})
export class InputNumberComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                                // Unique ID
  @Input() form: NgForm;                                              // Form it's part of
  @Input() value: number;                                             // Where to store the value
  @Input() placeholder: string;                                       // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                   // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |     // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                        // Classes to add
  @Input() disabled?: boolean;                                        // Make it disabled

  @Input() topLabel?: string;                                         // Top label text
  @Input() leftLabel?: string;                                        // Text on prepended label
  @Input() rightLabel?: string;                                       // Text on appended label

  @Input() btnText?: string;                                          // Text on appended button
  @Input() btnIcon?: string;                                          // Icon on appended button

  // Validity
  @Input() required?: boolean;                                        // Make it required
  @Input() minValue?: number;                                         // Enforce a minimum value
  @Input() maxValue?: number;                                         // Enforce a maximum value

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                // Message for required error
  @Input() minValueErrorMessage?: string;                             // Message for minLength error
  @Input() maxValueErrorMessage?: string;                             // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                            // Message for incorrect error

  @Output() valueChange = new EventEmitter<number>();
  @Output() btnClicked = new EventEmitter<void>();

  @ViewChild('inputNumber', { static: false }) inputNumber: NgModel;

  constructor() {
  }

  ngOnInit(): void {
    // Init default min/max value error messages
    if (!this.minValueErrorMessage) this.minValueErrorMessage = 'Value needs to be greater than or equal to ' + this.minValue;
    if (!this.maxValueErrorMessage) this.maxValueErrorMessage = 'Value needs to be smaller than or equal to ' + this.maxValue;
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputNumber);
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

  get InputGroupBtnColor(): typeof InputGroupBtnColor {
    return InputGroupBtnColor;
  }

}
