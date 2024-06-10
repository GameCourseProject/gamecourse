import { Component, Input, OnChanges, OnInit, ViewChild } from '@angular/core';
import { Output, EventEmitter } from '@angular/core';
import * as _ from "lodash";
import { Variable } from 'src/app/_domain/views/variables/variable';
import { AlertService, AlertType } from 'src/app/_services/alert.service';

@Component({
  selector: 'app-aux-var-card',
  templateUrl: './aux-var-card.component.html'
})
export class AuxVarCardComponent implements OnChanges {

  @Input() variable: Variable = null;
  @Input() new: boolean = false;
  @Output() deleteEvent = new EventEmitter<string>();
  @Output() createEvent = new EventEmitter<{ name: string; value: string; }>();
  @Output() updateEvent = new EventEmitter<{ name: string; value: string; }>();

  edit?: boolean = false;

  newName: string;
  newValue: string;

  constructor(
  ) { }

  ngOnChanges(): void {
    this.newName = this.variable?.name ?? "";
    this.newValue = this.variable?.value ?? "";
  }

  isFilled() {
    if (this.newName != '' || this.newValue != '') return true;
    else return false;
  }

  deleteAction() {
    this.deleteEvent.emit();
  }

  addNewAction() {
    if (this.newName != "" && this.newValue != "") {
      this.createEvent.emit({
        name: this.newName,
        value: this.newValue,
      });
      this.newName = this.variable?.name ?? "";
      this.newValue = this.variable?.value ?? "";
    }
    else AlertService.showAlert(AlertType.ERROR, "Auxiliary Variable must have Name and Expression");
  }

  editAction() {
    this.edit = true;
  }

  saveAction() {
    if (this.newName != "" && this.newValue != "") {
      this.edit = false;
      this.updateEvent.emit({
        name: this.newName,
        value: this.newValue,
      })
    }
    else AlertService.showAlert(AlertType.ERROR, "Auxiliary Variable must have Name and Expression");
  }

  cancelAction() {
    this.edit = false;
    this.ngOnChanges();
  }

}
