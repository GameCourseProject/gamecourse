/**
 * This class converts an object with snake_case keys to
 * an object with camelCase keys, and vice-versa:
 * @example {"snake_case": 1} -> {"snakeCase": 1}
 *
 * It also converts object values from MySQL to JavaScript
 * @example 1 -> true or "yyyy-mm-dd hh:mm:ss" -> Date
 */

export class ObjectKeysConverter {

  public keysToCamelCase(obj: Object): Object {
    if (ObjectKeysConverter.isObject(obj)) {
      const n = {};

      Object.keys(obj).forEach((key) => {
        n[ObjectKeysConverter.toCamelCase(key)] = this.keysToCamelCase(obj[key]);
      });

      return n;
    }

    return obj;
  }

  public keysToSnakeCase(obj: Object): Object {
    if (ObjectKeysConverter.isObject(obj)) {
      const n = {};

      Object.keys(obj).forEach((key) => {
        n[ObjectKeysConverter.toSnakeCase(key)] = this.keysToSnakeCase(obj[key]);
      });

      return n;
    }

    return obj;
  }

  private static isObject(o): boolean {
    return o === Object(o) && !Array.isArray(o) && typeof o !== 'function';
  };

  private static toCamelCase(s: string): string {
    return s.replace(/([-_][a-z])/ig, ($1) => {
      return $1.toUpperCase()
        .replace('_', '');
    });
  };

  private static toSnakeCase(s: string): string {
    return s.replace(/([A-Z])/g, ($1) => {
      return '_' + $1.toLowerCase();
    });
  };

}
