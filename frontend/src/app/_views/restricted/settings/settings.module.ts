import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SettingsRoutingModule } from './settings-routing.module';
import { SharedModule } from "../../../shared.module";

import { GlobalComponent } from './global/global.component';
import { ModulesComponent } from './modules/modules.component';
import { SidebarComponent } from './sidebar/sidebar.component';
import { SettingsComponent } from './settings/settings.component';

@NgModule({
  declarations: [
     GlobalComponent,
     ModulesComponent,
     SidebarComponent,
     SettingsComponent
  ],
    imports: [
      CommonModule,
      SettingsRoutingModule,
      SharedModule
    ]
})
export class SettingsModule { }
