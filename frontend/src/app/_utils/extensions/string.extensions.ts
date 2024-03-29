interface String {
  capitalize(): string;
  concatWithDivider(str: string, divider: string);
  containsWord(word: string): boolean;
  isEmpty(): boolean;
  noWhiteSpace(replace: string): string;
  removeWord(word: string): string;
  swapNonENChars(): string;
  toFlat(): string;
}

/**
 * Capitalizes string.
 *
 * @return string
 */
String.prototype.capitalize = function(): string {
  return this[0].toUpperCase() + this.substr(1);
}

/**
 * Concatenates a string with a given divider.
 *
 * @return string
 */
String.prototype.concatWithDivider = function(str: string, divider: string): string {
  const parts = this.split(divider);
  parts.push(str);
  return parts.filter(part => !part.isEmpty()).join(divider);
}

/**
 * Checks if a string contains a given word.
 *
 * @return string
 */
String.prototype.containsWord = function(word: string): boolean {
  return this.includes(word);
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
 * Removes whitespace from a string.
 *
 * @return string
 */
String.prototype.noWhiteSpace = function(replace: string = ''): string {
  return this.replace(/\s+/g, replace);
}

/**
 * Removes specific word from string.
 *
 * @return string
 */
String.prototype.removeWord = function(word: string): string {
  return this.replace(word, '');
}

/**
 * Replaces non-English characters by their English counterparts.
 *
 * @return string
 */
String.prototype.swapNonENChars = function(): string {
  return this.replaceAll(/[ãáâàåä]/ug, 'a')
    .replaceAll(/[ÃÁÂÀÅÄ]/ug, 'A')

    .replaceAll(/[óôõòøö]/ug, 'o')
    .replaceAll(/[ÓÔÕÒØÖ]/ug, 'O')

    .replaceAll(/ç/ug, 'c')
    .replaceAll(/Ç/ug, 'C')

    .replaceAll(/[éêè]/ug, 'e')
    .replaceAll(/[ÉÊÈ]/ug, 'E')

    .replaceAll(/[íì]/ug, 'i')
    .replaceAll(/[ÍÌ]/ug, 'I')

    .replaceAll(/[úùüû]/ug, 'u')
    .replaceAll(/[ÚÙÜÛ]/ug, 'U')

    .replaceAll(/ñ/ug, 'n')
    .replaceAll(/Ñ/ug, 'N')

    .replaceAll(/ß/ug, 'b')

    .replaceAll(/æ/ug, 'ae')
    .replaceAll(/Æ/ug, 'AE')
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
