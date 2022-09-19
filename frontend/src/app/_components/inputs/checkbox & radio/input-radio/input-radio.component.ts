import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";
import { RadioSize } from '../../InputSizes';

@Component({
  selector: 'app-input-radio',
  templateUrl: './input-radio.component.html'
})
export class InputRadioComponent implements OnInit {

  // Essentials
  @Input() id: string;                                    // Unique ID
  @Input() group: string;                                 // Radio group name
  @Input() form: NgForm;                                  // Form it's part of
  @Input() optionValue: any;                              // Option value (value to return)
  @Input() value: any;                                    // Where to store the value

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';       // Size
  @Input() color?: string;                                // Color
  @Input() classList?: string;                            // Classes to add
  @Input() disabled?: boolean;                            // Make it disable

  @Input() label?: string;                                // Label text

  // Validity
  @Input() required?: boolean;                            // Make it required

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';    // Message for required error

  @Output() valueChange = new EventEmitter<any>();

  @ViewChild('radio', { static: false }) radio: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.radio);
  }

  get RadioSize(): typeof RadioSize {
    return RadioSize;
  }

}
