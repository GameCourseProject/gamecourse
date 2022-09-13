import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { AppRoutingModule } from './app-routing.module';
import { HTTP_INTERCEPTORS, HttpClientModule } from "@angular/common/http";
import { AppComponent } from './app.component';

import { SharedModule } from "./shared.module";
import { CacheInterceptor } from "./_interceptors/cache.interceptor";

import { DataTablesModule } from "angular-datatables";
import { NgIconsModule } from "@ng-icons/core";

import {
  FeatherMoon,
  FeatherSun
} from "@ng-icons/feather-icons";

import {
  JamGoogle,
  JamFacebook,
  JamLinkedin
} from "@ng-icons/jam-icons";

@NgModule({
  declarations: [
    AppComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    SharedModule,
    DataTablesModule,
    NgIconsModule.withIcons({
      FeatherMoon,
      FeatherSun,
      JamGoogle,
      JamFacebook,
      JamLinkedin
    })
  ],
  providers: [{
    provide: HTTP_INTERCEPTORS,
    useClass: CacheInterceptor,
    multi: true
  }],
  exports: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
