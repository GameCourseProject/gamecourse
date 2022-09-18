import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from '@angular/forms';
import {InputGroupSize, InputSize } from '../../Settings';

@Component({
  selector: 'app-input-number',
  templateUrl: './input-number.component.html',
})
export class InputNumberComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                    // Unique ID
  @Input() form: NgForm;                                  // Form it's part of
  @Input() value: number;                                 // Where to store the value
  @Input() placeholder: string;                           // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';       // Size
  @Input() color?: string;                                // Color
  @Input() classList?: string;                            // Classes to add
  @Input() disabled?: boolean;                            // Make it disabled

  @Input() topLabel?: string;                             // Top label text
  @Input() leftLabel?: string;                            // Text on prepended label
  @Input() rightLabel?: string;                           // Text on appended label

  @Input() btnText?: string;                              // Text on appended button
  @Input() btnIcon?: string;                              // Icon on appended button

  // Validity
  @Input() required?: boolean;                            // Make it required
  @Input() minValue?: number;                             // Enforce a minimum value
  @Input() maxValue?: number;                             // Enforce a maximum value

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                                                        // Message for required error
  @Input() minValueErrorMessage?: string = 'Value needs to be greater than or equal to ' + this.minValue;     // Message for minLength error
  @Input() maxValueErrorMessage?: string = 'Value needs to be smaller than or equal to ' + this.maxValue;     // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                                                                    // Message for incorrect error

  @Output() valueChange = new EventEmitter<number>();
  @Output() btnClicked = new EventEmitter<void>();

  @ViewChild('inputNumber', { static: false }) inputNumber: NgModel;

  constructor() {
  }

  ngOnInit(): void {
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

}
