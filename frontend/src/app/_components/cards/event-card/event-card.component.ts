import { Component, Input, OnInit } from '@angular/core';
import { Output, EventEmitter } from '@angular/core';
import { Event } from 'src/app/_domain/views/events/event';
import { EventType } from 'src/app/_domain/views/events/event-type';
import { AlertService, AlertType } from 'src/app/_services/alert.service';

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

  eventToAdd: { type: EventType, expression: string };

  constructor(
  ) { }

  ngOnInit(): void {
    if (this.event) {
      this.eventToAdd = { type: this.event.type, expression: this.event.expression };
    }
    else {
      this.eventToAdd = { type: null, expression: "" };
    }
  }

  getEventTypes() {
    return Object.values(EventType).map((value) => { return ({ value: value, text: value.capitalize() }) })
  }

  deleteAction() {
    this.deleteEvent.emit();
  }

  addNewAction() {
    if (this.eventToAdd.type != null && this.eventToAdd.expression != "") {
      this.createEvent.emit({
        type: this.eventToAdd.type,
        expression: this.eventToAdd.expression,
      });
      this.eventToAdd.type = null;
      this.eventToAdd.expression = "";
    }
    else AlertService.showAlert(AlertType.ERROR, "Event must have When and Do");
  }

  editAction() {
    this.edit = true;
  }

  saveAction() {
    if (this.eventToAdd.type != null && this.eventToAdd.expression != "") {
      this.edit = false;
      this.updateEvent.emit({
        type: this.eventToAdd.type,
        expression: this.eventToAdd.expression,
      });
    }
    else AlertService.showAlert(AlertType.ERROR, "Event must have When and Do");
  }

  cancelAction() {
    this.edit = false;
    this.ngOnInit();
  }

}
