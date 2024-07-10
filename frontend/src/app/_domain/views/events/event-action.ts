export enum EventAction {
  GO_TO_PAGE = 'goToPage',
  SHOW_TOOLTIP = 'showTooltip',
  EXCHANGE_TOKENS = 'exchangeTokensForXP'
}

// TODO: Complete the types here, and add more verifications in event-card
export const EventActionHelper: Record<EventAction, {name: string, args: string[], types?: string[]}> = {
  [EventAction.GO_TO_PAGE]: {
    name: 'actions.goToPage',
    args: ['pageId', 'userId (optional)', 'isSkill (optional)']
    // types: FIXME
  },
  [EventAction.SHOW_TOOLTIP]: {
    name: 'actions.showTooltip',
    args: ['text', 'position (optional)'],
    types: ['string']
  },
  [EventAction.EXCHANGE_TOKENS]: {
    name: 'vc.exchangeTokensForXP',
    args: ['userId', 'ratio', 'threshold', 'extra'],
    types: [null, 'string', null, null] // FIXME
  }
};
