import {Component, Input, OnInit} from '@angular/core';
import { ViewHeader } from 'src/app/_domain/views/view-header';
import {ViewBlock} from "../../../_domain/views/view-block";
import {requireValues} from "../../../_utils/misc/misc";

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html',
  styleUrls: ['./block.component.scss']
})
export class BlockComponent implements OnInit {

  @Input() view: ViewBlock;
  @Input() edit: boolean;

  readonly BLOCK_CLASS = 'block';

  constructor() { }

  ngOnInit(): void {
    requireValues([this.view.children]);

    this.view.class += ' ' + this.BLOCK_CLASS;
  }

  get ViewHeader(): typeof ViewHeader {
    return ViewHeader;
  }

}
