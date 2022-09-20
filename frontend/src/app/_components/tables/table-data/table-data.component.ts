import {Component, EventEmitter, Input, OnInit, Output, TemplateRef} from '@angular/core';

import {ErrorService} from "../../../_services/error.service";

import {Action} from "../../../_domain/modules/config/Action";
import {Moment} from "moment/moment";

@Component({
  selector: '[table-data]',
  templateUrl: './table-data.component.html'
})
export class TableData implements OnInit {

  @Input() type: TableDataType;                                 // Type of table data to render
  @Input() data: any;                                           // Data to render

  // General
  classList?: string;                                           // Classes to add
  align?: 'left' | 'middle' | 'right' = 'middle';               // Alignment in cell

  // Type: TEXT
  text?: string;                                                // Text (either 1 line or 1st line)
  subtitle?: string;                                            // Subtitle (2nd line)

  // Type: NUMBER
  value?: number;                                               // Number
  valueFormat?:
    'default' | 'money' | 'percent' = 'default';                // Number format

  // Type: DATE
  date?: Moment;                                                // Date
  dateFormat?: string = 'DD/MM/YYYY';                           // Date format

  // Type: TIME
  time?: Moment;                                                // Time
  timeFormat?: string = 'HH:mm';                                // Time format

  // Type: DATETIME
  datetime?: Moment;                                            // Datetime
  datetimeFormat?: string = 'DD/MM/YYYY HH:mm';                 // Datetime format

  // Type: COLOR
  color?: string;                                               // Color
  colorLabel?: string;                                          // Label for color

  // Type: IMAGE
  imgSrc?: string;                                              // Image source
  imgShape?: 'round' | 'square';                                // Image shape

  // Type: PILL
  pillText?: string;                                            // Pill text
  pillColor?: 'ghost' | 'primary' | 'secondary' | 'accent'
    | 'info' | 'success' | 'warning' | 'error';                 // Pill color

  // Type: BUTTON
  buttonText?: string;                                          // Button text
  buttonColor?: 'ghost' | 'primary' | 'secondary' | 'accent'
    | 'neutral' | 'info' | 'success' | 'warning' | 'error';     // Button color
  buttonIcon?: string;                                          // Button icon

  // Type: AVATAR
  avatarSrc?: string;                                           // Avatar image source
  avatarTitle?: string;                                         // Avatar name (either 1 line or 1st line)
  avatarSubtitle?: string;                                      // Avatar additional text (2nd line)

  // Type: CHECKBOX
  checkboxId?: string;                                          // Checkbox ID
  checkboxValue?: boolean;                                      // Checkbox value
  checkboxColor?: 'primary' | 'secondary' | 'accent';           // Checkbox color
  checkboxDisabled?: boolean;                                   // Make checkbox disabled

  // Type: RADIO
  radioId?: string;                                             // Radio ID
  radioGroup?: string;                                          // Radio group name
  radioOptionValue?: any;                                       // Radio option value
  radioValue?: any;                                             // Radio value
  radioColor?: 'primary' | 'secondary' | 'accent';              // Radio color
  radioDisabled?: boolean;                                      // Make radio disabled

  // Type: TOOGLE
  toggleId?: string;                                            // Toggle ID
  toggleValue?: boolean;                                        // Toggle value
  toggleColor?: 'primary' | 'secondary' | 'accent';             // Toggle color
  toggleDisabled?: boolean;                                     // Make toggle disabled

  // Type: ACTIONS
  actions?: Action[];                                           // Actions

  // Type: CUSTOM
  html?: string;                                                // Custom HTML

  @Output() btnClicked: EventEmitter<Action | 'single'> = new EventEmitter<Action | 'single'>();
  @Output() valueChanged: EventEmitter<any> = new EventEmitter<any>();

  constructor() { }

  pills = {
    ghost: 'badge-ghost',
    primary: 'badge-primary',
    secondary: 'badge-secondary',
    accent: 'badge-accent',
    info: 'badge-info',
    success: 'badge-success',
    warning: 'badge-warning',
    error: 'badge-error'
  }

  buttons = {
    ghost: 'btn-ghost',
    primary: 'btn-primary',
    secondary: 'btn-secondary',
    accent: 'btn-accent',
    neutral: 'btn-neutral',
    info: 'btn-info',
    success: 'btn-success',
    warning: 'btn-warning',
    error: 'btn-error'
  }

  ngOnInit(): void {
    this.classList = this.data.classList ?? null;
    if (this.data.align) this.align = this.data.align;

    if (this.type === TableDataType.TEXT) {
      this.text = this.data.text;
      this.subtitle = this.data.subtitle ?? null;

    } else if (this.type === TableDataType.NUMBER) {
      this.value = this.data.value;
      if (this.data.valueFormat) this.valueFormat = this.data.valueFormat;

    } else if (this.type === TableDataType.DATE) {
      this.date = this.data.date;
      if (this.data.dateFormat) this.dateFormat = this.data.dateFormat;

    } else if (this.type === TableDataType.TIME) {
      this.time = this.data.time;
      if (this.data.timeFormat) this.timeFormat = this.data.timeFormat;

    } else if (this.type === TableDataType.DATETIME) {
      this.datetime = this.data.datetime;
      if (this.data.datetimeFormat) this.datetimeFormat = this.data.datetimeFormat;

    } else if (this.type === TableDataType.COLOR) {
      this.color = this.data.color;
      this.colorLabel = this.data.colorLabel ?? null;

    } else if (this.type === TableDataType.IMAGE) {
      this.imgSrc = this.data.imgSrc;
      if (this.data.imgShape) this.imgShape = this.data.imgShape;

    } else if (this.type === TableDataType.PILL) {
      this.pillText = this.data.pillText;
      this.pillColor = this.data.pillColor ?? null;

    } else if (this.type === TableDataType.BUTTON) {
      this.buttonText = this.data.buttonText;
      this.buttonColor = this.data.buttonColor ?? null;
      this.buttonIcon = this.data.buttonIcon ?? null;

    } else if (this.type === TableDataType.AVATAR) {
      this.avatarSrc = this.data.avatarSrc;
      this.avatarTitle = this.data.avatarTitle;
      this.avatarSubtitle = this.data.avatarSubtitle ?? null;

    } else if (this.type === TableDataType.CHECKBOX) {
      this.checkboxId = this.data.checkboxId;
      this.checkboxValue = this.data.checkboxValue;
      this.checkboxColor = this.data.checkboxColor ?? null;
      this.checkboxDisabled = this.data.checkboxDisabled;

    } else if (this.type === TableDataType.RADIO) {
      this.radioId = this.data.radioId;
      this.radioGroup = this.data.radioGroup;
      this.radioOptionValue = this.data.radioOptionValue;
      this.radioValue = this.data.radioValue;
      this.radioColor = this.data.radioColor ?? null;
      this.radioDisabled = this.data.radioDisabled;

    } else if (this.type === TableDataType.TOGGLE) {
      this.toggleId = this.data.toggleId;
      this.toggleValue = this.data.toggleValue;
      this.toggleColor = this.data.toggleColor ?? null;
      this.toggleDisabled = this.data.toggleDisabled;

    } else if (this.type === TableDataType.ACTIONS) {
      this.actions = this.data.actions;

    } else if (this.type === TableDataType.CUSTOM) {
      this.html = this.data.html;

    } else ErrorService.set('Table data type "' + this.type + '" not found.');
  }

  get DataType(): typeof TableDataType {
    return TableDataType;
  }

  get Action(): typeof Action {
    return Action;
  }

  get Align(): string {
    if (this.align === 'left') return '!text-left !text-start !justify-start';
    if (this.align === 'right') return '!text-right !text-end !justify-end';
    return '!text-center !text-middle !justify-center';
  }

}

export enum TableDataType {
  TEXT,       // params -> text: string, subtitle?: string
  NUMBER,     // params -> value: number, valueFormat?: default | money | percent
  DATE,       // params -> date: Moment, dateFormat?: string
  TIME,       // params -> time: Moment, timeFormat?: string
  DATETIME,   // params -> datetime: Moment, datetimeFormat?: string
  COLOR,      // params -> color: string, colorLabel?: string
  IMAGE,      // params -> imgSrc: string, imgShape?: round | square
  PILL,       // params -> pillText: string, pillColor?: string
  BUTTON,     // params -> buttonText: string, buttonColor?: string, buttonIcon?: string
  AVATAR,     // params -> avatarSrc: string, avatarTitle: string, avatarSubtitle?: string
  CHECKBOX,  // params -> checkboxId: string, checkboxValue: boolean, checkboxColor?: string, checkboxDisabled?: boolean
  RADIO,     // params -> radioId: string, radioGroup: string, radioOptionValue: any, radioValue: any, radioColor?: string, radioDisabled?: boolean
  TOGGLE,    // params -> toggleId: string, toggleValue: boolean, toggleColor?: string, toggleDisabled?: boolean
  ACTIONS,   // params -> actions: Action[]
  CUSTOM     // params -> html: string
}
