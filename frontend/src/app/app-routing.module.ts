import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {PageNotFoundComponent} from "./_components/page-not-found/page-not-found.component";

const routes: Routes = [
  {
    path: 'login',
    loadChildren: () => import('./login/login.module').then(mod => mod.LoginModule)
  },
  {
    path: 'setup',
    loadChildren: () => import('./setup/setup.module').then(mod => mod.SetupModule)
  },
  {
    path: '',
    loadChildren: () => import('./main/main.module').then(mod => mod.MainModule)
  },
  { path: '404', component: PageNotFoundComponent},
  { path: '**', redirectTo: '404', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
