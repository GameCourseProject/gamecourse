import {Component, Input, OnInit} from '@angular/core';
import {ViewText} from "../../../_domain/views/view-text";
import {requireValues} from "../../../_utils/misc/misc";

@Component({
  selector: 'bb-text',
  templateUrl: './text.component.html',
  styleUrls: ['./text.component.scss']
})
export class TextComponent implements OnInit {

  @Input() view: ViewText;
  @Input() edit: boolean;

  readonly TEXT_CLASS = 'text';

  isEmpty: boolean;

  constructor() { }

  ngOnInit(): void {
    requireValues([this.view.value]);

    this.view.class += ' ' + this.TEXT_CLASS;

    if (this.view.value.isEmpty()) {
      this.isEmpty = true;
      this.view.value = '(Empty value)';
    }
  }

}
