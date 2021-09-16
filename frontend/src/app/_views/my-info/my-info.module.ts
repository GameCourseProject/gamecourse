import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { MyInfoRoutingModule } from './my-info-routing.module';
import { MyInfoComponent } from './my-info/my-info.component';
import {SharedModule} from "../../_components/shared.module";
import {FormsModule} from "@angular/forms";


@NgModule({
  declarations: [
    MyInfoComponent
  ],
  imports: [
    CommonModule,
    MyInfoRoutingModule,
    SharedModule,
    FormsModule
  ]
})
export class MyInfoModule { }
