import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SettingsRoutingModule } from './settings-routing.module';
import {SharedModule} from "../../_components/shared.module";
import { GlobalComponent } from './global/global.component';
import { ModulesComponent } from './modules/modules.component';
import { AboutComponent } from './about/about.component';
import { SidebarComponent } from './sidebar/sidebar.component';
import {FormsModule} from "@angular/forms";
import { SettingsComponent } from './settings/settings.component';


@NgModule({
  declarations: [
     GlobalComponent,
     ModulesComponent,
     AboutComponent,
     SidebarComponent,
     SettingsComponent
  ],
    imports: [
      CommonModule,
      SettingsRoutingModule,
      SharedModule,
      FormsModule
    ]
})
export class SettingsModule { }
