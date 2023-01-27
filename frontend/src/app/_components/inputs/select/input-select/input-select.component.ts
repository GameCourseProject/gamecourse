import {Component, EventEmitter, Input, OnInit, Output, SimpleChanges, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";
import {Observable} from "rxjs";

import { InputGroupBtnColor, InputGroupLabelColor, InputSelectColor } from '../../InputColors';
import { InputGroupSize, InputSize } from '../../InputSizes';

import SlimSelect from "slim-select";

@Component({
  selector: 'app-input-select',
  templateUrl: './input-select.component.html'
})
export class InputSelectComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                              // Unique ID
  @Input() form: NgForm;                                                            // Form it's part of
  @Input() value: any;                                                              // Where to store the value
  @Input() options?: ({value: string, text: string, innerHTML?: string} |           // Options to select from
                      {label: string, options: {value: string, text: string, innerHTML?: string}[]}
                     )[];
  @Input() placeholder: string;                                                     // Message to show by default

  @Input() multiple?: boolean;                                                      // Whether to allow multiple selects
  @Input() limit?: number;                                                          // Multiple selection limit
  @Input() search?: boolean = true;                                                 // Allow to search options
  @Input() closeOnSelect?: boolean = true;                                          // Whether to close upon selecting a value
  @Input() hideSelectedOption?: boolean = false;                                    // Hide selected option

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                                 // Size  FIXME: not working
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |                   // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                                      // Classes to add
  @Input() disabled?: boolean;                                                      // Make it disabled

  @Input() topLabel?: string;                                                       // Top label text
  @Input() leftLabel?: string;                                                      // Text on prepended label
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
  @Input() setData?: Observable<{value: any, text: string, innerHTML?: string,      // Set data on demand
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
      if (this.setData) this.setData.subscribe(data => this.select.setData(data));
    }, 0);
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
    const options = {
      select: '#' + this.id,
      addToBody: true,
      allowDeselect: !this.required,
      searchPlaceholder: 'Search...',
      showSearch: this.search,
      hideSelectedOption: this.hideSelectedOption,
      closeOnSelect: this.closeOnSelect,
      data: JSON.parse(JSON.stringify(this.options)), // NOTE: deep clone of options; needed to reset initial value
      searchFilter: (option, search) => option.text.toFlat().includes(search.toFlat()),
      onChange: info => { // Clear search on item selected
        // Find select ID
        const cls = this.select.slim.container.classList.toString();
        const matches = cls.match(/ss-(\d+)/g);
        const selectID = matches[0].substring(3);

        // Clear search input
        const searchInput = $('.ss-' + selectID + ' input[type=search]');
        searchInput.val(null);
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

    if (this.placeholder) (options.data as any).unshift({placeholder: true, text: this.placeholder});
    if (this.limit) options['limit'] = this.limit;
    if (!this.multiple && !this.required) options['deselectLabel'] = '<span>❌</span>';

    this.select = new SlimSelect(options);

    // Set deselect
    if (!this.value || (Array.isArray(this.value) && this.value.length == 0)) {
      const deselect = $('.ss-deselect')[0]
      if (deselect) deselect.classList.add('!hidden');
    }
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

}
