import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {NgForm} from "@angular/forms";

@Component({
  selector: 'app-input-select-weekday',
  templateUrl: './input-select-weekday.component.html'
})
export class InputSelectWeekdayComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                // Unique ID
  @Input() form: NgForm;                                              // Form it's part of
  @Input() value: string;                                             // Where to store the value
  @Input() placeholder: string = 'Select a weekday';                  // Message to show by default

  @Input() multiple?: boolean;                                        // Whether to allow multiple selects
  @Input() limit?: number;                                            // Multiple selection limit
  @Input() closeOnSelect?: boolean = true;                            // Whether to close upon selecting a value
  @Input() hideSelectedOption?: boolean = true;                       // Hide selected options

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                   // Size  FIXME: not working
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |     // Color FIXME: not working
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

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                // Message for required error

  @Output() valueChange = new EventEmitter<number[]>();
  @Output() btnClicked = new EventEmitter<number[]>();

  weekdays: {value: string, text: string}[] = [
    {value: 'd-1', text: 'Monday'},
    {value: 'd-2', text: 'Tuesday'},
    {value: 'd-3', text: 'Wednesday'},
    {value: 'd-4', text: 'Thursday'},
    {value: 'd-5', text: 'Friday'},
    {value: 'd-6', text: 'Saturday'},
    {value: 'd-0', text: 'Sunday'},
  ]

  constructor() { }

  ngOnInit(): void {
  }

}
