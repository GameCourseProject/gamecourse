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
  @Input() placeholder: string = 'Day of the week';                   // Message to show by default

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

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                // Message for required error

  @Output() valueChange = new EventEmitter<number>();
  @Output() btnClicked = new EventEmitter<void>();

  weekdays: {value: number, text: string}[] = [
    {value: 1, text: 'Monday'},
    {value: 2, text: 'Tuesday'},
    {value: 3, text: 'Wednesday'},
    {value: 4, text: 'Thursday'},
    {value: 5, text: 'Friday'},
    {value: 6, text: 'Saturday'},
    {value: 0, text: 'Sunday'},
  ]

  constructor() { }

  ngOnInit(): void {
  }

}
