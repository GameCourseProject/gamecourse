import {Component, Input, OnInit} from '@angular/core';
import {View} from "../../../_domain/views/view";
import {ViewType} from "../../../_domain/views/view-type";
import { ViewText } from 'src/app/_domain/views/view-text';
import { ViewImage } from 'src/app/_domain/views/view-image';
import { ViewHeader } from 'src/app/_domain/views/view-header';
import { ViewTable } from 'src/app/_domain/views/view-table';
import { ViewBlock } from 'src/app/_domain/views/view-block';

@Component({
  selector: 'bb-any',
  templateUrl: './any.component.html',
  styleUrls: ['./any.component.scss']
})
export class AnyComponent implements OnInit {

  @Input() view: View;
  @Input() edit: boolean;

  constructor() { }

  ngOnInit(): void {
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

}
