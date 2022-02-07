import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

@Component({
  selector: 'app-input-text',
  templateUrl: './input-text.component.html',
  styleUrls: ['./input-text.component.scss']
})
export class InputTextComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                        // Unique id
  @Input() form: NgForm;                      // Form it's part of
  @Input() value: string;                     // Where to store the value
  @Input() placeholder: string;               // Message to show by default

  // Extras
  @Input() label?: string;                    // Label prepend
  @Input() classList?: string;                // Classes to add
  @Input() disabled?: boolean;                // Make it disabled

  // Validity
  @Input() pattern?: string;                  // The pattern to be applied
  @Input() required?: boolean;                // Make it required
  @Input() minLength?: number;                // Enforce a minimum length
  @Input() maxLength?: number;                // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string;      // Message for pattern error
  @Input() requiredErrorMessage?: string;     // Message for required error
  @Input() minLengthErrorMessage?: string;    // Message for minLength error
  @Input() maxLengthErrorMessage?: string;    // Message for maxLength error

  @Output() valueChange = new EventEmitter<string>();
  @Output() onEnter = new EventEmitter<string>();

  @ViewChild('inputText', { static: false }) inputText: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputText);
  }

  enter() {
    this.onEnter.emit(this.value);
  }

}
