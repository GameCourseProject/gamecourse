import {Component, Input, OnInit} from '@angular/core';
import {ViewText} from "../../../_domain/views/view-text";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode, VisibilityType} from "../../../_domain/views/view";
import {EventGoToPage} from "../../../_domain/events/event-go-to-page";
import {Event} from "../../../_domain/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/events/event-action";
import {EventHideView} from 'src/app/_domain/events/event-hide-view';
import {EventShowView} from "../../../_domain/events/event-show-view";
import {EventToggleView} from 'src/app/_domain/events/event-toggle-view';

@Component({
  selector: 'bb-text',
  templateUrl: './text.component.html'
})
export class TextComponent implements OnInit {

  @Input() view: ViewText;
  edit: boolean;

  readonly DEFAULT = '(Empty value)';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.value]);
    if (!this.edit && !!this.view.events?.click) this.view.class += ' gc-clickable';
    this.edit = this.view.mode === ViewMode.EDIT;

    if (this.view.value.isEmpty()) this.view.class += ' ' + ViewText.TEXT_EMPTY_CLASS;
    else this.view.class = this.view.class.removeWord(ViewText.TEXT_EMPTY_CLASS);

    if (this.view.visibilityType === VisibilityType.INVISIBLE && !this.edit) {
      this.view.style = this.view.style || '';
      this.view.style = this.view.style.concatWithDivider('display: none', ';');
    }
  }


  /*** ---------------------------------------- ***/
  /*** ---------------- Events ---------------- ***/
  /*** ---------------------------------------- ***/

  getEvent(action: EventAction): Event {
    if (!exists(this.view.events)) return null;
    return getEventFromAction(this.view.events, action);
  }

  get EventAction(): typeof EventAction {
    return EventAction;
  }

  get EventGoToPage(): typeof EventGoToPage {
    return EventGoToPage;
  }

  get EventHideView(): typeof EventHideView {
    return EventHideView;
  }

  get EventShowView(): typeof EventShowView {
    return EventShowView;
  }

  get EventToggleView(): typeof EventToggleView {
    return EventToggleView;
  }

}
