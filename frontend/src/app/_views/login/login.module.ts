import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { LoginRoutingModule } from './login-routing.module';
import { LoginComponent } from './login/login.component';
import {SharedModule} from "../../shared.module";
import {NgIconsModule} from "@ng-icons/core";


@NgModule({
  declarations: [
    LoginComponent
  ],
    imports: [
        CommonModule,
        LoginRoutingModule,
        SharedModule,
        NgIconsModule
    ]
})
export class LoginModule { }
