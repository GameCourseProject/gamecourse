import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {View, ViewMode, VisibilityType} from "../../../_domain/views/view";
import {ViewType} from "../../../_domain/views/view-type";
import { ViewText } from 'src/app/_domain/views/view-text';
import { ViewImage } from 'src/app/_domain/views/view-image';
import { ViewHeader } from 'src/app/_domain/views/view-header';
import { ViewTable } from 'src/app/_domain/views/view-table';
import { ViewBlock } from 'src/app/_domain/views/view-block';
import { ViewRow } from 'src/app/_domain/views/view-row';

@Component({
  selector: 'bb-any',
  templateUrl: './any.component.html'
})
export class AnyComponent implements OnInit {

  @Input() view: View;

  constructor() { }

  ngOnInit(): void {
  }

  get VisibilityType(): typeof VisibilityType {
    return VisibilityType;
  }

  get ViewMode(): typeof ViewMode {
    return ViewMode;
  }

  get ViewType(): typeof ViewType {
    return ViewType;
  }

  get ViewText(): typeof ViewText {
    return ViewText;
  }

  get ViewImage(): typeof ViewImage {
    return ViewImage;
  }

  get ViewHeader(): typeof ViewHeader {
    return ViewHeader;
  }

  get ViewTable(): typeof ViewTable {
    return ViewTable;
  }

  get ViewBlock(): typeof ViewBlock {
    return ViewBlock;
  }

  get ViewRow(): typeof ViewRow {
    return ViewRow;
  }

}
