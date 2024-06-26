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

export interface SelectedTypes {
  hair?: HairTypes;
  clothes?: ClothingTypes;
  graphic?: ClothingGraphicTypes;
  eyebrows?: EyebrowTypes;
  eyes?: EyeTypes;
  mouth?: MouthTypes;
  facialHair?: FacialHairTypes;
  glasses?: GlassesTypes;
  nose?: NoseTypes;
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
  "#FFE0BD", "#FFCD94", "#EAC086", "#FFAD60",
    "#C2854A", "#B06F38", "#6B431D",
  "#F2EFEE", "#EFE6DD", "#EBD3C5", "#D7B6A5",
    "#9F7967", "#86604C", "#523B2D",
  "#D3B2A3", "#B49F96", "#958C89", "#A28578",
    "#745F56", "#2E2622", "#211C16",
  "#FFD9E0", "#FFBECB", "#FFB1C1", "#FF9C9C",
    "#D98181", "#723F3F", "#562424",
]

export const HairColors = [
  "#FDDCB1", "#FCCC87", "#FFB366",
    "#E89E5D", "#D2853D", "#BA6B2D", "#8B4513",
  "#E0C9A8", "#D1B186", "#B18F60", "#8B5A2B",
  "#754B27", "#613A1F", "#4F2A15",
  "#808080", "#737373", "#666666", "#595959",
    "#4D4D4D", "#404040", "#333333",
  "#F06292", "#E53935", "#FBC02D", "#4CAF50",
    "#26A69A", "#2196F3", "#4A148C",
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

