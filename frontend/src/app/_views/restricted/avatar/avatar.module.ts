import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import {AvatarRoutingModule} from "./avatar-routing.module";
import { SharedModule } from "../../../shared.module";

import {AvatarComponent} from "./avatar/avatar.component";


@NgModule({
  declarations: [
    AvatarComponent
  ],
  imports: [
    CommonModule,
    AvatarRoutingModule,
    SharedModule
  ]
})
export class AvatarModule { }
