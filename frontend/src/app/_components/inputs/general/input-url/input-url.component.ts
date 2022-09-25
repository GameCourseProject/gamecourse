import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {NgForm} from "@angular/forms";

@Component({
  selector: 'app-input-url',
  templateUrl: './input-url.component.html'
})
export class InputUrlComponent implements OnInit {

  URL_PATTERN = '(https?:\\/\\/)?(www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b([-a-zA-Z0-9()@:%_\\+.~#?&//=]*)';

  // Essentials
  @Input() id: string;                                                    // Unique ID
  @Input() form: NgForm;                                                  // Form it's part of
  @Input() value: string;                                                 // Where to store the value
  @Input() placeholder: string = 'www.website.com';                       // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                       // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |         // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                            // Classes to add
  @Input() disabled?: boolean;                                            // Make it disabled

  @Input() topLabel?: string;                                             // Top label text
  @Input() leftLabel?: string = 'https://';                               // Text on prepended label

  // Validity
  @Input() pattern?: string = this.URL_PATTERN;                           // The pattern to be applied
  @Input() required?: boolean;                                            // Make it required
  @Input() minLength?: number;                                            // Enforce a minimum length
  @Input() maxLength?: number;                                            // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string = 'Invalid URL format';           // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                    // Message for required error
  @Input() minLengthErrorMessage?: string;                                // Message for minLength error
  @Input() maxLengthErrorMessage?: string;                                // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                                // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();

  constructor() { }

  ngOnInit(): void {
    // Init default min/max length error messaged
    if (!this.minLengthErrorMessage) this.minLengthErrorMessage = 'URL size needs to be greater than or equal to ' + this.minLength;
    if (!this.maxLengthErrorMessage) this.maxLengthErrorMessage = 'URL size needs to be smaller than or equal to ' + this.maxLength;
  }

}
