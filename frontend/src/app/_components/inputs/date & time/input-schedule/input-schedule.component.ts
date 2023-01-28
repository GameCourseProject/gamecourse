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

  @ViewChild('fDaily', { static: false }) fDaily: NgForm;
  @ViewChild('fWeekly', { static: false }) fWeekly: NgForm;
  @ViewChild('fMonthly', { static: false }) fMonthly: NgForm;
  @ViewChild('fExpression', { static: false }) fExpression: NgForm;

  tabs: string[]= ["Daily", "Weekly", "Monthly", "Advanced"];
  tabActive: string = this.tabs[0];

  dayOptions: {value: string, text: string}[] = this.initDayOptions(1, 31);
  monthOptions: {value: string, text: string}[] = this.initMonthOptions(1, 12);
  orderOptions: {value: string, text: string}[] = this.initOrderOptions();
  weekdayOptions: {value: string, text: string}[] = this.initWeekdayOptions();

  daily: DailyData;
  weekly: WeeklyData;
  monthly: MonthlyData;
  expression: string;

  humanReadable: string;

  constructor() { }

  ngOnInit(): void {
    // Init values
    this.initDailyData();
    this.initWeeklyData();
    this.initMonthlyData();

    // TODO: set value if not undefined
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  initDayOptions(min: number, max: number) {
    const options: {value: string, text: string}[] = [];
    for (let i = min; i <= max; i++) { options.push({value: 'd-' + i, text: i.toString()}); }
    return options;
  }

  initMonthOptions(min: number, max: number) {
    const options: {value: string, text: string}[] = [];
    for (let i = min; i <= max; i++) { options.push({value: 'm-' + i, text: i.toString()}); }
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
      days: 'd-1',
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
      days: 'd-1',
      months1: 'm-1',
      months2: 'm-1',
      order: 'first',
      weekday: 'd-1',
      time1: '00:00',
      time2: '00:00'
    };
  }


  /*** --------------------------------------------- ***/
  /*** ----------------- Actions ------------------- ***/
  /*** --------------------------------------------- ***/

  onSubmit() {
    if ((this.tabActive === 'Daily' && this.fDaily.valid) || (this.tabActive === 'Weekly' && this.fWeekly.valid) ||
      (this.tabActive === 'Monthly' && this.fMonthly.valid) || (this.tabActive === 'Advanced' && this.fExpression.valid)) {
      const expression = this.dataToExpression();
      this.valueChange.emit(expression);
      this.humanReadable = this.expressionToText(expression);

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
    if (this.tabActive === 'Daily') {
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
   * Returns a readable description from a Cron expression.
   */
  expressionToText(expression: string): string {
    const options = {verbose: true, use24HourTimeFormat: true};
    return cronstrue.toString(expression, options);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  openEditor() {
    ModalService.openModal('cron-editor');
  }

  closeEditor() {
    ModalService.closeModal('cron-editor');
  }

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
