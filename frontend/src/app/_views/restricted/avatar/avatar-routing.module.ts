import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { AvatarComponent } from "./avatar/avatar.component";

const routes: Routes = [
  {
    path: '',
    component: AvatarComponent
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class AvatarRoutingModule { }
