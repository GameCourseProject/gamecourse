import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {MyInfoComponent} from "./my-info/my-info.component";

const routes: Routes = [
  {
    path: '',
    component: MyInfoComponent
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class MyInfoRoutingModule { }
