import {Component, Input, OnInit} from '@angular/core';
import {ViewText} from "../../../_domain/views/view-text";
import {ErrorService} from "../../../_services/error.service";

@Component({
  selector: 'bb-text',
  templateUrl: './text.component.html',
  styleUrls: ['./text.component.scss']
})
export class TextComponent implements OnInit {

  @Input() view: ViewText;
  @Input() edit: boolean;

  isEmpty: boolean;

  constructor() { }

  ngOnInit(): void {
    if (this.view.value === null || this.view.value === undefined)
      ErrorService.set('ViewText requires a value \'value\'.');

    if (this.view.value.isEmpty()) {
      this.isEmpty = true;
      this.view.value = '(Empty value)';
    }
  }

}
