export enum Action {
  NEW = 'new',
  EDIT = 'edit',
  DELETE = 'delete',
  DUPLICATE = 'duplicate',
  PREVIEW = 'preview',
  MOVE_UP = 'move-up',
  MOVE_DOWN = 'move-down',
  EXPORT = 'export'
}

export enum ActionScope {
  ALL = 'all',
  FIRST = 'first',
  LAST = 'last',
  EVEN = 'even',
  ODD = 'odd',
  ALL_BUT_FIRST = 'all-but-first',
  ALL_BUT_LAST = 'all-but-last',
}
