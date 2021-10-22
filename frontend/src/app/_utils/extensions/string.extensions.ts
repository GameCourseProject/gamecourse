interface String {
  toFlat(): string;
  isEmpty(): boolean;
  swapNonENChars(): string;
  noWhiteSpace(): string;
}

/**
 * Checks if string is empty.
 *
 * @return true, if has no alphanumeric characters
 * @return false, otherwise
 */
String.prototype.isEmpty = function (): boolean {
  return this.replace(/\s*/g, '') === '';
}


/**
 * Flattens a string to a lowercase and English characters
 * only string.
 *
 * @return string
 */
String.prototype.toFlat = function(): string {
  return this.toLowerCase().swapNonENChars();
}


/**
 * Replaces non-English characters by their English counterparts.
 *
 * @return string
 */
String.prototype.swapNonENChars = function(): string {
  return this.replace(/[ãáâàåä]/ig, 'a')
    .replace(/[óôõòøö]/ig, 'o')
    .replace(/ç/ig, 'c')
    .replace(/[éêè]/ig, 'e')
    .replace(/[íì]/ig, 'i')
    .replace(/[úùüû]/ig, 'u')
    .replace(/ñ/ig, 'n')
    .replace(/ß/ig, 'b')
    .replace(/æ/ig, 'ae');
}


/**
 * Removes whitespace from a string.
 *
 * @return string
 */
String.prototype.noWhiteSpace = function(): string {
  return this.replace(/\s*/g, '');
}
