import {Component, Input, OnInit} from '@angular/core';
import {ViewBlock} from "../../../_domain/views/view-block";
import {requireValues} from "../../../_utils/misc/misc";
import {ViewType} from "../../../_domain/views/view-type";
import { ViewText } from 'src/app/_domain/views/view-text';
import { ViewImage } from 'src/app/_domain/views/view-image';
import { ViewHeader } from 'src/app/_domain/views/view-header';
import { ViewTable } from 'src/app/_domain/views/view-table';

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html',
  styleUrls: ['./block.component.scss']
})
export class BlockComponent implements OnInit {

  @Input() view: ViewBlock;
  @Input() edit: boolean;

  constructor() { }

  ngOnInit(): void {
    requireValues([this.view.children]);
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
