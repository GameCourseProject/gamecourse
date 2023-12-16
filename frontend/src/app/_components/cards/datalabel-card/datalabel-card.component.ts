import { Component, Input, OnInit } from '@angular/core';
import { Output, EventEmitter } from '@angular/core';
import * as _ from "lodash";
import { AlertService, AlertType } from 'src/app/_services/alert.service';

@Component({
  selector: 'app-datalabel-card',
  templateUrl: './datalabel-card.component.html'
})
export class DatalabelCardComponent implements OnInit {

  @Input() chartSeries: string[];
  @Input() serie: string = null;
  @Input() format: string = null;
  @Input() new: boolean = false;
  @Output() deleteEvent = new EventEmitter<string>();
  @Output() createEvent = new EventEmitter<{ serie: string, format: string }>();
  @Output() updateEvent = new EventEmitter<{ serie: string, format: string }>();

  edit?: boolean = false;

  datalabelToAdd: { serie: string, format: string };

  constructor(
  ) { }

  ngOnInit(): void {
    if (this.serie) {
      this.datalabelToAdd = { serie: this.serie, format: this.format };
    }
    else {
      this.datalabelToAdd = { serie: null, format: "" };
    }
  }

  getSeries() {
    return (this.chartSeries ?? []).map((value) => { return ({ value: value, text: value.capitalize() }) })
  }

  deleteAction() {
    this.deleteEvent.emit();
  }
  
  addNewAction() {
    if (this.datalabelToAdd.serie != null) {
      this.createEvent.emit({
        serie: this.datalabelToAdd.serie,
        format: this.datalabelToAdd.format,
      });
      this.datalabelToAdd.serie = null;
      this.datalabelToAdd.format = "";
    }
    else AlertService.showAlert(AlertType.ERROR, "Data Label must be associated with a serie");
  }

  editAction() {
    this.edit = true;
  }
  
  saveAction() {
    if (this.datalabelToAdd.serie != null) {
      this.edit = false;
      this.updateEvent.emit({
        serie: this.datalabelToAdd.serie,
        format: this.datalabelToAdd.format,
      });
    }
    else AlertService.showAlert(AlertType.ERROR, "Data Label must be associated with a serie");
  }
  
  cancelAction() {
    this.edit = false;
    this.ngOnInit();
  }
  
}
