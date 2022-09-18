import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";
import {InputGroupSize, InputSize } from '../../Settings';

@Component({
  selector: 'app-input-select',
  templateUrl: './input-select.component.html'
})
export class InputSelectComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                    // Unique ID
  @Input() form: NgForm;                                  // Form it's part of
  @Input() value: string;                                 // Where to store the value
  @Input() options?: {value: any, text: string}[];        // Options to select from
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

  // Errors
  @Input() requiredErrorMessage?: string;                 // Message for required error

  @Output() valueChange = new EventEmitter<any>();
  @Output() btnClicked = new EventEmitter<void>();

  @ViewChild('inputSelect', { static: false }) inputSelect: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputSelect);
  }

  get InputSize(): typeof InputSize {
    return InputSize;
  }

  get InputGroupSize(): typeof InputGroupSize {
    return InputGroupSize;
  }

}
