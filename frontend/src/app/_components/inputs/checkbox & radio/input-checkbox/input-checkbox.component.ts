import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

@Component({
  selector: 'app-input-checkbox',
  templateUrl: './input-checkbox.component.html'
})
export class InputCheckboxComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                    // Unique ID
  @Input() form: NgForm;                                  // Form it's part of
  @Input() value: boolean;                                // Where to store the value

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';       // Size
  @Input() color?: 'primary' | 'secondary' | 'accent';    // Color
  @Input() classList?: string;                            // Classes to add
  @Input() disabled?: boolean;                            // Make it disabled

  @Input() label?: string;                                // Label text

  // Validity
  @Input() required?: boolean;                            // Make it required

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';    // Message for required error

  @Output() valueChange = new EventEmitter<boolean>();

  @ViewChild('checkbox', { static: false }) checkbox: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.checkbox);
  }

  get CheckboxSize(): typeof CheckboxSize {
    return CheckboxSize;
  }

  get CheckboxColor(): typeof CheckboxColor {
    return CheckboxColor;
  }

}

enum CheckboxSize {
  xs = 'checkbox-xs',
  sm = 'checkbox-sm',
  md = 'checkbox-md',
  lg = 'checkbox-lg'
}

enum CheckboxColor {
  primary = 'checkbox-primary',
  secondary = 'checkbox-secondary',
  accent = 'checkbox-accent'
}
