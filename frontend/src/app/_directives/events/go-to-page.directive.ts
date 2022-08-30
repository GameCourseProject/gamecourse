import {Directive, HostListener, Input} from '@angular/core';
import {ActivatedRoute, Router} from "@angular/router";
import {EventGoToPage} from "../../_domain/views/events/event-go-to-page";
import {exists} from "../../_utils/misc/misc";
import {ViewMode} from "../../_domain/views/view";

@Directive({
  selector: '[goToPage]'
})
export class GoToPageDirective {

  @Input('goToPage') event: EventGoToPage;
  @Input() mode: ViewMode;

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
    const path = 'pages/' + pageId + (userId ? '/user/' + userId : '');
    this.router.navigate([path], {relativeTo: this.route.parent})
  }

  @HostListener('click', ['$event'])
  @HostListener('dblclick', ['$event'])
  @HostListener('mouseover', ['$event'])
  @HostListener('mouseout', ['$event'])
  @HostListener('mouseup', ['$event'])
  @HostListener('wheel', ['$event'])
  @HostListener('drag', ['$event'])
  onEvent(event: Event) {
    if (!exists(this.event) || this.mode === ViewMode.EDIT) return;
    if (event.type === this.event.type)
      this.goToPage(this.event.pageId, this.event.userId || null);
  }

}
