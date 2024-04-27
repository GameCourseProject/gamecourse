import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

import { InputSize, InputGroupSize } from '../../InputSizes';
import {InputColor, InputGroupBtnColor, InputGroupLabelColor } from "../../InputColors";

import Pickr from "@simonwep/pickr";

@Component({
  selector: 'app-input-color',
  templateUrl: './input-color.component.html'
})
export class InputColorComponent implements OnInit, AfterViewInit {

  COLOR_PATTERN = '^#[\\dA-Fa-f]{6}$';

  // Essentials
  @Input() id: string;                                                // Unique ID
  @Input() form: NgForm;                                              // Form it's part of
  @Input() value: string;                                             // Where to store the value
  @Input() placeholder: string = '#ffffff';                           // Message to show by default

  @Input() multiple?: boolean = false;                                // Allow several colors instead of just one
  @Input() values?: string[] = [];                                    // Keep the several colors

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                   // Size FIXME: not working
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |     // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                        // Classes to add
  @Input() disabled?: boolean;                                        // Make it disabled
  @Input() colors?: string[];                                         // Custom swatches of colors

  @Input() topLabel?: string;                                         // Top label text
  @Input() leftLabel?: string;                                        // Text on prepended label

  @Input() helperText?: string;                                       // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';      // Helper position

  @Input() position?: 'top' | 'end';                                  // Position of the picker

  // Validity
  @Input() pattern?: string = this.COLOR_PATTERN;                     // The pattern to be applied
  @Input() required?: boolean;                                        // Make it required
  @Input() minLength?: number = 7;                                    // Enforce a minimum length
  @Input() maxLength?: number | null = 7;                             // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string =
    'Invalid color format. Valid format: #XXXXXX';                    // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                // Message for required error
  @Input() minLengthErrorMessage?: string =
    'Invalid color format. Valid format: #XXXXXX';                    // Message for minLength error
  @Input() maxLengthErrorMessage?: string =
    'Invalid color format. Valid format: #XXXXXX';                    // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                            // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();
  @Output() valuesChange = new EventEmitter<string[]>();

  @ViewChild('inputColor', { static: false }) inputColor: NgModel;

  pickr: Pickr;

  show: boolean = false;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputColor);
    setTimeout(() => this.initPicker(), 0);
  }

  initPicker() {
    this.pickr = Pickr.create({
      el: '#' + this.id,
      container: '#color-picker-' + this.id,
      theme: 'monolith',
      appClass: 'relative !top-0 !left-0 !shadow-none',
      useAsButton: true,
      default: this.value ? this.value : this.required ? "#ffffff" : null,
      autoReposition: false,
      lockOpacity: true,
      comparison: false,
      components: {
        preview: true,
        opacity: false,
        hue: true,
      },
      swatches: this.colors ?? [
        '#E57373', '#EF5350', '#F44336', '#E53935', '#D32F2F', '#C62828', '#B71C1C',
        '#F06292', '#EC407A', '#E91E63', '#D81B60', '#C2185B', '#AD1457', '#880E4F',
        '#BA68C8', '#AB47BC', '#9C27B0', '#8E24AA', '#7B1FA2', '#6A1B9A', '#4A148C',
        '#9575CD', '#7E57C2', '#673AB7', '#5E35B1', '#512DA8', '#4527A0', '#311B92',
        '#7986CB', '#5C6BC0', '#3F51B5', '#3949AB', '#303F9F', '#283593', '#1A237E',
        '#64B5F6', '#42A5F5', '#2196F3', '#1E88E5', '#1976D2', '#1565C0', '#0D47A1',
        '#4FC3F7', '#29B6F6', '#03A9F4', '#039BE5', '#0288D1', '#0277BD', '#01579B',
        '#4DD0E1', '#26C6DA', '#00BCD4', '#00ACC1', '#0097A7', '#00838F', '#006064',
        '#4DB6AC', '#26A69A', '#009688', '#00897B', '#00796B', '#00695C', '#004D40',
        '#81C784', '#66BB6A', '#4CAF50', '#43A047', '#388E3C', '#2E7D32', '#1B5E20',
        '#AED581', '#9CCC65', '#8BC34A', '#7CB342', '#689F38', '#558B2F', '#33691E',
        '#DCE775', '#D4E157', '#CDDC39', '#C0CA33', '#AFB42B', '#9E9D24', '#827717',
        '#FFF176', '#FFEE58', '#FFEB3B', '#FDD835', '#FBC02D', '#F9A825', '#F57F17',
        '#FFD54F', '#FFCA28', '#FFC107', '#FFB300', '#FFA000', '#FF8F00', '#FF6F00',
        '#FFB74D', '#FFA726', '#FF9800', '#FB8C00', '#F57C00', '#EF6C00', '#E65100',
        '#FF8A65', '#FF7043', '#FF5722', '#F4511E', '#E64A19', '#D84315', '#BF360C'
      ],

    }).on('init', pickr => {
      if (pickr.getSelectedColor() && this.pattern != "")
        this.valueChange.emit(pickr.getSelectedColor().toHEXA().toString(0));

    }).on('change', color => {
      this.valueChange.emit(color.toHEXA().toString(0));

    }).on('show', (color, pickr) => {
      if (!this.show) pickr.hide();

    }).on('hide', pickr => {
      if (this.show) pickr.show();

    });
  }

  openPicker() {
    this.show = true;
    this.pickr.show();

    const picker = document.getElementById('color-picker-' + this.id);
    picker.classList.remove('hidden');
  }

  closePicker() {
    this.show = false;
    this.pickr?.hide();

    const picker = document.getElementById('color-picker-' + this.id);
    picker.classList.add('hidden');
  }

  setColor(value: string) {
    this.pickr.setColor(value)
    this.valueChange.emit(value);
  }

  addInput() {
    this.values.push(this.value);
    this.valuesChange.emit(this.values);
    this.value = null;
  }

  removeColor(index: number) {
    this.values.splice(index, 1);
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

  get InputGroupBtnColor(): typeof InputGroupBtnColor {
    return InputGroupBtnColor;
  }

  get InputGroupLabelColor(): typeof InputGroupLabelColor {
    return InputGroupLabelColor;
  }
}
