import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { UsersRoutingModule } from './users-routing.module';
import { MainComponent } from './main/main.component';
import {SharedModule} from "../../_components/shared.module";


@NgModule({
  declarations: [
    MainComponent
  ],
    imports: [
        CommonModule,
        UsersRoutingModule,
        SharedModule
    ]
})
export class UsersModule { }
