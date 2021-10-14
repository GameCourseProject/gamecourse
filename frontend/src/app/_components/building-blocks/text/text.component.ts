import {Component, Input, OnInit} from '@angular/core';
import {ViewText} from "../../../_domain/views/view-text";
import {requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-text',
  templateUrl: './text.component.html'
})
export class TextComponent implements OnInit {

  @Input() view: ViewText;
  edit: boolean;

  isEmpty: boolean;

  readonly TEXT_CLASS = 'text';

  readonly DEFAULT = '(Empty value)';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.value]);
    this.view.class += ' ' + this.TEXT_CLASS;
    this.edit = this.view.mode === ViewMode.EDIT;
    this.isEmpty = this.view.value.isEmpty();
  }

}
