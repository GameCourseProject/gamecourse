import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";
import Pickr from "@simonwep/pickr";

@Component({
  selector: 'app-input-color',
  templateUrl: './input-color.component.html',
  styleUrls: ['./input-color.component.scss']
})
export class InputColorComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                        // Unique id
  @Input() form?: NgForm;                     // Form it's part of
  @Input() value: string;                     // Where to store the value
  @Input() placeholder?: string = "Color";    // Message to show by default

  // Extras
  @Input() label?: string = "Color";          // Label prepend
  @Input() default?: string = "white";        // Default value
  @Input() classList?: string;                // Classes to add
  @Input() disabled?: boolean;                // Make it disabled

  // Validity
  @Input() required?: boolean;                // Make it required

  // Errors
  @Input() requiredErrorMessage?: string;     // Message for required error

  @Output() valueChange = new EventEmitter<string>();

  @ViewChild('inputColor', { static: false }) inputColor: NgModel;

  pickr: Pickr;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputColor);

    // Init color picker
    setTimeout(() => {
      this.pickr = Pickr.create({
        el: '#' + this.id,
        useAsButton: true,
        default: this.value ?? this.default,
        theme: 'monolith',
        components: {
          hue: true,
          interaction: {
            input: true,
            save: true
          }
        }

      }).on('init', pickr => {
        this.valueChange.emit(pickr.getSelectedColor().toHEXA().toString(0));

      }).on('save', color => {
        this.valueChange.emit(color.toHEXA().toString(0));
        this.pickr.hide();

      }).on('change', color => {
        this.valueChange.emit(color.toHEXA().toString(0));
      });
    }, 0);
  }

  isWhite(color: string): boolean {
    if (!color) return false;
    return ['white', '#ffffff', '#fff'].includes(color.toLowerCase());
  }
}
