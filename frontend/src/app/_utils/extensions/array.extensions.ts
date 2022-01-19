interface Array<T> {
  insertAtEnd(item: any): void;
  insertAtIndex(index: number, item: any): void;
  insertAtStart(item: any): void;
  isEqual(other: any[]): boolean;
  removeAtIndex(index: number): void;
}

/**
 * Inserts item at the end of array.
 * @example [1, 2, 3] --> [1, 2, 3, X]
 */
Array.prototype.insertAtEnd = function (item: any): void {
  this.push(item);
}

/**
 * Inserts item at a specific index of array.
 * @example (index = 0) [1, 2, 3] --> [X, 1, 2, 3]
 * @example (index = 1) [1, 2, 3] --> [1, X, 2, 3]
 * @example (index = 2) [1, 2, 3] --> [1, 2, X, 3]
 * @example (index = 3) [1, 2, 3] --> [1, 2, 3, X]
 */
Array.prototype.insertAtIndex = function (index: number, item: any): void {
  if (index <= 0) this.insertAtStart(item);
  else if (index >= this.length) this.insertAtEnd(item);
  else this.splice(index, 0, item);
}

/**
 * Inserts item at the start of array.
 * @example [1, 2, 3] --> [X, 1, 2, 3]
 */
Array.prototype.insertAtStart = function (item: any): void {
  this.unshift(item);
}

/**
 * Checks if two arrays have the same values.
 * @example [1, 2, 3] is equal to [3, 2, 1]
 *
 * @return true, if has no alphanumeric characters
 * @return false, otherwise
 */
Array.prototype.isEqual = function (other: any[]): boolean {
  return this.sort().join(',') === other.sort().join(',');
}

/**
 * Removes item at a specific index of array.
 * @example (index = 1) [1, 2, 3] --> [1, 3]
 */
Array.prototype.removeAtIndex = function (index: number): void {
  this.splice(index, 1);
}
