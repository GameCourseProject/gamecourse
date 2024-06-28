import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SettingsRoutingModule } from './settings-routing.module';
import { SharedModule } from "../../../shared.module";

import { ModulesComponent } from './modules/modules.component';
import {ThemesComponent} from "./themes/themes.component";

@NgModule({
  declarations: [
    ModulesComponent,
    ThemesComponent
  ],
    imports: [
      CommonModule,
      SettingsRoutingModule,
      SharedModule
    ]
})
export class SettingsModule { }
