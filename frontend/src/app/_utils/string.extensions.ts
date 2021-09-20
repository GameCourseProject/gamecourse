interface String {
  swapPTChars(): string;
  isEmpty(): boolean;
}

/**
 * Replaces Portuguese characters by their English counterparts.
 *
 * @return string
 */
String.prototype.swapPTChars = function(): string {
  return this.replace(/[ãáâà]/ig, 'a')
    .replace(/[óôõ]/ig, 'o')
    .replace(/ç/ig, 'c')
    .replace(/[éê]/ig, 'e')
    .replace(/í/ig, 'i')
    .replace(/ú/ig, 'u');
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
