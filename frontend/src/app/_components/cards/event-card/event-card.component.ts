import {ChangeDetectorRef, Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {Event} from 'src/app/_domain/views/events/event';
import {EventType} from 'src/app/_domain/views/events/event-type';
import {AlertService, AlertType} from 'src/app/_services/alert.service';
import {EventAction, EventActionHelper} from "../../../_domain/views/events/event-action";

@Component({
  selector: 'app-event-card',
  templateUrl: './event-card.component.html'
})
export class EventCardComponent implements OnInit {

  @Input() event: Event = null;
  @Input() new: boolean = false;
  @Output() deleteEvent = new EventEmitter<string>();
  @Output() createEvent = new EventEmitter<{ type: EventType, expression: string }>();
  @Output() updateEvent = new EventEmitter<{ type: EventType, expression: string }>();

  edit?: boolean = false;
  refresh?: boolean = false;
  eventToAdd: { type: EventType, action: EventAction, args: string[] } = { type: null, action: null, args: [] };

  constructor(
    private cdr: ChangeDetectorRef
  ) { }

  ngOnInit(): void {
    if (this.event) {
      this.eventToAdd.type = this.event.type;
      this.eventToAdd.action = this.event.action;
      this.eventToAdd.args = this.extractArguments(this.event.expression);
    }
  }

  isFilled() {
    if (this.eventToAdd.type || this.eventToAdd.action) return true;
    else return false;
  }

  deleteAction() {
    this.deleteEvent.emit();
  }

  addNewAction() {
    AlertService.clear(AlertType.ERROR);

    if (this.eventToAdd.type != null && this.eventToAdd.action != null) {
      this.refresh = true;

      const preparedArguments = this.prepareArgumentsForAction();

      if (preparedArguments != null) {
        this.createEvent.emit({
          type: this.eventToAdd.type,
          expression: "{" + EventActionHelper[this.eventToAdd.action].name + "(" + preparedArguments.join(",") + ")}",
        });
        this.event = null;
        this.eventToAdd = { type: null, action: null, args: [] };

        this.cdr.detectChanges();
      }

      this.refresh = false;
    }
    else AlertService.showAlert(AlertType.ERROR, "Event must have When and Do");
  }

  editAction() {
    this.edit = true;
  }

  saveAction() {
    AlertService.clear(AlertType.ERROR);

    if (this.eventToAdd.type != null && this.eventToAdd.action != null) {
      const preparedArguments = this.prepareArgumentsForAction();

      if (preparedArguments != null) {
        this.edit = false;

        this.updateEvent.emit({
          type: this.eventToAdd.type,
          expression: "{" + EventActionHelper[this.eventToAdd.action].name + "(" + preparedArguments.join(",") + ")}",
        });
      }
    }
    else AlertService.showAlert(AlertType.ERROR, "Event must have When and Do");
  }

  cancelAction() {
    this.edit = false;
    this.ngOnInit();
  }

  getEventTypes() {
    return Object.values(EventType).map((value) => { return ({ value: value, text: value.capitalize() }) })
  }

  getActionTypes() {
    return Object.values(EventAction).map((action) => ({
      value: action,
      text: EventActionHelper[action].name
    }));
  }


  /*-------------------------------------*/
  /*-------------- Helpers --------------*/
  /*-------------------------------------*/

  protected readonly EventActionHelper = EventActionHelper;

  prepareArgumentsForAction(): string[] | null {
    const argsToSend = [];

    let index = 0;

    for (let value of EventActionHelper[this.eventToAdd.action].args) {
      if (this.eventToAdd.args[index]) {
        if (EventActionHelper[this.eventToAdd.action].types?.[index] == "string") {
          if (!/^(['"]).*\1$/.test(this.eventToAdd.args[index])) {
            this.eventToAdd.args[index] = "\"" + this.eventToAdd.args[index] + "\""
          }
        }
        argsToSend.push(this.eventToAdd.args[index]);
      }
      else if (!value.containsWord("(optional)")) {
        AlertService.showAlert(AlertType.ERROR, "Missing mandatory arguments.");
        return null;
      }
      index++;
    }
    return argsToSend;
  }

  extractArguments(str: string): string[] {
    const start = str.indexOf('(');
    const end = str.lastIndexOf(')');
    if (start === -1 || end === -1 || start >= end) return [];

    const argsStr = str.substring(start + 1, end).trim();

    // Split arguments based on commas, ignoring those within parentheses
    const args = [];
    let arg = '';
    let level = 0;

    for (let i = 0; i < argsStr.length; i++) {
      const char = argsStr[i];
      if (char === '(') {
        level++;
      } else if (char === ')') {
        level--;
      }

      if (char === ',' && level === 0) {
        args.push(arg.trim());
        arg = '';
      } else {
        arg += char;
      }
    }

    if (arg.trim() !== '') {
      args.push(arg.trim());
    }

    return args;
  };
}
