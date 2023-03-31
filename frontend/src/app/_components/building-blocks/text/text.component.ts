import {Component, Input, OnInit} from '@angular/core';
import {Router} from "@angular/router";

import {ViewText} from "../../../_domain/views/view-types/view-text";
import {ViewMode} from "../../../_domain/views/view";
import {EventAction} from "../../../_domain/views/events/event-action";
import {GoToPageEvent} from "../../../_domain/views/events/actions/go-to-page-event";

import {environment} from "../../../../environments/environment";

@Component({
  selector: 'bb-text',
  templateUrl: './text.component.html'
})
export class BBTextComponent implements OnInit {

  @Input() view: ViewText;

  edit: boolean;
  classes: string;

  readonly DEFAULT = '(Empty text)';

  constructor(
    private router: Router
  ) { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;

    // Transform goToPage event into a link
    const goToPageEventIndex = this.view.events.findIndex(ev => ev.action === EventAction.GO_TO_PAGE);
    if (goToPageEventIndex !== -1) {
      const goToPageEvent: GoToPageEvent = this.view.events[goToPageEventIndex] as GoToPageEvent;
      this.view.link = environment.url + '/#/' + this.router.url.split('/').slice(1, 4).join('/') + '/' +
        goToPageEvent.pageId + (goToPageEvent.userId ? '/user/' + goToPageEvent.userId : '');
      this.view.events.splice(goToPageEventIndex, 1);
    }
    this.classes = 'bb-text' + (this.view.link ? ' bb-text-link' : '');
  }

  externalLink(link: string): boolean {
   return !link.containsWord(environment.url);
  }
}
