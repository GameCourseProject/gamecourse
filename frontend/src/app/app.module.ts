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
  FeatherHome,
  FeatherInfo,
  FeatherLayout,
  FeatherLogOut,
  FeatherMenu,
  FeatherMoon,
  FeatherSearch,
  FeatherSun,
  FeatherUser,
  FeatherUsers,
  FeatherX
} from "@ng-icons/feather-icons";

import {
  JamGoogle,
  JamFacebook,
  JamLinkedin
} from "@ng-icons/jam-icons";

import {
  TablerBooks,
  TablerClipboardList,
  TablerColorSwatch,
  TablerIdBadge2,
  TablerPlug,
  TablerPrompt
} from "@ng-icons/tabler-icons";

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
      FeatherHome,
      FeatherInfo,
      FeatherLayout,
      FeatherLogOut,
      FeatherMenu,
      FeatherMoon,
      FeatherSearch,
      FeatherSun,
      FeatherUser,
      FeatherUsers,
      FeatherX,

      JamGoogle,
      JamFacebook,
      JamLinkedin,

      TablerBooks,
      TablerClipboardList,
      TablerColorSwatch,
      TablerIdBadge2,
      TablerPlug,
      TablerPrompt
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
