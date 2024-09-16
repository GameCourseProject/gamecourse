import {
  AfterViewInit,
  Component,
  EventEmitter,
  Input,
  OnChanges,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild
} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";
import {Observable} from "rxjs";

import { InputGroupBtnColor, InputGroupLabelColor, InputSelectColor } from '../../InputColors';
import { InputGroupSize, InputSize } from '../../InputSizes';

import SlimSelect from "slim-select";

@Component({
  selector: 'app-input-select',
  templateUrl: './input-select.component.html'
})
export class InputSelectComponent implements OnInit, AfterViewInit, OnChanges {

  // Essentials
  @Input() id: string;                                                                         // Unique ID
  @Input() form: NgForm;                                                                       // Form it's part of
  @Input() value: any;                                                                         // Where to store the value
  @Input() options?: ({value: any, text: string, html?: string, display?: boolean} |           // Options to select from
                      {label: string, options: {value: string, text: string, html?: string}[]}
                     )[];
  @Input() placeholder: string;                                                     // Message to show by default

  @Input() multiple?: boolean;                                                      // Whether to allow multiple selects
  @Input() limit?: number;                                                          // Multiple selection limit
  @Input() search?: boolean = true;                                                 // Allow to search options
  @Input() closeOnSelect?: boolean = true;                                          // Whether to close upon selecting a value
  @Input() hideSelectedOption?: boolean = false;                                    // Hide selected option

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                                 // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |                   // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                                      // Classes to add
  @Input() disabled?: boolean;                                                      // Make it disabled

  @Input() topLabel?: string;                                                       // Top label text
  @Input() leftLabel?: string;                                                      // Text on prepended label
  @Input() leftLabelSize?: string;                                                  // Send a fixed width
  @Input() rightLabel?: string;                                                     // Text on appended label

  @Input() btnText?: string;                                                        // Text on appended button
  @Input() btnIcon?: string;                                                        // Icon on appended button

  @Input() helperText?: string;                                                     // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';                    // Helper position

  // Validity
  @Input() required?: boolean;                                                      // Make it required

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                              // Message for required error

  // Methods
  @Input() setData?: Observable<{value: string, text: string, html?: string,        // Set data on demand
    selected: boolean}[]>;

  @Output() valueChange = new EventEmitter<any | any[]>();
  @Output() btnClicked = new EventEmitter<any | any[]>();

  @ViewChild('inputSelect', { static: false }) inputSelect: NgModel;

  select: SlimSelect;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputSelect);
    setTimeout(() => {
      this.initSelect();
    }, 0);

    const selectValidator = (control) => {
      if (this.value) {
        return null;  // Valid
      } else {
        return { required: true };  // Invalid
      }
    };

    const validators = [
      this.required ? selectValidator : null
    ].filter(validator => validator !== null);

    this.inputSelect.control.setValidators(validators);
    this.inputSelect.control.updateValueAndValidity();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes.value && !changes.value.firstChange) {
      if (this.multiple && changes.value.currentValue?.length === 0)
        this.select.setData(this.options);

      if (!this.multiple && !this.required) {
        const deselect = $('.ss-deselect')[0];
        if (this.value === 'undefined') deselect.classList.add('!hidden');
        else deselect.classList.remove('!hidden');
      }
    }
  }

  initSelect() {
    this.options.unshift({value: "", text: null, display: false, html: "<span class='opacity-70 font-normal'>" + this.placeholder + "</span>"}) // Need an empty option for deselect

    const options = {
      select: '#' + this.id,
      data: JSON.parse(JSON.stringify(this.options)), // NOTE: deep clone of options; needed to reset initial value
      settings: {
        showSearch: this.search,
        allowDeselect: !this.required,
        searchPlaceholder: 'Search...',
        hideSelected: this.hideSelectedOption,
        closeOnSelect: this.closeOnSelect,
        maxSelected: this.limit,
      },
      events: {
        searchFilter: (option, search) => option.text.toFlat().includes(search.toFlat()),
        afterChange: () => this.valueChange.emit(this.Value)
      }
    }

    // Set initial value
    if (this.value !== undefined) {
      const values = Array.isArray(this.value) ? this.value : [this.value];
      for (let value of values) {
        options.data = options.data.map(option => {
          if (option.value === value) option['selected'] = true;
          return option;
        });
      }
    }

    this.select = new SlimSelect(options);
  }

  get Value(): any {
    if (this.multiple) {
      if (Array.isArray(this.value)) return this.value;

    } else {
      if (Array.isArray(this.value)) {
        if (this.value.length === 1) return this.value[0];

      } else return this.value;
    }
  }

  get InputSize(): typeof InputSize {
    return InputSize;
  }

  get InputGroupSize(): typeof InputGroupSize {
    return InputGroupSize;
  }

  get InputSelectColor(): typeof InputSelectColor {
    return InputSelectColor;
  }

  get InputGroupLabelColor(): typeof InputGroupLabelColor {
    return InputGroupLabelColor;
  }

  get InputGroupBtnColor(): typeof InputGroupBtnColor {
    return InputGroupBtnColor;
  }

  protected readonly onchange = onchange;
}
