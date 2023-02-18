import {Theme} from "../../_services/theming/themes-available";

export function TextColor(theme: Theme): string {
  if (theme === Theme.DARK) return '#BFC6D3';
  return '#1F2937';
}

export function BGDarkColor(theme: Theme): string {
  if (theme === Theme.DARK) return '#1b2027';
  return '#f1f1f1';
}

export function BGLightColor(theme: Theme): string {
  if (theme === Theme.DARK) return '#2A303C';
  return '#ffffff';
}

export function LineColor(theme: Theme): string {
  if (theme === Theme.DARK) return '#111318';
  return '#E5E6E6';
}
