export enum ViewType {
  TEXT = 'text',
  IMAGE = 'image',
  TABLE = 'table',
  TABLE_HEADER_ROW = 'headerRow', // FIXME: remove this (update .sql & .txt in modules)
  TABLE_ROW = 'row',
  CHART = 'chart', // TODO
  HEADER = 'header',
  BLOCK = 'block'
}
