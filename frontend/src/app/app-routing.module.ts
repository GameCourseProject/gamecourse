import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {PageNotFoundComponent} from "./_components/page-not-found/page-not-found.component";
import {SetupGuard} from "./_guards/setup.guard";

const routes: Routes = [
  {
    path: 'login',
    loadChildren: () => import('./login/login.module').then(mod => mod.LoginModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  {
    path: 'setup',
    loadChildren: () => import('./setup/setup.module').then(mod => mod.SetupModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  {
    path: 'main',
    loadChildren: () => import('./main/main.module').then(mod => mod.MainModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  { path: '', redirectTo: 'main', pathMatch: 'full' },
  { path: '404', component: PageNotFoundComponent},
  { path: '**', redirectTo: '404', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
