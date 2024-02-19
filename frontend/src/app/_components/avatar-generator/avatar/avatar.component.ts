import {Component, Input, OnChanges, Output, SimpleChanges, EventEmitter} from '@angular/core';
import {
  GlassesTypes,
  ClothingGraphicTypes,
  ClothingTypes,
  Colors,
  EyebrowTypes,
  EyeTypes, FacialHairTypes,
  HairTypes,
  MouthTypes,
  NoseTypes
} from "../model";

@Component({
  selector: 'app-avatar',
  templateUrl: './avatar.component.svg',
})
export class AvatarComponent implements OnChanges {
  @Input() public hairType: HairTypes;
  @Input() public eyebrowType: EyebrowTypes;
  @Input() public eyeType: EyeTypes;
  @Input() public noseType: NoseTypes;
  @Input() public mouthType: MouthTypes;
  @Input() public facialHairType: FacialHairTypes;
  @Input() public glassesType: GlassesTypes;
  @Input() public clothing: ClothingTypes;
  @Input() public clothingGraphic: ClothingGraphicTypes;
  @Input() public colors: Colors = {
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
  @Input() public enableBackground: boolean = false;
  @Input() public enableFace: boolean = false;
  @Input() public shape: 'round' | 'square' = 'round';

  @Output() private avatarChanged = new EventEmitter<boolean>();

  public HairTypes = HairTypes;
  public EyebrowTypes = EyebrowTypes;
  public EyeTypes = EyeTypes;
  public NoseTypes = NoseTypes;
  public MouthTypes = MouthTypes;
  public FacialHairTypes = FacialHairTypes;
  public GlassesTypes = GlassesTypes;
  public ClothingTypes = ClothingTypes;
  public ClothingGraphicTypes = ClothingGraphicTypes;

  constructor() {
  }

  ngOnChanges(changes: SimpleChanges): void {
    this.avatarChanged.emit(true);
  }

  lightenDarkenColor(col: string, amt: number): string {
    let usePound = false;
    if ( col[0] == "#" ) {
      col = col.slice(1);
      usePound = true;
    }
  
    let num = parseInt(col,16);
    let r = (num >> 16) + amt;
    if ( r > 255 ) r = 255;
    else if  (r < 0) r = 0;
  
    let b = ((num >> 8) & 0x00FF) + amt;
    if ( b > 255 ) b = 255;
    else if  (b < 0) b = 0
  
    let g = (num & 0x0000FF) + amt;
    if ( g > 255 ) g = 255;
    else if  ( g < 0 ) g = 0;
  
    let string = "000000" + (g | (b << 8) | (r << 16)).toString(16);
    return (usePound?"#":"") + string.substr(string.length-6);
  }
}