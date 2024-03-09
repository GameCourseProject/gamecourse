export enum HairTypes {
  NONE = 'NONE',
  BUN = 'BUN',
  BOB = 'BOB',
  BIG = 'BIG',
  CURLY = 'CURLY',
  CURVY = 'CURVY',
  DREADS01 = 'DREADS01',
  DREADS02 = 'DREADS02',
  DREADS03 = 'DREADS03',
  FRIDA = 'FRIDA',
  FRO = 'FRO',
  FROANDBAND = 'FROANDBAND',
  LONGNOTTOOLONG = 'LONGNOTTOOLONG',
  MIAWALLACE = 'MIAWALLACE',
  SHAVEDSIDES = 'SHAVEDSIDES',
  STRAIGHTANDSTRAND = 'STRAIGHTANDSTRAND',
  STRAIGHT01 = 'STRAIGHT01',
  STRAIGHT02 = 'STRAIGHT02',
  FRIZZLE = 'FRIZZLE',
  SHAGGY = 'SHAGGY',
  SHAGGYMULLET = 'SHAGGYMULLET',
  SHORTCURLY = 'SHORTCURLY',
  SHORTFLAT = 'SHORTFLAT',
  SHORTROUND = 'SHORTROUND',
  SIDES = 'SIDES',
  SHORTWAVED = 'SHORTWAVED',
  CEASARANDSIDE = 'CEASARANDSIDE',
  CEASAR = 'CEASAR',
}

export enum ClothingTypes {
  BLAZER_SHIRT = 'BLAZER_SHIRT',
  BLAZER_SWEATER = 'BLAZER_SWEATER',
  COLLAR_SWEATER = 'COLLAR_SWEATER',
  GRAPHIC_SHIRT = 'GRAPHIC_SHIRT',
  HOODIE = 'HOODIE',
  OVERALL = 'OVERALL',
  SHIRT_CREWNECK = 'SHIRT_CREWNECK',
  SHIRT_SCOOPNECK = 'SHIRT_SCOOPNECK',
  SHIRT_VNECK = 'SHIRT_VNECK'
}

export enum ClothingGraphicTypes {
  SKRULL_OUTLINE = 'SKRULL_OUTLINE',
  SKRULL = 'SKRULL',
  PIZZA = 'PIZZA',
  DIAMOND = 'DIAMOND',
  DEER = 'DEER',
  BEAR = 'BEAR',
  BAT = 'BAT'
}

export enum EyebrowTypes {
  NONE = 'NONE',
  ANGRY_NATURAL = 'ANGRY_NATURAL',
  DEFAULT_NATURAL = 'DEFAULT_NATURAL',
  FLAT_NATURAL = 'FLAT_NATURAL',
  FROWN_NATURAL = 'FROWN_NATURAL',
  RAISED_EXCITED_NATURAL = 'RAISED_EXCITED_NATURAL',
  SAD_CONCERNED_NATURAL = 'SAD_CONCERNED_NATURAL',
  UNIBROW_NATURAL = 'UNIBROW_NATURAL',
  UP_DOWN_NATURAL = 'UP_DOWN_NATURAL',
  RAISED_EXCITED = 'RAISED_EXCITED',
  ANGRY = 'ANGRY',
  DEFAULT = 'DEFAULT',
  SAD_CONCERNED = 'SAD_CONCERNED',
  UP_DOWN = 'UP_DOWN'
}

export enum EyeTypes {
  NONE = 'NONE',
  SQUINT = 'SQUINT',
  CLOSED = 'CLOSED',
  DEFAULT = 'DEFAULT',
  EYE_ROLL = 'EYE_ROLL',
  HAPPY = 'HAPPY',
  HEARTS = 'HEARTS',
  SIDE = 'SIDE',
  SURPRISED = 'SURPRISED',
  WINK = 'WINK',
  WINK_WACKY = 'WINK_WACKY',
  X_DIZZY = 'X_DIZZY'
}

export enum NoseTypes {
  NONE = 'NONE',
  DEFAULT = 'DEFAULT'
}

export enum MouthTypes {
  NONE = 'NONE',
  CONCERNED = 'CONCERNED',
  DEFAULT = 'DEFAULT',
  DISBELIEF = 'DISBELIEF',
  GRIMACE = 'GRIMACE',
  SAD = 'SAD',
  SCREAM_OPEN = 'SCREAM_OPEN',
  SERIOUS = 'SERIOUS',
  SMILE = 'SMILE',
  TONGUE = 'TONGUE',
  TWINKLE = 'TWINKLE'
}

export enum FacialHairTypes {
  NONE = 'NONE',
  BEARD_LIGHT = 'BEARD_LIGHT',
  BEARD_MAJESTIC = 'BEARD_MAJESTIC',
  BEARD_MEDIUM = 'BEARD_MEDIUM',
  MOUSTACHE_FANCY = 'MOUSTACHE_FANCY',
  MOUSTACHE_MAGNUM = 'MOUSTACHE_MAGNUM',
}

export enum GlassesTypes {
  NONE = 'NONE',
  GLASSES = 'GLASSES',
  GLASSES_ROUND = 'GLASSES_ROUND'
}

export interface Colors {
  hair?: string;
  skin?: string;
  clothes?: string;
  graphic?: string;
  accessory?: string;
  background?: string;
  eyebrows?: string;
  eyes?: string;
  mouth?: string;
  facialHair?: string;
  glasses?: string;
}

export const SkinTones = [
  "#ffe0bd", "#ffcd94", "#eac086", "#ffad60", "#c2854a", "#b06f38", "#6b431d",
  "#f2efee", "#efe6dd", "#ebd3c5", "#d7b6a5", "#9f7967", "#86604c",
  "#523b2d", "#D3B2A3", "#B49F96", "#958C89", "#A28578", "#745F56", "#2E2622", "#211c16",
  "#ffd9e0", "#ffbecb", "#ffb1c1", "#ff9c9c", "#d98181", "#723f3f", "#562424",
]

export const HairColors = [
  "#fff8e7", "#fddcb1", "#fccc87", "#ffb366", "#e89e5d", "#d2853d", "#ba6b2d",
  "#8B4513", "#A52A2A", "#CD853F", "#8B5A2B", "#A36D40", "#B8860B", "#D2B48C",
  "#333333", "#404040", "#4d4d4d", "#595959", "#666666", "#737373", "#808080",
  "#ff9999", "#ff6666", "#ff3333", "#ff0000", "#cc0000", "#990000", "#660000",
];

export const BackgroundColors = [
  '#E57373', '#EF5350', '#F44336', '#E53935', '#D32F2F', '#C62828', '#B71C1C',
  '#F06292', '#EC407A', '#E91E63', '#D81B60', '#C2185B', '#AD1457', '#880E4F',
  '#BA68C8', '#AB47BC', '#9C27B0', '#8E24AA', '#7B1FA2', '#6A1B9A', '#4A148C',
  '#7986CB', '#5C6BC0', '#3F51B5', '#3949AB', '#303F9F', '#283593', '#1A237E',
  '#64B5F6', '#42A5F5', '#2196F3', '#1E88E5', '#1976D2', '#1565C0', '#0D47A1',
  '#4DB6AC', '#26A69A', '#009688', '#00897B', '#00796B', '#00695C', '#004D40',
  '#81C784', '#66BB6A', '#4CAF50', '#43A047', '#388E3C', '#2E7D32', '#1B5E20',
  '#FFD54F', '#FFCA28', '#FFC107', '#FFB300', '#FFA000', '#FF8F00', '#FF6F00',
];

