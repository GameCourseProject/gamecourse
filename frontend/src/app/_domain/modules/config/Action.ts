export enum Action {
  NEW = 'new',
  EDIT = 'edit',
  DELETE = 'delete',
  REMOVE = 'remove',
  DUPLICATE = 'duplicate',
  VIEW = 'view',
  MOVE_UP = 'move-up',
  MOVE_DOWN = 'move-down',
  IMPORT = 'import',
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

export function scopeAllows(scope: ActionScope | string, nrItems: number, index: number): boolean {
  const first = index === 0;
  const last = index === nrItems - 1;
  const even = index % 2 === 0;
  const odd = index % 2 !== 0;

  if (scope === ActionScope.ALL) return true;
  if (scope === ActionScope.FIRST && first) return true;
  if (scope === ActionScope.LAST && last) return true;
  if (scope === ActionScope.EVEN && even) return true;
  if (scope === ActionScope.ODD && odd) return true;
  if (scope === ActionScope.ALL_BUT_FIRST && !first) return true;
  if (scope === ActionScope.ALL_BUT_LAST && !last) return true;
  if (scope === ActionScope.ALL_BUT_FIRST_AND_LAST && !first && !last) return true;
  if (scope === ActionScope.ALL_BUT_TWO_LAST && index < nrItems - 2) return true;

  // ALL_BUT_INDEXES (format: "[0, 3]")
  const indexes = scope.slice(1, -1).split(",").map(i => parseInt(i.trim()));
  return !indexes.includes(index);
}
