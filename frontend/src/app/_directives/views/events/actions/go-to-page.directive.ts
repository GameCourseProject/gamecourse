import {Directive, HostListener, Input} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";

import {GoToPageEvent} from "../../../../_domain/views/events/actions/go-to-page-event";
import {View, ViewMode} from "../../../../_domain/views/view";
import {exists} from "../../../../_utils/misc/misc";

@Directive({selector: '[goToPage]'})
export class GoToPageDirective {

  @Input('goToPage') info: {event: GoToPageEvent, view: View};

  constructor(
    private router: Router,
    private route: ActivatedRoute
  ) { }


  /**
   * Go to a page in the active course.
   *
   * @param pageId
   * @param userId (optional)
   */
  goToPage(pageId: string, userId?: string): void {
    let path = 'pages/' + pageId;
    if (userId) path += '/user/' + userId;
    this.router.navigate([path], {relativeTo: this.route.parent.parent});
  }

  @HostListener('click', ['$event'])
  @HostListener('dblclick', ['$event'])
  @HostListener('mouseover', ['$event'])
  @HostListener('mouseout', ['$event'])
  @HostListener('mouseup', ['$event'])
  @HostListener('wheel', ['$event'])
  @HostListener('drag', ['$event'])
  onEvent(event: Event) {
    // Do nothing
    if (!exists(this.info.event) || this.info.view.mode === ViewMode.EDIT) return;

    // Perform event
    if (event.type === this.info.event.type)
      this.goToPage(this.info.event.pageId, this.info.event.userId);
  }

}
