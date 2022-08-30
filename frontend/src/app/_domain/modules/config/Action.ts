export enum Action {
  NEW = 'new',
  EDIT = 'edit',
  DELETE = 'delete',
  DUPLICATE = 'duplicate',
  VIEW = 'view',
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
  ALL_BUT_FIRST_AND_LAST = 'all-but-first-and-last',
  ALL_BUT_TWO_LAST = 'all-but-two-last'
}

export function scopeAllows(scope: ActionScope, nrItems: number, index: number, first: boolean, last: boolean, even: boolean, odd: boolean): boolean {
  if (scope === ActionScope.ALL) return true;
  if (scope === ActionScope.FIRST && first) return true;
  if (scope === ActionScope.LAST && last) return true;
  if (scope === ActionScope.EVEN && even) return true;
  if (scope === ActionScope.ODD && odd) return true;
  if (scope === ActionScope.ALL_BUT_FIRST &&  !first) return true;
  if (scope === ActionScope.ALL_BUT_LAST &&  !last) return true;
  if (scope === ActionScope.ALL_BUT_FIRST_AND_LAST && !first && !last) return true;
  if (scope === ActionScope.ALL_BUT_TWO_LAST && index < nrItems - 2) return true;
  return false;
}

export function showAtLeastOnce(scope: ActionScope, nrItems: number): boolean {
  if (scope === ActionScope.ALL) return true;
  if (scope === ActionScope.FIRST && nrItems !== 0) return true;
  if (scope === ActionScope.LAST && nrItems !== 0) return true;
  if (scope === ActionScope.EVEN && nrItems !== 0) return true;
  if (scope === ActionScope.ODD && nrItems >= 1) return true;
  if (scope === ActionScope.ALL_BUT_FIRST && nrItems > 1) return true;
  if (scope === ActionScope.ALL_BUT_LAST && nrItems > 1) return true;
  if (scope === ActionScope.ALL_BUT_FIRST_AND_LAST && nrItems > 2) return true;
  if (scope === ActionScope.ALL_BUT_TWO_LAST && nrItems > 2) return true;
  return false;
}
