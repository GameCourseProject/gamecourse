export function removePTCharacters(s: string): string {
  return s.replace(/[ãáâà]/ig, 'a')
    .replace(/[óôõ]/ig, 'o')
    .replace(/ç/ig, 'c')
    .replace(/[éê]/ig, 'e')
    .replace(/í/ig, 'i')
    .replace(/ú/ig, 'u');
}
