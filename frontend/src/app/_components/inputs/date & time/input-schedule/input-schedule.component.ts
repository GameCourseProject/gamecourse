import {Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm} from "@angular/forms";

import {ModalService} from "../../../../_services/modal.service";
import {AlertService, AlertType} from "../../../../_services/alert.service";

import cronstrue from 'cronstrue';

@Component({
  selector: 'app-input-schedule',
  templateUrl: './input-schedule.component.html'
})
export class InputScheduleComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                    // Unique ID
  @Input() form: NgForm;                                                  // Form it's part of
  @Input() value: string;                                                 // Where to store the value
  @Input() filterOptions?: string[];                                      // Options to be filtered out

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                       // Size FIXME: not working
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |         // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                            // Classes to add
  @Input() disabled?: boolean;                                            // Make it disabled

  @Input() topLabel?: string;                                             // Top label text
  @Input() leftLabel?: string;                                            // Text on prepended label

  @Input() helperText?: string;                                           // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';          // Helper position


  // Validity
  @Input() required?: boolean;                                            // Make it required

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                    // Message for required error

  @Output() valueChange = new EventEmitter<string>();

  @ViewChild('fMinutes', { static: false }) fMinutes: NgForm;
  @ViewChild('fHourly', { static: false }) fHourly: NgForm;
  @ViewChild('fDaily', { static: false }) fDaily: NgForm;
  @ViewChild('fWeekly', { static: false }) fWeekly: NgForm;
  @ViewChild('fMonthly', { static: false }) fMonthly: NgForm;
  @ViewChild('fExpression', { static: false }) fExpression: NgForm;

  tabs: string[] = ["Minutes", "Hourly", "Daily", "Weekly", "Monthly", "Advanced"];
  tabActive: string;

  minuteOptions: {value: string, text: string}[] = this.initNumberOptions(1, 59);
  hourOptions: {value: string, text: string}[] = this.initNumberOptions(0, 23);
  dayOptions: {value: string, text: string}[] = this.initNumberOptions(1, 31);
  monthOptions: {value: string, text: string}[] = this.initNumberOptions(1, 12);
  orderOptions: {value: string, text: string}[] = this.initOrderOptions();
  weekdayOptions: {value: string, text: string}[] = this.initWeekdayOptions();

  minutes: string;
  hours: string;
  daily: DailyData;
  weekly: WeeklyData;
  monthly: MonthlyData;
  expression: string;

  readable: string

  constructor() { }

  ngOnInit(): void {
    // Filter options
    if (this.filterOptions?.length > 0)
      this.tabs = this.tabs.filter(tab => !this.filterOptions.includes(tab))

    // Set active tab
    this.tabActive = this.tabs[0];

    // Init values
    this.init();
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  initNumberOptions(min: number, max:number) {
    const options: {value: string, text: string}[] = [];
    for (let i = min; i <= max; i++) { options.push({value: 'i-' + i, text: i.toString()}); }
    return options;
  }

  initOrderOptions() {
    const options: {value: string, text: string}[] = [];
    for (const order of ['first', 'second', 'third', 'fourth', 'fifth', 'last']) { options.push({value: order, text: order.capitalize()}); }
    return options;
  }

  initWeekdayOptions() {
    return [
      {value: 'd-1', text: 'Monday'},
      {value: 'd-2', text: 'Tuesday'},
      {value: 'd-3', text: 'Wednesday'},
      {value: 'd-4', text: 'Thursday'},
      {value: 'd-5', text: 'Friday'},
      {value: 'd-6', text: 'Saturday'},
      {value: 'd-0', text: 'Sunday'},
    ];
  }


  initDailyData(data?: DailyData) {
    this.daily = data ?? {
      case: 1,
      days: 'i-1',
      time1: '00:00',
      time2: '00:00',
    };
  }

  initWeeklyData(data?: WeeklyData) {
    this.weekly = data ?? {
      days: [],
      time: '00:00'
    };
  }

  initMonthlyData(data?: MonthlyData) {
    this.monthly = data ?? {
      case: 1,
      days: 'i-1',
      months1: 'i-1',
      months2: 'i-1',
      order: 'first',
      weekday: 'd-1',
      time1: '00:00',
      time2: '00:00'
    };
  }


  init() {
    this.minutes = 'i-1';
    this.hours = 'i-1';
    this.initDailyData();
    this.initWeeklyData();
    this.initMonthlyData();
    if (this.value) this.expressionToData(this.value);
  }


  /*** --------------------------------------------- ***/
  /*** ----------------- Actions ------------------- ***/
  /*** --------------------------------------------- ***/

  onSubmit() {
    if ((this.tabActive === 'Minutes' && this.fMinutes.valid) || (this.tabActive === 'Hourly' && this.fHourly.valid) ||
      (this.tabActive === 'Daily' && this.fDaily.valid) || (this.tabActive === 'Weekly' && this.fWeekly.valid) ||
      (this.tabActive === 'Monthly' && this.fMonthly.valid) || (this.tabActive === 'Advanced' && this.fExpression.valid)) {

      const expression = this.dataToExpression();
      this.valueChange.emit(expression);
      this.readable = cronExpressionToText(expression);

      if (this.tabActive !== 'Daily') this.initDailyData();
      if (this.tabActive !== 'Weekly') this.initWeeklyData();
      if (this.tabActive !== 'Monthly') this.initMonthlyData();
      if (this.tabActive !== 'Advanced') this.expression = null;
      this.closeEditor();

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  /**
   * Transforms user input data into a Cron expression.
   */
  dataToExpression(): string {
    if (this.tabActive === 'Minutes') {
      const minutes = this.minutes.substring(2);
      return createCronExpression('*/' + minutes, '*', '*', '*', '*');

    } else if (this.tabActive === 'Hourly') {
      const hours = this.hours.substring(2);
      return createCronExpression('0', '*/' + hours, '*', '*', '*');

    } else if (this.tabActive === 'Daily') {
      if (this.daily.case === 1) {
        const days = this.daily.days.substring(2);
        const hours = this.daily.time1.split(':')[0];
        const minutes = this.daily.time1.split(':')[1];
        return createCronExpression(minutes, hours, '*/' + days, '*', '*');

      } else {
        const hours = this.daily.time2.split(':')[0];
        const minutes = this.daily.time2.split(':')[1];
        return createCronExpression(minutes, hours, '*', '*', 'MON-FRI');
      }

    } else if (this.tabActive === 'Weekly') {
      const days = this.weekly.days.map(day => dayToText(parseInt(day.substring(2)))).join(',');
      const hours = this.weekly.time.split(':')[0];
      const minutes = this.weekly.time.split(':')[1];
      return createCronExpression(minutes, hours, '*', '*', days);

    } else if (this.tabActive === 'Monthly') {
      if (this.monthly.case === 1) {
        const days = this.monthly.days.substring(2);
        const months = this.monthly.months1.substring(2);
        const hours = this.monthly.time1.split(':')[0];
        const minutes = this.monthly.time1.split(':')[1];
        return createCronExpression(minutes, hours, days, '1/' + months, '*');

      } else {
        const order = this.monthly.order;
        const weekday = this.monthly.weekday.substring(2);
        const months = this.monthly.months1.substring(2);
        const hours = this.monthly.time1.split(':')[0];
        const minutes = this.monthly.time1.split(':')[1];
        return createCronExpression(minutes, hours, '*', '1/' + months, dayToText(parseInt(weekday)) + '#' + orderToNumber(order));
      }

    } else return this.expression;

    function createCronExpression(minute: string, hour: string, day: string, month: string, weekday: string): string {
      return [minute, hour, day, month, weekday].join(' ');
    }

    function dayToText(day: number): string {
      if (day === 1) return 'MON';
      else if (day === 2) return 'TUE';
      else if (day === 3) return 'WED';
      else if (day === 4) return 'THU';
      else if (day === 5) return 'FRI';
      else if (day === 6) return 'SAT';
      else if (day === 0) return 'SUN';
      else return '';
    }

    function orderToNumber(order: string): string {
      if (order === 'first') return '1';
      else if (order === 'second') return '2';
      else if (order === 'third') return '3';
      else if (order === 'fourth') return '4';
      else if (order === 'fifth') return '5';
      else if (order === 'last') return 'L';
      else return '';
    }
  }

  /**
   * Transforms a given expression into appropriate data.
   */
  expressionToData(expression: string) {
    const MINUTES_REGEX = /^\*\/(\d+) \* \* \* \*$/g;
    const HOURLY_REGEX = /^0 \*\/(\d+) \* \* \*$/g;
    const DAILY_1_REGEX = /^(\d+) (\d+) \*\/(\d+) \* \*$/g;
    const DAILY_2_REGEX = /^(\d+) (\d+) \* \* MON-FRI$/g;
    const WEEKLY_REGEX = /^(\d+) (\d+) \* \* ((?:(?:MON|TUE|WED|THU|FRI|SAT|SUN),*)+)$/g;
    const MONTHLY_1_REGEX = /^(\d+) (\d+) (\d+) (\d+)\/(\d+) \*$/g;
    const MONTHLY_2_REGEX = /^(\d+) (\d+) \* (\d+)\/(\d+) (MON|TUE|WED|THU|FRI|SAT|SUN)#(\d|L)$/g;

    if (expression.match(MINUTES_REGEX)) {
      const matches = expression.matchAll(MINUTES_REGEX);
      for (const match of matches) {
        this.minutes = 'i-' + match[1];
        this.tabActive = 'Minutes';
      }

    } else if (expression.match(HOURLY_REGEX)) {
      const matches = expression.matchAll(HOURLY_REGEX);
      for (const match of matches) {
        this.hours = 'i-' + match[1];
        this.tabActive = 'Hourly';
      }

    } else if (expression.match(DAILY_1_REGEX)) {
      const matches = expression.matchAll(DAILY_1_REGEX);
      for (const match of matches) {
        this.daily.case = 1;
        this.daily.days = 'i-' + match[3];
        this.daily.time1 = match[2] + ':' + match[1];
        this.tabActive = 'Daily';
      }

    } else if (expression.match(DAILY_2_REGEX)) {
      const matches = expression.matchAll(DAILY_2_REGEX);
      for (const match of matches) {
        this.daily.case = 2;
        this.daily.time2 = match[2] + ':' + match[1];
        this.tabActive = 'Daily';
      }

    } else if (expression.match(WEEKLY_REGEX)) {
      const matches = expression.matchAll(WEEKLY_REGEX);
      for (const match of matches) {
        this.weekly.days = match[3].split(',').map(text => textToDay(text));
        this.weekly.time = match[2] + ':' + match[1];
        this.tabActive = 'Weekly';
      }

    } else if (expression.match(MONTHLY_1_REGEX)) {
      const matches = expression.matchAll(MONTHLY_1_REGEX);
      for (const match of matches) {
        this.monthly.case = 1;
        this.monthly.days = 'i-' + match[3];
        this.monthly.months1 = 'i-' + match[5];
        this.monthly.time1 = match[2] + ':' + match[1];
        this.tabActive = 'Monthly';
      }

    } else if (expression.match(MONTHLY_2_REGEX)) {
      const matches = expression.matchAll(MONTHLY_2_REGEX);
      for (const match of matches) {
        this.monthly.case = 2;
        this.monthly.order = textToOrder(match[6])
        this.monthly.weekday = textToDay(match[5])
        this.monthly.months2 = 'i-' + match[4];
        this.monthly.time2 = match[2] + ':' + match[1];
        this.tabActive = 'Monthly';
      }

    } else {
      this.expression = expression;
      this.tabActive = 'Advanced';
    }

    this.readable = cronExpressionToText(expression);

    function textToDay(text: string): string {
      if (text === 'MON') return 'd-1';
      else if (text === 'TUE') return 'd-2';
      else if (text === 'WED') return 'd-3';
      else if (text === 'THU') return 'd-4';
      else if (text === 'FRI') return 'd-5';
      else if (text === 'SAT') return 'd-6';
      else if (text === 'SUN') return 'd-0';
      else return null;
    }

    function textToOrder(text: string): string {
      if (text === '1') return 'first';
      else if (text === '2') return 'second';
      else if (text === '3') return 'third';
      else if (text === '4') return 'fourth';
      else if (text === '5') return 'fifth';
      else if (text === 'L') return 'last';
      else return '';
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  openEditor() {
    this.init();
    ModalService.openModal('cron-editor-' + this.id);
  }

  closeEditor() {
    ModalService.closeModal('cron-editor-' + this.id);
  }

}

/**
 * Returns a readable description from a Cron expression.
 */
export function cronExpressionToText(expression: string): string {
  const options = {verbose: false, use24HourTimeFormat: true};
  return cronstrue.toString(expression, options);
}

interface DailyData {
  case: number,
  days?: string,
  time1?: string,
  time2?: string
}

interface WeeklyData {
  days: string[]
  time: string
}

interface MonthlyData {
  case: number,
  days?: string,
  months1?: string,
  months2?: string,
  order?: string,
  weekday?: string,
  time1?: string
  time2?: string
}
