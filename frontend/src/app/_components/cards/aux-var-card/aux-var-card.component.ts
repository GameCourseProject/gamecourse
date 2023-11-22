import { Component, Input, OnChanges, OnInit, ViewChild } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Output, EventEmitter } from '@angular/core';
import * as _ from "lodash";
import { Variable } from 'src/app/_domain/views/variables/variable';

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
  
  deleteAction() {
    this.deleteEvent.emit();
  }
  
  addNewAction() {
    this.createEvent.emit({
      name: this.newName,
      value: this.newValue,
    });
  }

  editAction() {
    this.edit = true;
  }
  
  saveAction() {
    this.edit = false;
    this.updateEvent.emit({
      name: this.newName,
      value: this.newValue,
    })
  }
  
  cancelAction() {
    this.edit = false;
    this.ngOnChanges();
  }
  
}
