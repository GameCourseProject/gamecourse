import {Component, Input, OnInit} from '@angular/core';
import {View, ViewMode, VisibilityType} from "../../../_domain/views/view";
import {ViewType} from "../../../_domain/views/view-type";
import { ViewText } from 'src/app/_domain/views/view-text';
import { ViewImage } from 'src/app/_domain/views/view-image';
import { ViewHeader } from 'src/app/_domain/views/view-header';
import { ViewTable } from 'src/app/_domain/views/view-table';
import { ViewBlock } from 'src/app/_domain/views/view-block';
import { ViewRow } from 'src/app/_domain/views/view-row';
import {ViewChart} from "../../../_domain/views/view-chart";

@Component({
  selector: 'bb-any',
  templateUrl: './any.component.html'
})
export class BBAnyComponent implements OnInit {

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

  get ViewChart(): typeof ViewChart {
    return ViewChart;
  }

}
