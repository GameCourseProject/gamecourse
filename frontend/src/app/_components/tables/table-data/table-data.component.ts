import {
  Component,
  ComponentRef,
  EventEmitter,
  Input,
  OnInit,
  Output,
  Type,
  ViewChild, ViewContainerRef
} from '@angular/core';
import {DomSanitizer} from "@angular/platform-browser";
import {environment} from "../../../../environments/environment.prod";
import {Moment} from "moment/moment";

import {ErrorService} from "../../../_services/error.service";
import {ThemingService} from "../../../_services/theming/theming.service";

import {Action} from "../../../_domain/modules/config/Action";
import {Theme} from "../../../_services/theming/themes-available";
import {ResourceManager} from "../../../_utils/resources/resource-manager";
import {TableDataCustomDirective} from "./table-data-custom.directive";


@Component({
  selector: '[table-data]',
  templateUrl: './table-data.component.html'
})
export class TableData implements OnInit {

  @Input() type: TableDataType;                                 // Type of table data to render
  @Input() data: any;                                           // Data to render
  @Input() align?: 'left' | 'middle' | 'right' = 'middle';      // Alignment in cell

  // General
  classList?: string;                                           // Classes to add

  // Type: TEXT
  text?: string;                                                // Text (either 1 line or 1st line)
  subtitle?: string;                                            // Subtitle (2nd line)

  // Type: NUMBER
  value?: number;                                               // Number
  valueFormat?:
    'none' | 'default' | 'money' | 'percent' = 'default';       // Number format

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
  img: ResourceManager;                                         // Triggers image update
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
  buttonStyle?: 'default' | 'outline' = 'default';              // Button style
  buttonIcon?: string;                                          // Button icon
  buttonDisable?: boolean;                              // Make it disabled

  // Type: AVATAR
  avatar: ResourceManager;                                      // Triggers avatar update
  avatarTitle?: string;                                         // Avatar name (either 1 line or 1st line)
  avatarSubtitle?: string;                                      // Avatar additional text (2nd line)
  avatarSrc?: string;                                           // Avatar image source
  avatarIcon?: string;                                          // Avatar icon
  avatarIconColor?: 'primary' | 'secondary' | 'accent'
    | 'neutral' | 'info' | 'success' | 'warning' | 'error';     // Avatar icon color

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
  actions?: (Action | {action: Action | string, icon?: string,  // Actions
    color?: 'ghost' | 'primary' | 'secondary' | 'accent' |
      'neutral' | 'info' | 'success' | 'warning' | 'error',
    disabled?: boolean})[];

  // Type: SELECT
  selectId?: string;                                                                    // Select id
  selectValue?: any;                                                                   // Select value
  selectOptions?: ({value: string, text: string, innerHTML?: string} |                 // Select options
    {label: string, options: {value: string, text: string, innerHTML?: string}[]})[];
  selectMultiple?: boolean;                                                            // Select allow multiple
  selectRequire?: boolean;                                                             // Select require answer
  selectPlaceholder?: string;                                                          // Select placeholder
  selectSearch?: boolean;                                                              // Select allow search

  // Type: CUSTOM
  html?: string;                                                // Custom HTML
  component?: Type<any>;                                        // Custom component
  componentData?: {[key: string]: any};                         // Custom component data
  searchBy?: string;                                            // Search term
  @ViewChild(TableDataCustomDirective, {static: true}) componentContainer!: TableDataCustomDirective;

  @Output() btnClicked: EventEmitter<Action | 'single'> = new EventEmitter<Action | 'single'>();
  @Output() valueChanged: EventEmitter<any> = new EventEmitter<any>();

  constructor(
    private themeService: ThemingService,
    private sanitizer: DomSanitizer
  ) { }

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

  textColors = {
    ghost: 'text-base-content text-opacity-75',
    primary: 'text-primary',
    secondary: 'text-secondary',
    accent: 'text-accent',
    neutral: 'text-neutral',
    info: 'text-info',
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-error'
  }

  ngOnInit(): void {
    this.classList = this.data.classList ?? null;

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
      this.img = new ResourceManager(this.sanitizer);
      this.img.set(this.imgSrc);
      if (this.data.imgShape) this.imgShape = this.data.imgShape;

    } else if (this.type === TableDataType.PILL) {
      this.pillText = this.data.pillText;
      this.pillColor = this.data.pillColor ?? null;

    } else if (this.type === TableDataType.BUTTON) {
      this.buttonText = this.data.buttonText;
      this.buttonColor = this.data.buttonColor ?? null;
      this.buttonStyle = this.data.buttonStyle ?? null;
      this.buttonIcon = this.data.buttonIcon ?? null;
      this.buttonDisable = this.data.buttonDisable;

    } else if (this.type === TableDataType.AVATAR) {
      this.avatarTitle = this.data.avatarTitle;
      this.avatarSubtitle = this.data.avatarSubtitle ?? null;
      this.avatarSrc = this.data.avatarSrc ?? null;
      if (this.avatarSrc) {
        this.avatar = new ResourceManager(this.sanitizer);
        this.avatar.set(this.avatarSrc);
      }
      this.avatarIcon = this.data.avatarIcon ?? null;
      this.avatarIconColor = this.data.avatarIconColor ?? null;

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

    } else if (this.type === TableDataType.SELECT){

        this.selectId = this.data.selectId;
        this.selectValue = this.data.selectValue;
        this.selectOptions = this.data.selectOptions;
        this.selectMultiple = this.data.selectMultiple;
        this.selectRequire = this.data.selectRequire;
        this.selectPlaceholder = this.data.selectPlaceholder;
        this.selectSearch = this.data.selectSearch;

    } else if (this.type === TableDataType.CUSTOM) {
      if (this.data.html) this.html = this.data.html;
      if (this.data.component) {
        this.component = this.data.component;

        const viewContainerRef: ViewContainerRef = this.componentContainer.viewContainerRef;
        viewContainerRef.clear();

        const componentRef: ComponentRef<any> = viewContainerRef.createComponent(this.component);
        if (this.data.componentData) {
          this.componentData = this.data.componentData;
          for (const [key, value] of Object.entries(this.componentData)) {
            componentRef.instance[key] = value;
          }
        }
      }
      if (this.data.searchBy) this.searchBy = this.data.searchBy;

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

  get DefaultProfileImg(): string {
    const theme = this.themeService.getTheme();
    return theme === Theme.DARK ? environment.userPicture.dark : environment.userPicture.light;
  }

  isCustomAction(action: any): boolean {
    return action.action && !Object.values(Action).includes(action.action);
  }

}

export enum TableDataType {
  TEXT = 'text',            // params -> text: string, subtitle?: string
  NUMBER = 'number',        // params -> value: number, valueFormat?: default | money | percent
  DATE = 'date',            // params -> date: Moment, dateFormat?: string
  TIME = 'time',            // params -> time: Moment, timeFormat?: string
  DATETIME = 'datetime',    // params -> datetime: Moment, datetimeFormat?: string
  COLOR = 'color',          // params -> color: string, colorLabel?: string
  IMAGE = 'image',          // params -> imgSrc: string, imgShape?: round | square
  PILL = 'pill',            // params -> pillText: string, pillColor?: string
  BUTTON = 'button',        // params -> buttonText: string, buttonColor?: string, buttonStyle?: string, buttonIcon?: string
  AVATAR = 'avatar',        // params -> avatarTitle: string, avatarSubtitle?: string, avatarSrc?: string, avatarIcon?: string, avatarIconColor: string
  CHECKBOX = 'checkbox',    // params -> checkboxId: string, checkboxValue: boolean, checkboxColor?: string, checkboxDisabled?: boolean
  RADIO = 'radio',          // params -> radioId: string, radioGroup: string, radioOptionValue: any, radioValue: any, radioColor?: string, radioDisabled?: boolean
  TOGGLE = 'toggle',        // params -> toggleId: string, toggleValue: boolean, toggleColor?: string, toggleDisabled?: boolean
  ACTIONS = 'actions',      // params -> actions: (Action|{action: Action, icon?: string, color?: string, disabled?: boolean})[]
  SELECT = 'select',        // params -> selectId: string, selectValue?: any, selectOptions?: {value: string, text: string, innerHTML?: string} |
                            // {label: string, options: {value: string, text: string, innerHTML?: string}[]}, selectMultiple?: boolean, selectRequire?: boolean, selectPlaceholder?: string, selectSearch?: boolean
  CUSTOM = 'custom'         // params -> html?: string, component?: Type<any>, componentData?: {[key: string]: any}, searchBy?: string
}

export function getValue(data: {type: TableDataType, content: any}): string {
  if (data.type === TableDataType.TEXT) return data.content['text'] ?? '';
  else if (data.type === TableDataType.NUMBER) return data.content['value']?.toString() ?? '';
  else if (data.type === TableDataType.DATE) return data.content['date']?.format(data.content['dateFormat'] ?? 'DD/MM/YYYY') ?? 'Never';
  else if (data.type === TableDataType.TIME) return data.content['time']?.format(data.content['timeFormat'] ?? 'HH:mm') ?? 'Never';
  else if (data.type === TableDataType.DATETIME) return data.content['datetime']?.format(data.content['datetimeFormat'] ?? 'DD/MM/YYYY HH:mm') ?? 'Never';
  else if (data.type === TableDataType.COLOR) return data.content['colorLabel'] ?? data.content['colorLabel'] ?? '';
  else if (data.type === TableDataType.PILL) return data.content['pillText'] ?? '';
  else if (data.type === TableDataType.AVATAR) return data.content['avatarTitle'] + (' ' + data.content['avatarSubtitle'] ?? '');
  else if (data.type === TableDataType.CHECKBOX) return data.content['checkboxValue'].toString();
  else if (data.type === TableDataType.RADIO) return data.content['radioValue'].toString();
  else if (data.type === TableDataType.TOGGLE) return data.content['toggleValue'].toString();
  else if (data.type === TableDataType.SELECT) return data.content['selectValue'];
  else if (data.type === TableDataType.CUSTOM) return data.content['searchBy'] ?? '';
  return '';
}

export function isFilterable(type: TableDataType, options, index): boolean {
  for (const option of options['columnDefs']) {
    if (option.hasOwnProperty('filterable') && !option['filterable'] && option.hasOwnProperty('targets') && option['targets'].includes(index))
      return false;
  }
  return type !== TableDataType.IMAGE && type !== TableDataType.BUTTON && type !== TableDataType.ACTIONS;
}

export function isSelectable(type: TableDataType): boolean {
  return type === TableDataType.CHECKBOX || type === TableDataType.RADIO || type === TableDataType.TOGGLE || type === TableDataType.SELECT;
}
