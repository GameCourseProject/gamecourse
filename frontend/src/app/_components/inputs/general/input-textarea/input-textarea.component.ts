import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

@Component({
  selector: 'app-input-textarea',
  templateUrl: './input-textarea.component.html'
})
export class InputTextareaComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                      // Unique ID
  @Input() form: NgForm;                                                    // Form it's part of
  @Input() value: string;                                                   // Where to store the value
  @Input() placeholder: string;                                             // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                         // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |           // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                              // Classes to add
  @Input() disabled?: boolean;                                              // Make it disabled
  @Input() label?: string;                                                  // Top label text

  // Validity
  @Input() pattern?: string;                                                // The pattern to be applied
  @Input() required?: boolean;                                              // Make it required
  @Input() minLength?: number;                                              // Enforce a minimum length
  @Input() maxLength?: number;                                              // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string;                                    // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                      // Message for required error
  @Input() minLengthErrorMessage?: string;                                  // Message for minLength error
  @Input() maxLengthErrorMessage?: string;                                  // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                                  // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();
  @Output() btnClicked = new EventEmitter<void>();

  @ViewChild('inputTextarea', { static: false }) inputTextarea: NgModel;

  constructor() { }

  ngOnInit(): void {
    // Init default min/max length error messaged
    if (!this.minLengthErrorMessage) this.minLengthErrorMessage = 'Text size needs to be greater than or equal to ' + this.minLength;
    if (!this.maxLengthErrorMessage) this.maxLengthErrorMessage = 'Text size needs to be smaller than or equal to ' + this.maxLength;
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputTextarea);
  }

  get TextareaSize(): typeof TextareaSize {
    return TextareaSize;
  }

  get TextareaColor(): typeof TextareaColor {
    return TextareaColor;
  }

}

enum TextareaSize {
  xs = 'h-10',
  sm = 'h-20',
  md = 'h-32',
  lg = 'h-48'
}

enum TextareaColor {
  ghost = 'textarea-ghost',
  primary = 'textarea-primary',
  secondary = 'textarea-secondary',
  accent = 'textarea-accent',
  info = 'textarea-info',
  success = 'textarea-success',
  warning = 'textarea-warning',
  error = 'textarea-error'
}
