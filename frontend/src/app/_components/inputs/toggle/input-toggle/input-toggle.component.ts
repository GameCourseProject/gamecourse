import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

@Component({
  selector: 'app-input-toggle',
  templateUrl: './input-toggle.component.html'
})
export class InputToggleComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                                // Unique ID
  @Input() form: NgForm;                                              // Form it's part of
  @Input() value: boolean;                                            // Where to store the value

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                   // Size
  @Input() color?: 'primary' | 'secondary' | 'accent';                // Color
  @Input() classList?: string;                                        // Classes to add
  @Input() disabled?: boolean;                                        // Make it disabled

  @Input() label?: string;                                            // Label text
  @Input() labelPosition?: 'left' | 'right' = 'left';                 // Label position

  @Input() helperText?: string;                                       // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';      // Helper position

  // Validity
  @Input() required?: boolean;                                        // Make it required

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                // Message for required error

  @Output() valueChange = new EventEmitter<boolean>();

  @ViewChild('toggle', { static: false }) toggle: NgModel;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.toggle);
  }

  get ToggleSize(): typeof ToggleSize {
    return ToggleSize;
  }

  get ToggleColor(): typeof ToggleColor {
    return ToggleColor;
  }

}

enum ToggleSize {
  xs = 'toggle-xs',
  sm = 'toggle-sm',
  md = 'toggle-md',
  lg = 'toggle-lg'
}

enum ToggleColor {
  primary = 'toggle-primary',
  secondary = 'toggle-secondary',
  accent = 'toggle-accent'
}
