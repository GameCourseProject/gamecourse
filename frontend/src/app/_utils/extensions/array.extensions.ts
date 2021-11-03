interface Array<T> {
  isEqual(other: any[]): boolean;
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
