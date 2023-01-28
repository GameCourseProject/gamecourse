import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {NgForm} from "@angular/forms";
import { InputColor, InputGroupBtnColor, InputGroupLabelColor } from '../../InputColors';
import { InputGroupSize } from '../../InputSizes';

@Component({
  selector: 'app-input-periodicity',
  templateUrl: './input-periodicity.component.html'
})
export class InputPeriodicityComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                    // Unique ID
  @Input() form: NgForm;                                                  // Form it's part of
  @Input() value: {number: number, time: string};                         // Where to store the value
  @Input() filterOptions?: string[];                                      // Options to be filtered out

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                       // Size  FIXME: not working
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |         // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                            // Classes to add
  @Input() disabled?: boolean;                                            // Make it disabled

  @Input() topLabel?: string;                                             // Top label text
  @Input() leftLabel?: string;                                            // Text on prepended label
  @Input() rightLabel?: string;                                           // Text on appended label

  @Input() btnText?: string;                                              // Text on appended button
  @Input() btnIcon?: string;                                              // Icon on appended button                                                       // Text on appended button

  @Input() helperText?: string;                                           // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';          // Helper position

  // Validity
  @Input() required?: boolean;                                            // Make it required
  @Input() minNumber?: number;                                            // Enforce a minimum number
  @Input() maxNumber?: number;                                            // Enforce a maximum number

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                    // Message for required error
  @Input() minValueErrorMessage?: string;                                 // Message for minValue error
  @Input() maxValueErrorMessage?: string;                                 // Message for maxValue error

  @Output() valueChange = new EventEmitter<{number: number, time: string}>();
  @Output() btnClicked = new EventEmitter<{number: number, time: string}>();

  number: number;
  time: string;

  periods: {value: string, text: string}[] = [
    {value: 'second', text: 'Second(s)'},
    {value: 'minute', text: 'Minute(s)'},
    {value: 'hour', text: 'Hour(s)'},
    {value: 'day', text: 'Day(s)'},
    {value: 'week', text: 'Week(s)'},
    {value: 'month', text: 'Month(s)'},
    {value: 'year', text: 'Year(s)'},
  ]

  constructor() { }

  ngOnInit(): void {
    // Set initial value
    if (this.value) {
      this.number = this.value.number;
      this.time = this.value.time;
    }

    // Filter options
    if (this.filterOptions?.length > 0)
      this.periods = this.periods.filter(option => !this.filterOptions.includes(option.value))

    // Init default min/max value error messages
    if (!this.minValueErrorMessage) this.minValueErrorMessage = 'Number needs to be greater than or equal to ' + this.minNumber;
    if (!this.maxValueErrorMessage) this.maxValueErrorMessage = 'Number needs to be smaller than or equal to ' + this.maxNumber;
  }

  emit(emitter: EventEmitter<{number: number, time: string}>): void {
    if ((this.number == 0 || this.number) && this.time) {
      this.value = {number: this.number, time: this.time};
      emitter.emit(this.value);
    }
  }

  get InputGroupSize(): typeof InputGroupSize {
    return InputGroupSize;
  }

  get InputColor(): typeof InputColor {
    return InputColor;
  }

  get InputGroupBtnColor(): typeof InputGroupBtnColor {
    return InputGroupBtnColor;
  }

  get InputGroupLabelColor(): typeof InputGroupLabelColor {
    return InputGroupLabelColor;
  }

}
