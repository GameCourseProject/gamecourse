import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {NgForm} from "@angular/forms";

@Component({
  selector: 'app-input-email',
  templateUrl: './input-email.component.html',
  styles: [
  ]
})
export class InputEmailComponent implements OnInit {

  EMAIL_PATTERN = '^[\\w-\\.]+@([\\w-]+\\.)+[\\w-]{2,4}$';

  // Essentials
  @Input() id: string;                                              // Unique ID
  @Input() form: NgForm;                                            // Form it's part of
  @Input() value: string;                                           // Where to store the value
  @Input() placeholder: string = 'email@example.com';               // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                 // Size
  @Input() color?: string;                                          // Color
  @Input() classList?: string;                                      // Classes to add
  @Input() disabled?: boolean;                                      // Make it disabled

  @Input() topLabel?: string;                                       // Top label text
  @Input() leftLabel?: string = 'Email';                            // Text on prepended label

  // Validity
  @Input() pattern?: string = this.EMAIL_PATTERN;                   // The pattern to be applied
  @Input() required?: boolean;                                      // Make it required
  @Input() minLength?: number;                                      // Enforce a minimum length
  @Input() maxLength?: number;                                      // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string = 'Invalid email format';                                                   // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                                                              // Message for required error
  @Input() minLengthErrorMessage?: string = 'Email size needs to be greater than or equal to ' + this.minLength;    // Message for minLength error
  @Input() maxLengthErrorMessage?: string = 'Email size needs to be smaller than or equal to ' + this.maxLength;    // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                                                                          // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();

  constructor() { }

  ngOnInit(): void {
  }

}
