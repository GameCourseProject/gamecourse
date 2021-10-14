import {Component, Input, OnInit} from '@angular/core';
import { ViewHeader } from 'src/app/_domain/views/view-header';
import {ViewBlock} from "../../../_domain/views/view-block";
import {requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html'
})
export class BlockComponent implements OnInit {

  @Input() view: ViewBlock;
  edit: boolean;
  isEditingLayout: boolean;

  readonly BLOCK_CLASS = 'block';
  readonly BLOCK_CHILDREN_CLASS = 'block_children';
  readonly BLOCK_EMPTY_CLASS = 'block_empty';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.children]);
    this.view.class += ' ' + this.BLOCK_CLASS;
    this.edit = this.view.mode === ViewMode.EDIT;
  }

  get ViewHeader(): typeof ViewHeader {
    return ViewHeader;
  }

}
