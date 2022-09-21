interface Number {
  format(type?: 'none' | 'default' | 'money' | 'percent'): string;
  countDecimals(): number;
}

/**
 * Formats a number according the type given.
 * @example (type = none)     123456.789 --> 123456789
 * @example (type = default)  123456.789 --> 123 456,789
 * @example (type = money)    123456.789 --> 123 456,79 €
 * @example (type = percent)  123456.789 --> 123 456,789%
 */
Number.prototype.format = function (type: string): string {
  switch (type) {
    case 'none':
      return this.toString();

    case 'money':
      return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(this);

    case 'percent':
      return new Intl.NumberFormat('pt-PT', { style: 'percent', minimumFractionDigits: this.countDecimals()}).format(this / 100);

    default:
      return new Intl.NumberFormat('pt-PT').format(this);
  }
}

/**
 * Gets the count of decimal digits in a number.
 * @example 123 --> 0
 * @example 123.45 --> 2
 */
Number.prototype.countDecimals = function (): number {
  if (Math.floor(this.valueOf()) === this.valueOf()) return 0;
  return this.toString().split(".")[1].length || 0;
}
