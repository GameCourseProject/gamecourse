import {Component, Input, OnInit} from '@angular/core';
import {Router} from "@angular/router";

import {ViewButton} from "../../../_domain/views/view-types/view-button";
import {ViewMode} from "../../../_domain/views/view";

import {EventAction} from "../../../_domain/views/events/event-action";
import {GoToPageEvent} from "../../../_domain/views/events/actions/go-to-page-event";
import {environment} from "../../../../environments/environment";

@Component({
  selector: 'bb-button',
  templateUrl: './button.component.html'
})
export class BBButtonComponent implements OnInit {

  @Input() view: ViewButton;

  edit: boolean;
  classes: string;
  link: string;

  readonly DEFAULT = 'tabler-question-mark';

  constructor(
    private router: Router
  ) { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    if (this.view.color) this.classes += ' bb-button-colored';
    if (this.view.icon) this.classes += ' bb-button-icon';
    if (this.view.classList.includes('btn-')) this.classes = this.view.classList;
    else this.classes = 'bb-button btn btn-ghost';

    // Transform goToPage event into a link
    const goToPageEventIndex = this.view.events.findIndex(ev => ev.action === EventAction.GO_TO_PAGE);
    if (goToPageEventIndex !== -1) {
      const goToPageEvent: GoToPageEvent = this.view.events[goToPageEventIndex] as GoToPageEvent;
      this.link = environment.url + '/#/' + this.router.url.split('/').slice(1, 4).join('/') + '/' +
        goToPageEvent.pageId + (goToPageEvent.userId ? '/user/' + goToPageEvent.userId : '');
      this.view.events.splice(goToPageEventIndex, 1);
    }
  }

  externalLink(link: string): boolean {
    return !link.containsWord(environment.url);
  }

}
