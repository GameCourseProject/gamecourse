import {Component, ElementRef, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {
  GlassesTypes,
  ClothingGraphicTypes,
  ClothingTypes,
  Colors,
  EyebrowTypes,
  EyeTypes,
  FacialHairTypes,
  HairTypes, MouthTypes,
  NoseTypes,
} from "./model";
import * as svg from 'save-svg-as-png';

@Component({
  selector: 'app-avatar-generator',
  templateUrl: './avatar-generator.component.html',
  styleUrls: ['./avatar-generator.component.css']

})
export class AvatarGeneratorComponent implements OnInit {
  @Input() public shape: 'round' | 'square' = 'round';
  @Input() public enableBackground: boolean;
  @Input() public enableFace: boolean;
  @Input() public displayDownload: boolean;
  @Output() private svgUrl = new EventEmitter<string>();
  @ViewChild('avatar', {read: ElementRef}) avatar: ElementRef;

  public selectedHair: HairTypes;
  public selectedEyebrow: EyebrowTypes;
  public selectedEye: EyeTypes;
  public selectedMouth: MouthTypes;
  public selectedFacialHair: FacialHairTypes;
  public selectedGlasses: GlassesTypes;
  public selectedNose: NoseTypes;
  public selectedClothing: ClothingTypes;
  public selectedClothingGraphic: ClothingGraphicTypes;

  public colors: Colors = {
    hair: '',
    skin: '',
    clothes: '',
    graphic: '',
    accessory: '',
    background: '',
    eyebrows: '',
    eyes: '',
    mouth: '',
    facialHair: '',
    glasses: ''
  };

  hairTypesArray: HairTypes[] = Object.keys(HairTypes) as HairTypes[];
  eyebrowTypesArray: EyebrowTypes[] = Object.keys(EyebrowTypes) as EyebrowTypes[];
  eyeTypesArray: EyeTypes[] = Object.keys(EyeTypes) as EyeTypes[];
  mouthTypesArray: MouthTypes[] = Object.keys(MouthTypes) as MouthTypes[];
  noseTypesArray: NoseTypes[] = Object.keys(NoseTypes) as NoseTypes[];
  facialHairTypesArray: FacialHairTypes[] = Object.keys(FacialHairTypes) as FacialHairTypes[];
  glassesTypesArray: GlassesTypes[] = Object.keys(GlassesTypes) as GlassesTypes[];
  clothingTypesArray: ClothingTypes[] = Object.keys(ClothingTypes) as ClothingTypes[];
  clothingGraphicTypesArray: ClothingGraphicTypes[] = Object.keys(ClothingGraphicTypes) as ClothingGraphicTypes[];

  private url: string;
  constructor() {
  }

  ngOnInit(): void {
    this.goCompletelyRandom();
  }

  public goCompletelyRandom(): void {
    this.getRandomColors();
    this.getRandomHairStyle();
    this.getRandomEyebrowType();
    this.getRandomEyeType();
    this.getRandomNoseType();
    this.getRandomMouthType();
    this.getRandomFacialHairType();
    this.getRandomGlassesType();
    this.getRandomClothing();
    this.getRandomClothingGraphic()
  }

  public getRandomColors(): void {
    for (const [key, value] of Object.entries(this.colors)) {
      this.colors[key] = this.randomizeColor();
    }
  }

  public getRandomHairStyle(): void {
    this.selectedHair = this.getRandomStyle(this.hairTypesArray, HairTypes).name;
  }

  public getRandomEyebrowType(): void {
    this.selectedEyebrow = this.getRandomStyle(this.eyebrowTypesArray, EyebrowTypes).name;
  }

  public getRandomEyeType(): void {
    this.selectedEye = this.getRandomStyle(this.eyeTypesArray, EyeTypes).name;
  }

  public getRandomNoseType(): void {
    this.selectedNose = this.getRandomStyle(this.noseTypesArray, NoseTypes).name;
  }

  public getRandomMouthType(): void {
    this.selectedMouth = this.getRandomStyle(this.mouthTypesArray, MouthTypes).name;
  }

  public getRandomFacialHairType(): void {
    this.selectedFacialHair = this.getRandomStyle(this.facialHairTypesArray, FacialHairTypes).name;
  }

  public getRandomGlassesType(): void {
    this.selectedGlasses = this.getRandomStyle(this.glassesTypesArray, GlassesTypes).name;
  }

  public getRandomClothing(): void {
    this.selectedClothing = this.getRandomStyle(this.clothingTypesArray, ClothingTypes).name;
  }

  public getRandomClothingGraphic(): void {
    this.selectedClothingGraphic = this.getRandomStyle(this.clothingGraphicTypesArray, ClothingGraphicTypes).name;
  }

  public selectHairType(hair: HairTypes): void {
    this.selectedHair = hair;
  }

  public selectEyebrowType(eyebrow: EyebrowTypes): void {
    this.selectedEyebrow = eyebrow;
  }

  public selectEyeType(eyes: EyeTypes): void {
    this.selectedEye = eyes;
  }

  public selectNoseType(nose: NoseTypes): void {
    this.selectedNose = nose;
  }

  public selectMouthType(mouth: MouthTypes): void {
    this.selectedMouth = mouth;
  }

  public selectFacialHairType(hair: FacialHairTypes): void {
    this.selectedFacialHair = hair;
  }

  public selectGlassesType(glasses: GlassesTypes): void {
    this.selectedGlasses = glasses;
  }

  public selectClothingType(clothes: ClothingTypes): void {
    this.selectedClothing = clothes;
  }

  public selectClothingGraphicType(graphic: ClothingGraphicTypes): void {
    this.selectedClothingGraphic = graphic;
  }

  private getRandomStyle(typesArray: Array<any>, enumm: typeof HairTypes | typeof ClothingTypes | typeof ClothingGraphicTypes | typeof EyebrowTypes | typeof EyeTypes | typeof NoseTypes | typeof MouthTypes | typeof FacialHairTypes | typeof GlassesTypes): { name: any, index: number } {
    const index = this.randomIntFromInterval(0, typesArray.length - 1);
    return {
      name: enumm[typesArray[index]],
      index: index
    };
  }

  private iterateOverOptions(value: { name: any, index: number }, typesArray: Array<any>, enumName: typeof HairTypes | typeof ClothingTypes | typeof ClothingGraphicTypes | typeof EyebrowTypes | typeof EyeTypes | typeof NoseTypes | typeof MouthTypes | typeof FacialHairTypes | typeof GlassesTypes, upwards: boolean): { name: any, index: number } {
    let newIndex = upwards ? value.index + 1 : value.index - 1;
    if (newIndex === typesArray.length) {
      newIndex = 0;
    } else if (newIndex === -1) {
      newIndex = typesArray.length - 1
    }
    return {
      name: enumName[typesArray[newIndex]],
      index: newIndex
    };
  }

  public prepareLink(): void {
    const svg = this.avatar?.nativeElement?.querySelector('svg');
    const serializer = new XMLSerializer();
    if (svg) {
      let source = serializer.serializeToString(svg);
      source = '<?xml version="1.0" standalone="no"?>\r\n' + source;
      const url = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(source);
      this.url = url.replace(/%3C!--.*?--%3E/g, '');
      this.svgUrl.emit(this.url);
    }
  }

  public doDownload(format: string): void {
    if (format === 'png') {
      svg.saveSvgAsPng(this.avatar?.nativeElement?.querySelector('svg'), 'avatar.png');
    } else {
      const downloadLink = document.createElement("a");
      downloadLink.href = this.url;
      downloadLink.download = "avatar.svg";
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    }
  }

  public getColorsObject() {
    return {...this.colors};
  }


  /*** --------------------------------------------- ***/
  /*** ------------------- Utils ------------------- ***/
  /*** --------------------------------------------- ***/

  randomIntFromInterval(min: number, max: number): number {
    return Math.floor(Math.random() * (max - min + 1) + min);
  }

  randomizeColor() {
    return "#000000".replace(/0/g,()=> (~~(Math.random()*16)).toString(16));
  }

  protected readonly ClothingTypes = ClothingTypes;
}
