import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

import { TextareaSize } from '../../Settings';

@Component({
  selector: 'app-input-textarea',
  templateUrl: './input-textarea.component.html'
})
export class InputTextareaComponent implements OnInit {

  // Essentials
  @Input() id: string;                                    // Unique ID
  @Input() form: NgForm;                                  // Form it's part of
  @Input() value: string;                                 // Where to store the value
  @Input() placeholder: string;                           // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';       // Size
  @Input() color?: string;                                // Color
  @Input() classList?: string;                            // Classes to add
  @Input() disabled?: boolean;                            // Make it disabled
  @Input() label?: string;                                // Top label text

  // Validity
  @Input() pattern?: string;                              // The pattern to be applied
  @Input() required?: boolean;                            // Make it required
  @Input() minLength?: number;                            // Enforce a minimum length
  @Input() maxLength?: number;                            // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string;                  // Message for pattern error
  @Input() requiredErrorMessage?: string;                 // Message for required error
  @Input() minLengthErrorMessage?: string;                // Message for minLength error
  @Input() maxLengthErrorMessage?: string;                // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();
  @Output() btnClicked = new EventEmitter<void>();

  @ViewChild('inputTextarea', { static: false }) inputTextarea: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputTextarea);
  }

  get TextareaSize(): typeof TextareaSize {
    return TextareaSize;
  }

}
