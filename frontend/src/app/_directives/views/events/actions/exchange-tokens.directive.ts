import {Directive, HostListener, Input} from '@angular/core';
import {View, ViewMode} from "../../../../_domain/views/view";
import {exists} from "../../../../_utils/misc/misc";
import {ExchangeTokensEvent} from "../../../../_domain/views/events/actions/exchange-tokens-event";
import {AlertService, AlertType} from "../../../../_services/alert.service";
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ActivatedRoute, Router} from "@angular/router";

@Directive({selector: '[exchangeTokens]'})
export class ExchangeTokensDirective {
  @Input('exchangeTokens') info: {event: ExchangeTokensEvent, view: View};
  courseId: number;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private router: Router
  ) { }

  ngOnInit(){
    this.route.parent.parent.params.subscribe(async params => {
      // Get course information
      this.courseId = parseInt(params.id);
    })
  }

  /**
   * Exchanges gold in the active course.
   *
   * @param userId
   * @param ratio
   * @param threshold
   * @param extra
   */
  async exchangeTokens(userId: number, ratio: string, threshold: number, extra: boolean) {
    const earnedXP = await this.api.exchangeUserTokens(this.courseId, userId, ratio, threshold, extra).toPromise();
    AlertService.showAlert(AlertType.SUCCESS, 'You earned ' + earnedXP + ' XP!');
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
      this.exchangeTokens(this.info.event.userId, this.info.event.ratio, this.info.event.threshold, this.info.event.extra);
  }

}
