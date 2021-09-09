/**
 * This class converts TypeScript types to MySQL types,
 * and vice-versa FIXME: maybe delete
 */

const MYSQL_DATE_FORMAT = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;

export class TypesConverter {

  public toDatabase(value: any): any {
    if (typeof value === 'boolean') return value ? 1 : 0;
    else if (value instanceof Date) return value.toLocaleString().replace(/\//g, '-').replace(',', '');
    return value;
  }

  public fromDatabase(value: any): any {
    if (MYSQL_DATE_FORMAT.test(value)) return new Date(value);
    return value;
  }

}
