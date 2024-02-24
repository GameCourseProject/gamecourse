import {AfterViewInit, Component, ElementRef, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm} from '@angular/forms';

import { InputGroupSize, InputSize } from '../../InputSizes';
import { InputColor, InputGroupBtnColor, InputGroupLabelColor } from "../../InputColors";

@Component({
  selector: 'app-input-notification-text',
  templateUrl: './input-notification-text.component.html',
})
export class InputNotificationTextComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                                        // Unique ID
  @Input() form: NgForm;                                                      // Form it's part of
  @Input() value: string;                                                     // Where to store the value
  @Input() placeholder: string;                                               // Message to show by default

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                           // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |             // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                                // Classes to add
  @Input() disabled?: boolean;                                                // Make it disabled

  @Input() topLabel?: string;                                                 // Top label text
  @Input() leftLabel?: string;                                                // Text on prepended label
  @Input() rightLabel?: string;                                               // Text on appended label

  @Input() btnText?: string;                                                  // Text on appended button
  @Input() btnIcon?: string;                                                  // Icon on appended button

  @Input() helperText?: string;                                               // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';              // Helper position

  // Validity
  @Input() pattern?: string;                                                  // The pattern to be applied
  @Input() required?: boolean;                                                // Make it required
  @Input() minLength?: number;                                                // Enforce a minimum length
  @Input() maxLength?: number;                                                // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string;                                      // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                        // Message for required error
  @Input() minLengthErrorMessage?: string;                                    // Message for minLength error
  @Input() maxLengthErrorMessage?: string;                                    // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                                    // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();
  @Output() btnClicked = new EventEmitter<void>();

  @ViewChild('inputText', { static: false }) inputText: ElementRef;

  constructor() {
  }

  ngOnInit(): void {
    // Init default min/max length error messaged
    if (!this.minLengthErrorMessage) this.minLengthErrorMessage = 'Entry size needs to be greater than or equal to ' + this.minLength;
    if (!this.maxLengthErrorMessage) this.maxLengthErrorMessage = 'Entry size needs to be smaller than or equal to ' + this.maxLength;
  }

  ngAfterViewInit(): void {
    const regex = /(%\S+)\s/g;

    // Wrap all matches with the specified HTML tag
    this.inputText.nativeElement.innerHTML = this.inputText.nativeElement.innerHTML
      .replace(regex, `<div contenteditable="false" class="inline-block rounded-full px-2 bg-base-300">$1</div> `);
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
