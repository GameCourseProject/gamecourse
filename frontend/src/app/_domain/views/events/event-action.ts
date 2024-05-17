export enum EventAction {
  GO_TO_PAGE = 'goToPage',
  SHOW_TOOLTIP = 'showTooltip',
  EXCHANGE_TOKENS = 'exchangeTokensForXP'
}

export const EventActionHelper: Record<EventAction, {name: string, args: string[]}> = {
  [EventAction.GO_TO_PAGE]: {
    name: 'actions.goToPage',
    args: ['pageId', 'userId (optional)', 'isSkill (optional)']
  },
  [EventAction.SHOW_TOOLTIP]: {
    name: 'actions.showTooltip',
    args: ['text', 'position (optional)']
  },
  [EventAction.EXCHANGE_TOKENS]: {
    name: 'vc.exchangeTokensForXP',
    args: ['userId', 'ratio', 'threshold', 'extra']
  }
};
